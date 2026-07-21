package com.smartforum.cache;

import java.io.File;
import java.sql.*;

/**
 * SQLite local desktop cache (SDD §3.1.1 — Local Desktop Cache).
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

    public Connection connect() throws SQLException {
        return DriverManager.getConnection(JDBC_URL);
    }

    public void initialise() {
        new File("data").mkdirs();
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            conn.setAutoCommit(false);
            createTables(st);
            migrateSchema(conn);
            conn.commit();
            System.out.println("[LocalCache] Schema initialised.");
        } catch (SQLException e) {
            throw new RuntimeException("Failed to initialise local cache: " + e.getMessage(), e);
        }
    }

    private void migrateSchema(Connection conn) throws SQLException {
        // Add user_id to cached_topics if missing
        try (ResultSet rs = conn.getMetaData().getColumns(null, null, "cached_topics", "user_id")) {
            if (!rs.next()) {
                conn.createStatement().execute(
                    "ALTER TABLE cached_topics ADD COLUMN user_id INTEGER NOT NULL DEFAULT 0");
                System.out.println("[LocalCache] Migrated: added user_id to cached_topics.");
            }
        }
        // Add role-specific profile columns to session_cache if missing
        String[][] newCols = {
            {"avatar",        "TEXT"},
            {"bio",           "TEXT"},
            {"is_active",     "INTEGER NOT NULL DEFAULT 1"},
            {"student_id",    "TEXT"},
            {"programme",     "TEXT"},
            {"year_of_study", "INTEGER NOT NULL DEFAULT 0"},
            {"reputation",    "INTEGER NOT NULL DEFAULT 0"},
            {"staff_id",      "TEXT"},
            {"department",    "TEXT"},
            {"specialisation","TEXT"},
            {"super_admin",   "INTEGER NOT NULL DEFAULT 0"}
        };
        for (String[] col : newCols) {
            try (ResultSet rs = conn.getMetaData().getColumns(null, null, "session_cache", col[0])) {
                if (!rs.next()) {
                    conn.createStatement().execute(
                        "ALTER TABLE session_cache ADD COLUMN " + col[0] + " " + col[1]);
                    System.out.println("[LocalCache] Migrated: added " + col[0] + " to session_cache.");
                }
            }
        }
    }

    private void createTables(Statement st) throws SQLException {

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

        st.execute("""
            CREATE TABLE IF NOT EXISTS cached_topics (
                id           INTEGER PRIMARY KEY,
                group_id     INTEGER NOT NULL,
                user_id      INTEGER NOT NULL DEFAULT 0,
                title        TEXT    NOT NULL,
                body         TEXT    NOT NULL,
                author_name  TEXT    NOT NULL,
                is_pinned    INTEGER NOT NULL DEFAULT 0,
                is_locked    INTEGER NOT NULL DEFAULT 0,
                views        INTEGER NOT NULL DEFAULT 0,
                cached_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            )
            """);

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

        // ── Offline statistics cache (Days 17-18) ─────────────────────────
        st.execute("""
            CREATE TABLE IF NOT EXISTS statistics_cache (
                id           INTEGER PRIMARY KEY CHECK (id = 1),
                stats_json   TEXT    NOT NULL,
                cached_at    TEXT    NOT NULL DEFAULT (datetime('now'))
            )
            """);
    }

    /** Persists the latest stats JSON for offline viewing. */
    public void saveStatistics(String statsJson) {
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            String escaped = statsJson.replace("'", "''");
            st.execute("INSERT OR REPLACE INTO statistics_cache(id, stats_json) VALUES(1, '" + escaped + "')");
        } catch (SQLException e) {
            System.err.println("[LocalCache] saveStatistics failed: " + e.getMessage());
        }
    }

    /** Returns the last cached stats JSON, or null if none. */
    public String loadStatistics() {
        try (Connection conn = connect();
             ResultSet rs = conn.createStatement().executeQuery(
                 "SELECT stats_json, cached_at FROM statistics_cache WHERE id = 1")) {
            if (rs.next()) {
                System.out.println("[LocalCache] Loaded offline stats cached at " + rs.getString("cached_at"));
                return rs.getString("stats_json");
            }
        } catch (SQLException e) {
            System.err.println("[LocalCache] loadStatistics failed: " + e.getMessage());
        }
        return null;
    }

    public void markAllSynced() {
        String[] tables = {"pending_messages", "pending_quiz_answers"};
        try (Connection conn = connect(); Statement st = conn.createStatement()) {
            for (String table : tables)
                st.execute("UPDATE " + table + " SET synced = 1 WHERE synced = 0");
        } catch (SQLException e) {
            throw new RuntimeException("markAllSynced failed: " + e.getMessage(), e);
        }
    }

    public void resetForTesting() {
        String[] tables = {
            "pending_messages", "cached_topics", "cached_posts",
            "pending_quiz_answers", "sync_log", "session_cache", "statistics_cache"
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
