package com.smartforum.auth;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Map;

/**
 * Handles authentication against the Laravel backend (SDD §3.1.2).
 *
 * POST /api/login  → { email, password }
 *   ← { token, user: { id, name, email, role } }
 *
 * POST /api/logout (Bearer token)
 *
 * Falls back to the session_cache table when offline.
 */
public class AuthService {

    private final ApiClient api;
    private final LocalCacheDatabase cache;
    private final ObjectMapper mapper = new ObjectMapper();

    /** Currently authenticated user; null when logged out. */
    private AuthUser currentUser;

    public AuthService(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
    }

    // ── Online login ──────────────────────────────────────────────────────

    /**
     * Authenticates against the Laravel backend.
     * On success the token is stored in ApiClient and the session is
     * persisted to session_cache for offline fallback.
     *
     * @throws IOException           on network failure
     * @throws AuthException         on invalid credentials (HTTP 401/422)
     */
    public AuthUser login(String email, String password)
            throws IOException, AuthException {

        String responseBody;
        try {
            responseBody = api.post("/login", Map.of(
                    "email",    email,
                    "password", password
            ));
        } catch (IOException e) {
            // Server unreachable — try offline session cache
            AuthUser cached = loadCachedSession();
            if (cached != null && cached.getEmail().equalsIgnoreCase(email)) {
                currentUser = cached;
                api.setToken(cached.getToken());
                return cached;
            }
            throw new IOException("Server unreachable and no cached session found.", e);
        }

        JsonNode root = mapper.readTree(responseBody);

        if (root.has("error") || root.has("message") && !root.has("token")) {
            String msg = root.has("message")
                    ? root.get("message").asText()
                    : "Authentication failed.";
            throw new AuthException(msg);
        }

        String token = root.get("token").asText();
        JsonNode userNode = root.get("user");

        AuthUser user = new AuthUser(
                userNode.get("id").asInt(),
                userNode.get("name").asText(),
                userNode.get("email").asText(),
                userNode.get("role").asText("member"),
                token
        );

        api.setToken(token);
        currentUser = user;
        persistSession(user);
        return user;
    }

    // ── Logout ────────────────────────────────────────────────────────────

    public void logout() {
        if (api.isOnline() && currentUser != null) {
            try {
                api.post("/logout", Map.of());
            } catch (IOException ignored) { /* best-effort */ }
        }
        api.setToken(null);
        currentUser = null;
        clearCachedSession();
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public AuthUser getCurrentUser() { return currentUser; }
    public boolean isLoggedIn()      { return currentUser != null; }

    // ── Session cache helpers ─────────────────────────────────────────────

    private void persistSession(AuthUser u) {
        String sql = """
            INSERT OR REPLACE INTO session_cache
                (id, user_id, name, email, role, token, saved_at)
            VALUES (1, ?, ?, ?, ?, ?, datetime('now'))
            """;
        try (Connection c = cache.connect();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setInt(1,    u.getUserId());
            ps.setString(2, u.getName());
            ps.setString(3, u.getEmail());
            ps.setString(4, u.getRole());
            ps.setString(5, u.getToken());
            ps.executeUpdate();
        } catch (SQLException e) {
            System.err.println("[Auth] Could not persist session: " + e.getMessage());
        }
    }

    private AuthUser loadCachedSession() {
        String sql = "SELECT user_id, name, email, role, token FROM session_cache WHERE id = 1";
        try (Connection c = cache.connect();
             PreparedStatement ps = c.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            if (rs.next()) {
                return new AuthUser(
                        rs.getInt("user_id"),
                        rs.getString("name"),
                        rs.getString("email"),
                        rs.getString("role"),
                        rs.getString("token")
                );
            }
        } catch (SQLException e) {
            System.err.println("[Auth] Could not load cached session: " + e.getMessage());
        }
        return null;
    }

    private void clearCachedSession() {
        try (Connection c = cache.connect();
             PreparedStatement ps = c.prepareStatement("DELETE FROM session_cache WHERE id = 1")) {
            ps.executeUpdate();
        } catch (SQLException e) {
            System.err.println("[Auth] Could not clear session cache: " + e.getMessage());
        }
    }
}
