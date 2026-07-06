package com.smartforum.sync;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;

import java.io.IOException;
import java.sql.*;
import java.util.Map;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

/**
 * Realtime Synchronisation Service (SDD §3.1.3 — Offline Recovery Workflow).
 *
 * Responsibilities:
 *   syncMessages()      — push pending_messages to POST /api/posts
 *   fetchUpdates()      — pull new topics/posts from GET /api/topics/updates
 *   storeOfflineData()  — persist data locally when the server is unreachable
 *
 * Runs on a background scheduler; interval is configurable via
 * the sync.interval property (default 30 s).
 */
public class RealtimeSyncService {

    private final ApiClient            api;
    private final LocalCacheDatabase   cache;
    private final ObjectMapper         mapper  = new ObjectMapper();
    private final ScheduledExecutorService scheduler =
        Executors.newSingleThreadScheduledExecutor(r -> {
            Thread t = new Thread(r, "sync-thread");
            t.setDaemon(true);
            return t;
        });

    /** Timestamp of the last successful fetchUpdates() call (ISO-8601). */
    private String lastFetchedAt = "1970-01-01T00:00:00";

    public RealtimeSyncService(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────

    /**
     * Starts the background sync loop.
     * @param intervalSeconds how often to run a full sync cycle
     */
    public void start(int intervalSeconds) {
        scheduler.scheduleAtFixedRate(this::runCycle,
            0, intervalSeconds, TimeUnit.SECONDS);
        System.out.println("[RealtimeSync] Started — interval=" + intervalSeconds + "s");
    }

    public void stop() {
        scheduler.shutdownNow();
        System.out.println("[RealtimeSync] Stopped.");
    }

    // ── Main cycle ────────────────────────────────────────────────────────

    private void runCycle() {
        if (!api.isOnline()) {
            System.out.println("[RealtimeSync] Offline — cycle skipped.");
            return;
        }
        syncMessages();
        fetchUpdates();
    }

    // ── syncMessages() ────────────────────────────────────────────────────

    /**
     * Uploads all pending_messages (synced = 0) to POST /api/posts.
     * Marks each record synced on success; logs failures to sync_log.
     */
    public void syncMessages() {
        String sql = "SELECT id, topic_id, user_id, body FROM pending_messages WHERE synced = 0";
        try (Connection conn = cache.connect();
             Statement  st   = conn.createStatement();
             ResultSet  rs   = st.executeQuery(sql)) {

            while (rs.next()) {
                int    id      = rs.getInt("id");
                int    topicId = rs.getInt("topic_id");
                int    userId  = rs.getInt("user_id");
                String body    = rs.getString("body");

                try {
                    api.post("/posts", Map.of(
                        "topic_id", topicId,
                        "user_id",  userId,
                        "body",     body
                    ));
                    markSynced(conn, "pending_messages", id);
                    writeSyncLog(conn, "UPLOAD", "pending_messages", id, "success");
                    System.out.println("[RealtimeSync] syncMessages — uploaded id=" + id);
                } catch (IOException e) {
                    writeSyncLog(conn, "UPLOAD", "pending_messages", id, "failed");
                    System.err.println("[RealtimeSync] syncMessages — failed id=" + id
                        + ": " + e.getMessage());
                }
            }
        } catch (SQLException e) {
            System.err.println("[RealtimeSync] syncMessages SQL error: " + e.getMessage());
        }
    }

    // ── fetchUpdates() ────────────────────────────────────────────────────

    /**
     * Pulls topics and posts created/updated since {@code lastFetchedAt}
     * from GET /api/topics/updates?since=&lt;timestamp&gt; and caches them
     * locally via storeOfflineData().
     */
    public void fetchUpdates() {
        try {
            String endpoint = "/topics/updates?since=" + lastFetchedAt;
            String json     = api.get(endpoint);
            JsonNode root   = mapper.readTree(json);

            if (root.has("topics")) {
                for (JsonNode topic : root.get("topics")) {
                    storeOfflineData("topic", topic);
                }
            }
            if (root.has("posts")) {
                for (JsonNode post : root.get("posts")) {
                    storeOfflineData("post", post);
                }
            }

            // Advance the watermark
            if (root.has("fetched_at")) {
                lastFetchedAt = root.get("fetched_at").asText();
            }

            System.out.println("[RealtimeSync] fetchUpdates — done, since=" + lastFetchedAt);
        } catch (IOException e) {
            System.err.println("[RealtimeSync] fetchUpdates failed: " + e.getMessage());
        }
    }

    // ── storeOfflineData() ────────────────────────────────────────────────

    /**
     * Persists a single API payload node into the appropriate cache table.
     *
     * @param type  "topic" → cached_topics  |  "post" → cached_posts
     * @param node  JSON node from the API response
     */
    public void storeOfflineData(String type, JsonNode node) {
        try (Connection conn = cache.connect()) {
            switch (type) {
                case "topic" -> cacheTopic(conn, node);
                case "post"  -> cachePost(conn, node);
                default      -> System.err.println(
                    "[RealtimeSync] storeOfflineData — unknown type: " + type);
            }
        } catch (SQLException e) {
            System.err.println("[RealtimeSync] storeOfflineData failed: " + e.getMessage());
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private void cacheTopic(Connection conn, JsonNode n) throws SQLException {
        String sql = """
            INSERT OR REPLACE INTO cached_topics
                (id, group_id, title, body, author_name, is_pinned, is_locked, views, cached_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1,    n.path("id").asInt());
            ps.setInt(2,    n.path("group_id").asInt());
            ps.setString(3, n.path("title").asText());
            ps.setString(4, n.path("body").asText());
            ps.setString(5, n.path("author_name").asText("Unknown"));
            ps.setInt(6,    n.path("is_pinned").asInt(0));
            ps.setInt(7,    n.path("is_locked").asInt(0));
            ps.setInt(8,    n.path("views").asInt(0));
            ps.executeUpdate();
        }
    }

    private void cachePost(Connection conn, JsonNode n) throws SQLException {
        String sql = """
            INSERT OR REPLACE INTO cached_posts
                (id, topic_id, user_id, author_name, body,
                 is_best_answer, upvotes, downvotes, cached_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1,    n.path("id").asInt());
            ps.setInt(2,    n.path("topic_id").asInt());
            ps.setInt(3,    n.path("user_id").asInt());
            ps.setString(4, n.path("author_name").asText("Unknown"));
            ps.setString(5, n.path("body").asText());
            ps.setInt(6,    n.path("is_best_answer").asInt(0));
            ps.setInt(7,    n.path("upvotes").asInt(0));
            ps.setInt(8,    n.path("downvotes").asInt(0));
            ps.executeUpdate();
        }
    }

    private void markSynced(Connection conn, String table, int id) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "UPDATE " + table + " SET synced = 1 WHERE id = ?")) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    private void writeSyncLog(Connection conn, String op, String table,
                              int recordId, String status) {
        String sql = """
            INSERT INTO sync_log (operation, table_name, record_id, status, completed_at)
            VALUES (?, ?, ?, ?, datetime('now'))
            """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, op);
            ps.setString(2, table);
            ps.setInt(3,    recordId);
            ps.setString(4, status);
            ps.executeUpdate();
        } catch (SQLException e) {
            System.err.println("[RealtimeSync] sync_log write failed: " + e.getMessage());
        }
    }
}
