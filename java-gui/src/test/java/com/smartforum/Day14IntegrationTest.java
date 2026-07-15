package com.smartforum;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ObjectNode;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.sync.OfflineSyncManager;
import com.smartforum.sync.RealtimeSyncService;
import org.junit.jupiter.api.*;

import java.io.IOException;
import java.sql.*;
import java.util.Map;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicInteger;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Day 14 — Integration & mid-project review.
 *
 * Uses a hand-rolled ApiClient stub (no Mockito) so the test compiles
 * with only the existing JUnit 5 dependency.
 *
 * Covers:
 *   1. Post on web → appears on Java desktop in real time (fetchUpdates caches it)
 *   2. Go offline → write messages → reconnect → verify sync
 */
@TestMethodOrder(MethodOrderer.OrderAnnotation.class)
class Day14IntegrationTest {

    // ── Stub ─────────────────────────────────────────────────────────────

    /** Configurable stub that replaces real HTTP calls. */
    static class StubApiClient extends ApiClient {
        boolean online          = true;
        String  getResponse     = "{}";
        String  postResponse    = "{}";
        boolean postShouldThrow = false;
        final AtomicInteger postCallCount = new AtomicInteger(0);
        final AtomicInteger getCallCount  = new AtomicInteger(0);

        @Override public boolean isOnline() { return online; }

        @Override
        public String get(String endpoint) throws IOException {
            getCallCount.incrementAndGet();
            return getResponse;
        }

        @Override
        public String post(String endpoint, Map<String, Object> body) throws IOException {
            postCallCount.incrementAndGet();
            if (postShouldThrow) throw new IOException("Simulated network error");
            return postResponse;
        }
    }

    // ── Fixtures ──────────────────────────────────────────────────────────

    private static LocalCacheDatabase db;
    private StubApiClient      stub;
    private OfflineSyncManager offlineSync;
    private RealtimeSyncService realtimeSync;
    private final ObjectMapper mapper = new ObjectMapper();

    @BeforeAll
    static void initDb() {
        System.setProperty("cache.db.path", ":memory:");
        db = LocalCacheDatabase.getInstance();
        db.initialise();
    }

    @BeforeEach
    void setUp() {
        stub         = new StubApiClient();
        offlineSync  = new OfflineSyncManager(stub, db);
        realtimeSync = new RealtimeSyncService(stub, db);
    }

    // ── Test 1: Post on web → appears on Java desktop via fetchUpdates ────

    /**
     * Simulates the server returning a new post (created via the web) in the
     * fetchUpdates delta.  After the call the post must be in cached_posts.
     */
    @Test
    @Order(1)
    void webPost_appearsOnDesktop_viaFetchUpdates() throws Exception {
        ObjectNode post = mapper.createObjectNode();
        post.put("id",             99);
        post.put("topic_id",       1);
        post.put("user_id",        2);
        post.put("author_name",    "Alice");
        post.put("body",           "Hello from the web!");
        post.put("is_best_answer", 0);
        post.put("upvotes",        0);
        post.put("downvotes",      0);

        ObjectNode response = mapper.createObjectNode();
        response.set("topics",     mapper.createArrayNode());
        response.set("posts",      mapper.createArrayNode().add(post));
        response.put("fetched_at", "2025-01-01T12:00:00");

        stub.online      = true;
        stub.getResponse = response.toString();

        realtimeSync.fetchUpdates();

        try (Connection conn = db.connect();
             PreparedStatement ps = conn.prepareStatement(
                 "SELECT body FROM cached_posts WHERE id = ?")) {
            ps.setInt(1, 99);
            ResultSet rs = ps.executeQuery();
            assertTrue(rs.next(), "Post should be cached after fetchUpdates");
            assertEquals("Hello from the web!", rs.getString("body"));
        }
    }

    // ── Test 2: Offline → write messages → reconnect → verify sync ────────

    /** While offline, messages are stored locally (synced = 0). */
    @Test
    @Order(2)
    void offline_messagesStoredLocally() throws SQLException {
        stub.online = false;

        offlineSync.storeOfflineData(1, 10, "Offline msg A");
        offlineSync.storeOfflineData(1, 10, "Offline msg B");

        try (Connection conn = db.connect();
             ResultSet rs = conn.createStatement().executeQuery(
                 "SELECT COUNT(*) AS cnt FROM pending_messages WHERE synced = 0")) {
            assertTrue(rs.next());
            assertTrue(rs.getInt("cnt") >= 2, "Both messages should be pending");
        }
    }

    /** sendOrQueue falls back to local storage when offline. */
    @Test
    @Order(3)
    void sendOrQueue_queuesWhenOffline() throws SQLException {
        stub.online = false;

        boolean sentLive = offlineSync.sendOrQueue(2, 10, "Queued offline");

        assertFalse(sentLive, "Should return false when queued offline");
        assertDatabaseHasPending("Queued offline");
    }

    /** On reconnect, synchronizeOfflineData uploads pending messages. */
    @Test
    @Order(4)
    void reconnect_synchronizeOfflineData_uploadsPending() throws Exception {
        offlineSync.storeOfflineData(1, 10, "Reconnect upload");

        stub.online      = true;
        stub.postResponse = "{\"id\":200}";
        stub.getResponse  = emptyDeltaJson();

        offlineSync.synchronizeOfflineData();

        assertTrue(stub.postCallCount.get() >= 1,
            "POST /posts should have been called at least once");
    }

    /** After sync, pending messages are marked synced = 1. */
    @Test
    @Order(5)
    void reconnect_pendingMessages_markedSyncedAfterUpload() throws Exception {
        // Clear previous pending rows
        try (Connection conn = db.connect()) {
            conn.createStatement().execute("UPDATE pending_messages SET synced = 1");
        }

        offlineSync.storeOfflineData(3, 10, "Must be synced");

        stub.online       = true;
        stub.postResponse = "{\"id\":201}";
        stub.getResponse  = emptyDeltaJson();

        offlineSync.synchronizeOfflineData();

        try (Connection conn = db.connect();
             ResultSet rs = conn.createStatement().executeQuery(
                 "SELECT COUNT(*) AS cnt FROM pending_messages WHERE synced = 0")) {
            assertTrue(rs.next());
            assertEquals(0, rs.getInt("cnt"),
                "No pending messages should remain after sync");
        }
    }

    /** SyncListener is notified after a successful synchronizeOfflineData(). */
    @Test
    @Order(6)
    void reconnect_syncListener_isNotifiedOnCompletion() throws Exception {
        AtomicBoolean notified = new AtomicBoolean(false);
        offlineSync.setSyncListener(() -> notified.set(true));

        stub.online      = true;
        stub.postResponse = "{}";
        stub.getResponse  = emptyDeltaJson();

        offlineSync.synchronizeOfflineData();

        assertTrue(notified.get(), "SyncListener.onSyncComplete() must be called");
    }

    /** synchronizeOfflineData() is a no-op when still offline. */
    @Test
    @Order(7)
    void synchronizeOfflineData_isNoOp_whenStillOffline() throws Exception {
        stub.online = false;
        int postsBefore = stub.postCallCount.get();
        int getsBefore  = stub.getCallCount.get();

        offlineSync.synchronizeOfflineData();

        assertEquals(postsBefore, stub.postCallCount.get(), "No POST calls when offline");
        assertEquals(getsBefore,  stub.getCallCount.get(),  "No GET calls when offline");
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private String emptyDeltaJson() throws Exception {
        ObjectNode n = mapper.createObjectNode();
        n.set("topics",     mapper.createArrayNode());
        n.set("posts",      mapper.createArrayNode());
        n.put("fetched_at", "2025-01-01T12:00:00");
        return n.toString();
    }

    private void assertDatabaseHasPending(String body) throws SQLException {
        try (Connection conn = db.connect();
             PreparedStatement ps = conn.prepareStatement(
                 "SELECT 1 FROM pending_messages WHERE body = ? AND synced = 0")) {
            ps.setString(1, body);
            ResultSet rs = ps.executeQuery();
            assertTrue(rs.next(), "Expected pending message with body: " + body);
        }
    }
}
