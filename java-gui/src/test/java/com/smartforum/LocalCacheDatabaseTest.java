package com.smartforum;

import com.smartforum.cache.LocalCacheDatabase;
import org.junit.jupiter.api.*;

import java.sql.*;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Verifies that LocalCacheDatabase creates the correct schema
 * and that basic insert/query operations work against SQLite.
 */
@TestMethodOrder(MethodOrderer.OrderAnnotation.class)
class LocalCacheDatabaseTest {

    private static LocalCacheDatabase db;

    @BeforeAll
    static void setUp() {
        // Use an in-memory SQLite database for tests
        System.setProperty("cache.db.path", ":memory:");
        db = LocalCacheDatabase.getInstance();
        db.initialise();
    }

    @Test
    @Order(1)
    void schemaCreatesAllTables() throws SQLException {
        String[] expected = {
            "pending_messages", "cached_topics", "cached_posts",
            "pending_quiz_answers", "sync_log", "session_cache"
        };
        try (Connection conn = db.connect()) {
            DatabaseMetaData meta = conn.getMetaData();
            for (String table : expected) {
                ResultSet rs = meta.getTables(null, null, table, new String[]{"TABLE"});
                assertTrue(rs.next(), "Table missing: " + table);
            }
        }
    }

    @Test
    @Order(2)
    void canInsertAndReadPendingMessage() throws SQLException {
        try (Connection conn = db.connect()) {
            conn.createStatement().execute(
                "INSERT INTO pending_messages (topic_id, user_id, body) VALUES (1, 2, 'Hello offline')"
            );
            ResultSet rs = conn.createStatement().executeQuery(
                "SELECT body FROM pending_messages WHERE synced = 0"
            );
            assertTrue(rs.next());
            assertEquals("Hello offline", rs.getString("body"));
        }
    }

    @Test
    @Order(3)
    void markAllSyncedUpdatesRecords() throws SQLException {
        db.markAllSynced();
        try (Connection conn = db.connect();
             ResultSet rs = conn.createStatement().executeQuery(
                 "SELECT COUNT(*) as cnt FROM pending_messages WHERE synced = 0")) {
            assertTrue(rs.next());
            assertEquals(0, rs.getInt("cnt"));
        }
    }
}
