package com.smartforum.cache;

import java.io.File;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;

/**
 * SQLite local desktop cache (SDD §3.1.1 — Local Desktop Cache).
 *
 * Stores offline data such as unsent messages and recently accessed
 * discussions. Synchronises with the central MySQL database on
 * connectivity restoration.
 *
 * DB file location: java-gui/data/local_cache.db
 */
public class LocalCacheDatabase {

    public static final String DB_PATH = "data/local_cache.db";
    private static final String JDBC_URL = "jdbc:sqlite:" + DB_PATH;

    private static LocalCacheDatabase instance;

    private LocalCacheDatabase() {}

    public static synchronized LocalCacheDatabase getInstance() {
        if (instance == null) instance = new LocalCacheDatabase();
        return instance;
    }

    /** Opens a connection to the SQLite file. Caller must close it. */
    public Connection connect() throws SQLException {
        return DriverManager.getConnection(JDBC_URL);
    }

    /**
     * Creates the data/ directory and all cache tables if they do not
     * already exist.  Safe to call on every startup.
     */
    public void initialise() {
        new File("data").mkdirs();
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            conn.setAutoCommit(false);
            createTables(st);
            conn.commit();
            System.out.println("[LocalCache] Schema initialised.");
        } catch (SQLException e) {
            throw new RuntimeException("Failed to initialise local cache: " + e.getMessage(), e);
        }
    }

    private void createTables(Statement st) throws SQLException {

        // ── Pending outbound messages (unsent while offline) ──────────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS pending_messages (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id     INTEGER NOT NULL,
                user_id      INTEGER NOT NULL,
                body         TEXT    NOT NULL,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                synced       INTEGER NOT NULL DEFAULT 0
            )
            """);

        // ── Cached topics (recently accessed, read offline) ───────────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS cached_topics (
                id           INTEGER PRIMARY KEY,
                group_id     INTEGER NOT NULL,
                title        TEXT    NOT NULL,
                body         TEXT    NOT NULL,
                author_name  TEXT    NOT NULL,
                is_pinned    INTEGER NOT NULL DEFAULT 0,
                is_locked    INTEGER NOT NULL DEFAULT 0,
                views        INTEGER NOT NULL DEFAULT 0,
                cached_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            )
            """);

        // ── Cached posts for offline reading ──────────────────────────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS cached_posts (
                id              INTEGER PRIMARY KEY,
                topic_id        INTEGER NOT NULL,
                user_id         INTEGER NOT NULL,
                author_name     TEXT    NOT NULL,
                body            TEXT    NOT NULL,
                is_best_answer  INTEGER NOT NULL DEFAULT 0,
                upvotes         INTEGER NOT NULL DEFAULT 0,
                downvotes       INTEGER NOT NULL DEFAULT 0,
                cached_at       TEXT    NOT NULL DEFAULT (datetime('now'))
            )
            """);

        // ── Pending quiz answers (submitted offline, sync later) ──────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS pending_quiz_answers (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                quiz_id      INTEGER NOT NULL,
                user_id      INTEGER NOT NULL,
                answers_json TEXT    NOT NULL,
                submitted_at TEXT    NOT NULL DEFAULT (datetime('now')),
                synced       INTEGER NOT NULL DEFAULT 0
            )
            """);

        // ── Sync log — tracks what was pushed / pulled each session ───────
        st.execute("""
            CREATE TABLE IF NOT EXISTS sync_log (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                operation    TEXT    NOT NULL,
                table_name   TEXT    NOT NULL,
                record_id    INTEGER,
                status       TEXT    NOT NULL DEFAULT 'pending',
                attempted_at TEXT    NOT NULL DEFAULT (datetime('now')),
                completed_at TEXT
            )
            """);

        // ── Cached user session (avoids re-login after reconnect) ─────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS session_cache (
                id           INTEGER PRIMARY KEY CHECK (id = 1),
                user_id      INTEGER NOT NULL,
                name         TEXT    NOT NULL,
                email        TEXT    NOT NULL,
                role         TEXT    NOT NULL,
                token        TEXT    NOT NULL,
                saved_at     TEXT    NOT NULL DEFAULT (datetime('now'))
            )
            """);
    }

    /** Marks all pending_messages and pending_quiz_answers as synced. */
    public void markAllSynced() {
        String[] tables = {"pending_messages", "pending_quiz_answers"};
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            for (String table : tables) {
                st.execute("UPDATE " + table + " SET synced = 1 WHERE synced = 0");
            }
        } catch (SQLException e) {
            throw new RuntimeException("markAllSynced failed: " + e.getMessage(), e);
        }
    }

    /** Drops and recreates all tables — use only in tests. */
    public void resetForTesting() {
        String[] tables = {
            "pending_messages", "cached_topics", "cached_posts",
            "pending_quiz_answers", "sync_log", "session_cache"
        };
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            conn.setAutoCommit(false);
            for (String t : tables) st.execute("DROP TABLE IF EXISTS " + t);
            conn.commit();
            initialise();
        } catch (SQLException e) {
            throw new RuntimeException("resetForTesting failed: " + e.getMessage(), e);
        }
    }
}
