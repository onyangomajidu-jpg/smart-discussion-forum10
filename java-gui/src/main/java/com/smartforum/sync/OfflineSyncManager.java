package com.smartforum.sync;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;

import java.io.IOException;
import java.sql.*;
import java.util.Map;

/**
 * Offline write + auto-sync on reconnect (SDD §3.1.3).
 *
 * storeOfflineData()       — persist an unsent message to pending_messages
 * synchronizeOfflineData() — upload pending rows, download missing records,
 *                            update local cache
 * sendOrQueue()            — convenience: try live POST, fall back to store
 */
public class OfflineSyncManager {

    private final ApiClient          api;
    private final LocalCacheDatabase cache;
    private final ObjectMapper       mapper = new ObjectMapper();

    /** Listener notified after a successful sync so the UI can refresh. */
    public interface SyncListener {
        void onSyncComplete();
    }

    private SyncListener syncListener;

    public OfflineSyncManager(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
    }

    public void setSyncListener(SyncListener l) { this.syncListener = l; }

    // ── storeOfflineData() ────────────────────────────────────────────────

    /**
     * Saves an unsent message to {@code pending_messages} (synced = 0).
     * Called when the server is unreachable.
     */
    public void storeOfflineData(int topicId, int userId, String body) {
        String sql = "INSERT INTO pending_messages (topic_id, user_id, body) VALUES (?, ?, ?)";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1,    topicId);
            ps.setInt(2,    userId);
            ps.setString(3, body);
            ps.executeUpdate();
            System.out.println("[OfflineSync] storeOfflineData — queued topicId=" + topicId);
        } catch (SQLException e) {
            System.err.println("[OfflineSync] storeOfflineData failed: " + e.getMessage());
        }
    }

    // ── synchronizeOfflineData() ──────────────────────────────────────────

    /**
     * Full sync on reconnect:
     *   1. Upload every pending_message (synced = 0) to POST /api/posts
     *   2. Download topics/posts from GET /api/topics/updates?since=0
     *      and upsert into cached_topics / cached_posts
     *   3. Notify the UI listener
     */
    public void synchronizeOfflineData() {
        if (!api.isOnline()) {
            System.out.println("[OfflineSync] Still offline — sync deferred.");
            return;
        }
        System.out.println("[OfflineSync] Online — starting synchronizeOfflineData()");
        uploadPending();
        downloadMissing();
        if (syncListener != null) syncListener.onSyncComplete();
        System.out.println("[OfflineSync] synchronizeOfflineData() complete.");
    }

    // ── createTopic() ─────────────────────────────────────────────────────

    public void createTopic(String title, String body, int userId) throws IOException {
        api.post("/topics", Map.of(
            "title",   title,
            "body",    body,
            "user_id", userId
        ));
        synchronizeOfflineData();
    }

    // ── editPost() ────────────────────────────────────────────────────────

    public void editPost(int postId, String newBody) throws IOException {
        api.put("/posts/" + postId, Map.of("body", newBody));
        synchronizeOfflineData();
    }

    // ── deletePost() ──────────────────────────────────────────────────────

    public void deletePost(int postId) throws IOException {
        api.delete("/posts/" + postId);
        synchronizeOfflineData();
    }

    // ── deleteTopic() ─────────────────────────────────────────────────────

    public void deleteTopic(int topicId) throws IOException {
        api.delete("/topics/" + topicId);
        synchronizeOfflineData();
    }

    // ── sendOrQueue() ─────────────────────────────────────────────────────

    /**
     * Tries to POST the message live; falls back to storeOfflineData().
     *
     * @return true if sent live, false if queued offline
     */
    public boolean sendOrQueue(int topicId, int userId, String body) {
        if (api.isOnline()) {
            try {
                api.post("/posts", Map.of(
                    "topic_id", topicId,
                    "user_id",  userId,
                    "body",     body
                ));
                downloadMissing();
                return true;
            } catch (IOException e) {
                System.err.println("[OfflineSync] Live send failed, queuing: " + e.getMessage());
            }
        }
        storeOfflineData(topicId, userId, body);
        return false;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private void uploadPending() {
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
                    markSynced(conn, id);
                    writeSyncLog(conn, "UPLOAD", "pending_messages", id, "success");
                    System.out.println("[OfflineSync] uploaded pending id=" + id);
                } catch (IOException e) {
                    writeSyncLog(conn, "UPLOAD", "pending_messages", id, "failed");
                    System.err.println("[OfflineSync] upload failed id=" + id + ": " + e.getMessage());
                }
            }
        } catch (SQLException e) {
            System.err.println("[OfflineSync] uploadPending SQL error: " + e.getMessage());
        }
    }

    private void downloadMissing() {
        try {
            String   json = api.get("/topics/updates?since=1970-01-01+00:00:00");
            JsonNode root = mapper.readTree(json);

            try (Connection conn = cache.connect()) {
                if (root.has("topics")) {
                    for (JsonNode n : root.get("topics")) upsertTopic(conn, n);
                }
                if (root.has("posts")) {
                    for (JsonNode n : root.get("posts"))  upsertPost(conn, n);
                }
            }
            System.out.println("[OfflineSync] downloadMissing complete.");
        } catch (Exception e) {
            System.err.println("[OfflineSync] downloadMissing failed: " + e.getMessage());
        }
    }

    private void upsertTopic(Connection conn, JsonNode n) throws SQLException {
        String sql = """
            INSERT OR REPLACE INTO cached_topics
                (id, group_id, user_id, title, body, author_name, is_pinned, is_locked, views, cached_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1,    n.path("id").asInt());
            ps.setInt(2,    n.path("group_id").asInt());
            ps.setInt(3,    n.path("user_id").asInt(0));
            ps.setString(4, n.path("title").asText());
            ps.setString(5, n.path("body").asText());
            ps.setString(6, n.path("author_name").asText("Unknown"));
            ps.setInt(7,    n.path("is_pinned").asInt(0));
            ps.setInt(8,    n.path("is_locked").asInt(0));
            ps.setInt(9,    n.path("views").asInt(0));
            ps.executeUpdate();
        }
    }

    private void upsertPost(Connection conn, JsonNode n) throws SQLException {
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

    private void markSynced(Connection conn, int id) throws SQLException {
        try (PreparedStatement ps = conn.prepareStatement(
                "UPDATE pending_messages SET synced = 1 WHERE id = ?")) {
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
            System.err.println("[OfflineSync] sync_log write failed: " + e.getMessage());
        }
    }
}
