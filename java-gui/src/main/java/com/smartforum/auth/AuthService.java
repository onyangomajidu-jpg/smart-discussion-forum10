package com.smartforum.auth;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AdminProfile;
import com.smartforum.model.AuthUser;
import com.smartforum.model.LecturerProfile;
import com.smartforum.model.MemberProfile;

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
 * GET /api/profile (after login) → full user + role-specific profile
 *
 * Falls back to the session_cache table when offline.
 */
public class AuthService {

    private final ApiClient api;
    private final LocalCacheDatabase cache;
    private final ObjectMapper mapper = new ObjectMapper();

    private AuthUser currentUser;

    public AuthService(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
    }

    // ── Online login ──────────────────────────────────────────────────────

    public AuthUser login(String email, String password)
            throws IOException, AuthException {

        String responseBody;
        try {
            responseBody = api.post("/login", Map.of(
                    "email",    email,
                    "password", password
            ));
        } catch (IOException e) {
            String msg = e.getMessage() != null ? e.getMessage() : "";
            if (msg.contains("401") || msg.contains("422")) {
                throw new AuthException("Invalid credentials.");
            }
            AuthUser cached = loadCachedSession();
            if (cached != null && cached.getEmail().equalsIgnoreCase(email)) {
                currentUser = cached;
                api.setToken(cached.getToken());
                return cached;
            }
            throw new IOException("Server unreachable and no cached session found.", e);
        }

        JsonNode root = mapper.readTree(responseBody);

        if (root.has("error") || (root.has("message") && !root.has("token"))) {
            String msg = root.has("message")
                    ? root.get("message").asText()
                    : "Authentication failed.";
            throw new AuthException(msg);
        }

        String   token    = root.get("token").asText();
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

        // Fetch full profile (avatar, bio, is_active, role-specific fields)
        enrichFromProfile(user);

        persistSession(user);
        return user;
    }

    // ── Logout ────────────────────────────────────────────────────────────

    public void logout() {
        if (api.isOnline() && currentUser != null) {
            try { api.post("/logout", Map.of()); } catch (IOException ignored) {}
        }
        api.setToken(null);
        currentUser = null;
        clearCachedSession();
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public AuthUser getCurrentUser() { return currentUser; }
    public boolean  isLoggedIn()     { return currentUser != null; }

    // ── Profile enrichment ────────────────────────────────────────────────

    /**
     * Calls GET /api/profile and populates avatar, bio, is_active and the
     * role-specific profile (member / lecturer / admin) on the AuthUser.
     */
    private void enrichFromProfile(AuthUser user) {
        try {
            JsonNode root = mapper.readTree(api.get("/profile"));
            JsonNode u    = root.path("user");

            // Rebuild with full fields — token stays the same
            AuthUser enriched = new AuthUser(
                    u.path("id").asInt(user.getUserId()),
                    u.path("name").asText(user.getName()),
                    u.path("email").asText(user.getEmail()),
                    u.path("role").asText(user.getRole()),
                    user.getToken(),
                    u.path("avatar").asText(null),
                    u.path("bio").asText(null),
                    u.path("is_active").asBoolean(true)
            );

            // Role-specific profile
            if (enriched.isMember()) {
                JsonNode m = u.path("member");
                if (!m.isMissingNode()) {
                    enriched.setMemberProfile(new MemberProfile(
                            m.path("student_id").asText(null),
                            m.path("programme").asText(null),
                            m.path("year_of_study").asInt(0),
                            m.path("reputation").asInt(0)
                    ));
                }
            } else if (enriched.isLecturer()) {
                JsonNode l = u.path("lecturer");
                if (!l.isMissingNode()) {
                    enriched.setLecturerProfile(new LecturerProfile(
                            l.path("staff_id").asText(null),
                            l.path("department").asText(null),
                            l.path("specialisation").asText(null)
                    ));
                }
            } else if (enriched.isAdmin()) {
                JsonNode a = u.path("admin");
                if (!a.isMissingNode()) {
                    enriched.setAdminProfile(new AdminProfile(
                            a.path("super_admin").asBoolean(false)
                    ));
                }
            }

            currentUser = enriched;

        } catch (Exception e) {
            System.err.println("[Auth] Could not enrich profile: " + e.getMessage());
            // Non-fatal — user still logged in with basic fields
        }
    }

    // ── Session cache helpers ─────────────────────────────────────────────

    private void persistSession(AuthUser u) {
        String sql = """
            INSERT OR REPLACE INTO session_cache
                (id, user_id, name, email, role, token, avatar, bio, is_active,
                 student_id, programme, year_of_study, reputation,
                 staff_id, department, specialisation,
                 super_admin, saved_at)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, datetime('now'))
            """;
        try (Connection c = cache.connect();
             PreparedStatement ps = c.prepareStatement(sql)) {
            ps.setInt(1,    u.getUserId());
            ps.setString(2, u.getName());
            ps.setString(3, u.getEmail());
            ps.setString(4, u.getRole());
            ps.setString(5, u.getToken());
            ps.setString(6, u.getAvatar());
            ps.setString(7, u.getBio());
            ps.setInt(8,    u.isActive() ? 1 : 0);

            MemberProfile m = u.getMemberProfile();
            ps.setString(9,  m != null ? m.studentId   : null);
            ps.setString(10, m != null ? m.programme    : null);
            ps.setInt(11,    m != null ? m.yearOfStudy  : 0);
            ps.setInt(12,    m != null ? m.reputation   : 0);

            LecturerProfile l = u.getLecturerProfile();
            ps.setString(13, l != null ? l.staffId        : null);
            ps.setString(14, l != null ? l.department      : null);
            ps.setString(15, l != null ? l.specialisation  : null);

            AdminProfile a = u.getAdminProfile();
            ps.setInt(16, a != null && a.superAdmin ? 1 : 0);

            ps.executeUpdate();
        } catch (SQLException e) {
            System.err.println("[Auth] Could not persist session: " + e.getMessage());
        }
    }

    private AuthUser loadCachedSession() {
        String sql = """
            SELECT user_id, name, email, role, token, avatar, bio, is_active,
                   student_id, programme, year_of_study, reputation,
                   staff_id, department, specialisation, super_admin
            FROM session_cache WHERE id = 1
            """;
        try (Connection c = cache.connect();
             PreparedStatement ps = c.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            if (rs.next()) {
                String role = rs.getString("role");
                AuthUser u = new AuthUser(
                        rs.getInt("user_id"),
                        rs.getString("name"),
                        rs.getString("email"),
                        role,
                        rs.getString("token"),
                        rs.getString("avatar"),
                        rs.getString("bio"),
                        rs.getInt("is_active") == 1
                );
                if ("member".equalsIgnoreCase(role)) {
                    u.setMemberProfile(new MemberProfile(
                            rs.getString("student_id"),
                            rs.getString("programme"),
                            rs.getInt("year_of_study"),
                            rs.getInt("reputation")
                    ));
                } else if ("lecturer".equalsIgnoreCase(role)) {
                    u.setLecturerProfile(new LecturerProfile(
                            rs.getString("staff_id"),
                            rs.getString("department"),
                            rs.getString("specialisation")
                    ));
                } else if ("admin".equalsIgnoreCase(role)) {
                    u.setAdminProfile(new AdminProfile(rs.getInt("super_admin") == 1));
                }
                return u;
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
