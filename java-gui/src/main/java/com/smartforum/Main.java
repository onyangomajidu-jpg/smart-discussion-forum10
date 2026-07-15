package com.smartforum;

import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.sync.RealtimeSyncService;
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
 *   3. Wire ApiClient, AuthService, RealtimeSyncService
 *   4. Launch Swing LoginWindow on the EDT
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
        ApiClient api = new ApiClient();

        // ── 3. Auth + sync services ───────────────────────────────────────
        AuthService         authService = new AuthService(api, cache);
        RealtimeSyncService syncService = new RealtimeSyncService(api, cache);

        int syncInterval = Integer.parseInt(
            props.getProperty("sync.interval", "30"));
        syncService.start(syncInterval);

        // ── 4. GUI ────────────────────────────────────────────────────────
        SwingUtilities.invokeLater(() -> {
            try {
                UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
            } catch (Exception ignored) { /* fall back to default L&F */ }

            LoginWindow loginWindow = new LoginWindow(authService, cache);
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
