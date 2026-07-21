package com.smartforum;

import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.ui.LoginWindow;

import javax.swing.*;
import java.io.InputStream;
import java.util.Properties;

/**
 * Entry point for the Smart Discussion Forum desktop client.
 *
 * Boot sequence (SDD §3.1):
 *   1. Load app.properties
 *   2. Initialise SQLite local cache (creates tables if absent)
 *   3. Wire ApiClient, AuthService
 *   4. Launch Swing LoginWindow on the EDT
 *
 * Sync is handled entirely by OfflineSyncManager inside MainWindow
 * (reconnect poller every 10 s). RealtimeSyncService is not started
 * here to avoid duplicate pending_messages uploads.
 */
public class Main {

    public static void main(String[] args) {
        Properties props = loadProperties();

        // ── 1. Local cache ────────────────────────────────────────────────
        LocalCacheDatabase cache = LocalCacheDatabase.getInstance();
        cache.initialise();

        // ── 2. API client ─────────────────────────────────────────────────
        String baseUrl = props.getProperty("api.baseUrl", "http://localhost:8000/api");
        System.setProperty("api.baseUrl", baseUrl);
        String wsUrl = props.getProperty("ws.url", "ws://localhost:8080/app/local");
        System.setProperty("ws.url", wsUrl);
        ApiClient api = new ApiClient();

        // ── 3. Auth service ───────────────────────────────────────────────
        AuthService authService = new AuthService(api, cache);

        // ── 4. GUI ────────────────────────────────────────────────────────
        // Pass api + cache into LoginWindow so it can construct MainWindow
        final ApiClient          apiFinal   = api;
        final LocalCacheDatabase cacheFinal = cache;
        SwingUtilities.invokeLater(() -> {
            try {
                UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
            } catch (Exception ignored) { /* fall back to default L&F */ }

            LoginWindow loginWindow = new LoginWindow(authService, apiFinal, cacheFinal);
            loginWindow.setVisible(true);
        });
    }

    private static Properties loadProperties() {
        Properties props = new Properties();
        try (InputStream in = Main.class.getResourceAsStream("/app.properties")) {
            if (in != null) props.load(in);
        } catch (Exception e) {
            System.err.println("[Main] Could not load app.properties: " + e.getMessage());
        }
        return props;
    }
}
