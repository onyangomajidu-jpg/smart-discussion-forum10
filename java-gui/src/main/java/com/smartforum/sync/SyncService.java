package com.smartforum.sync;

import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;

import java.io.IOException;
import java.sql.*;
import java.util.HashMap;
import java.util.Map;

/**
 * Offline Recovery and Synchronisation Workflow (SDD §3.1.3).
 *
 * When the desktop client reconnects, locally cached messages and
 * pending quiz answers are transmitted to the Laravel API. Timestamps
 * and message histories are reconciled, and the sync_log is updated.
 */
public class SyncService {

    private final LocalCacheDatabase cache;
    private final ApiClient api;

    public SyncService(LocalCacheDatabase cache, ApiClient api) {
        this.cache = cache;
        this.api   = api;
    }

    /**
     * Runs a full sync cycle:
     * 1. Upload pending_messages
     * 2. Upload pending_quiz_answers
     * 3. Mark all as synced
     */
    public void sync() {
        if (!api.isOnline()) {
            System.out.println("[Sync] Offline — sync skipped.");
            return;
        }
        System.out.println("[Sync] Online — starting sync...");
        uploadPendingMessages();
        uploadPendingQuizAnswers();
        cache.markAllSynced();
        System.out.println("[Sync] Sync complete.");
    }

    private void uploadPendingMessages() {
        String sql = "SELECT id, topic_id, user_id, body FROM pending_messages WHERE synced = 0";
        try (Connection conn = cache.connect();
             Statement st   = conn.createStatement();
             ResultSet rs   = st.executeQuery(sql)) {

            while (rs.next()) {
                Map<String, Object> body = new HashMap<>();
                body.put("topic_id", rs.getInt("topic_id"));
                body.put("user_id",  rs.getInt("user_id"));
                body.put("body",     rs.getString("body"));

                try {
                    api.post("/posts", body);
                    logSync(conn, "UPLOAD", "pending_messages", rs.getInt("id"), "success");
                } catch (IOException e) {
                    logSync(conn, "UPLOAD", "pending_messages", rs.getInt("id"), "failed");
                    System.err.println("[Sync] Failed to upload message id=" + rs.getInt("id"));
                }
            }
        } catch (SQLException e) {
            throw new RuntimeException("uploadPendingMessages failed: " + e.getMessage(), e);
        }
    }

    private void uploadPendingQuizAnswers() {
        String sql = "SELECT id, quiz_id, user_id, answers_json FROM pending_quiz_answers WHERE synced = 0";
        try (Connection conn = cache.connect();
             Statement st   = conn.createStatement();
             ResultSet rs   = st.executeQuery(sql)) {

            while (rs.next()) {
                Map<String, Object> body = new HashMap<>();
                body.put("quiz_id",      rs.getInt("quiz_id"));
                body.put("user_id",      rs.getInt("user_id"));
                body.put("answers",      rs.getString("answers_json"));

                try {
                    api.post("/quiz-attempts", body);
                    logSync(conn, "UPLOAD", "pending_quiz_answers", rs.getInt("id"), "success");
                } catch (IOException e) {
                    logSync(conn, "UPLOAD", "pending_quiz_answers", rs.getInt("id"), "failed");
                    System.err.println("[Sync] Failed to upload quiz attempt id=" + rs.getInt("id"));
                }
            }
        } catch (SQLException e) {
            throw new RuntimeException("uploadPendingQuizAnswers failed: " + e.getMessage(), e);
        }
    }

    private void logSync(Connection conn, String op, String table, int recordId, String status) {
        String sql = """
            INSERT INTO sync_log (operation, table_name, record_id, status, completed_at)
            VALUES (?, ?, ?, ?, datetime('now'))
            """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, op);
            ps.setString(2, table);
            ps.setInt(3, recordId);
            ps.setString(4, status);
            ps.executeUpdate();
        } catch (SQLException e) {
            System.err.println("[Sync] Could not write sync_log: " + e.getMessage());
        }
    }
}
