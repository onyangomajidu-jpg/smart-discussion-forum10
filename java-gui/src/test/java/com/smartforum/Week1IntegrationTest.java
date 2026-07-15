package com.smartforum;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthException;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import org.junit.jupiter.api.*;

import java.io.IOException;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Week 1 end-to-end integration test (Days 6–7).
 *
 * Validates the full chain: Java login → API token → dashboard stats.
 *
 * Prerequisites (run before executing):
 *   1. Laravel server running at http://localhost:8000
 *   2. A seeded user: email=test@example.com / password=password123
 *
 * Skip gracefully when the server is unreachable so CI stays green.
 */
@TestMethodOrder(MethodOrderer.OrderAnnotation.class)
class Week1IntegrationTest {

    private static final String BASE_URL  = "http://localhost:8000/api";
    private static final String TEST_EMAIL    = "test@example.com";
    private static final String TEST_PASSWORD = "password123";
    private static final String BAD_PASSWORD  = "wrongpassword";

    private static ApiClient          api;
    private static AuthService        authService;
    private static LocalCacheDatabase cache;
    private static final ObjectMapper mapper = new ObjectMapper();

    /** Shared token obtained in the login test and reused by later tests. */
    private static String sharedToken;

    // ── Setup / teardown ──────────────────────────────────────────────────

    @BeforeAll
    static void setUp() {
        System.setProperty("api.baseUrl",   BASE_URL);
        System.setProperty("cache.db.path", ":memory:");

        api   = new ApiClient();
        cache = LocalCacheDatabase.getInstance();
        cache.initialise();
        authService = new AuthService(api, cache);
    }

    @AfterAll
    static void tearDown() {
        // Best-effort logout so the token is revoked on the server
        if (authService.isLoggedIn()) {
            authService.logout();
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────

    /** Returns true when the Laravel server is reachable. */
    private static boolean serverReachable() {
        return api.isOnline();
    }

    // ── Test 1: Ping ──────────────────────────────────────────────────────

    @Test
    @Order(1)
    @DisplayName("T1 – /api/ping returns {status:ok}")
    void pingEndpointReturnsOk() throws IOException {
        assumeServerReachable();

        String body = api.get("/ping");
        JsonNode node = mapper.readTree(body);

        assertEquals("ok", node.path("status").asText(),
            "Ping should return {status: ok}");
    }

    // ── Test 2: API login with valid credentials ──────────────────────────

    @Test
    @Order(2)
    @DisplayName("T2 – API login returns token + user payload")
    void apiLoginReturnsTokenAndUser() throws Exception {
        assumeServerReachable();

        AuthUser user = authService.login(TEST_EMAIL, TEST_PASSWORD);

        assertNotNull(user,                        "AuthUser must not be null");
        assertNotNull(user.getToken(),             "Token must not be null");
        assertFalse(user.getToken().isBlank(),     "Token must not be blank");
        assertEquals(TEST_EMAIL, user.getEmail(),  "Email must match");
        assertNotNull(user.getRole(),              "Role must be present");
        assertTrue(user.getUserId() > 0,           "User ID must be positive");

        sharedToken = user.getToken();
        System.out.println("[T2] Logged in as: " + user);
    }

    // ── Test 3: Invalid credentials are rejected ──────────────────────────

    @Test
    @Order(3)
    @DisplayName("T3 – Invalid credentials throw AuthException")
    void invalidCredentialsThrowAuthException() {
        assumeServerReachable();

        // Create a fresh service so it doesn't reuse the cached session
        AuthService fresh = new AuthService(new ApiClient(), cache);

        assertThrows(AuthException.class,
            () -> fresh.login(TEST_EMAIL, BAD_PASSWORD),
            "Wrong password must throw AuthException");
    }

    // ── Test 4: Dashboard stats endpoint ─────────────────────────────────

    @Test
    @Order(4)
    @DisplayName("T4 – /api/dashboard returns required stats fields")
    void dashboardReturnsRequiredFields() throws IOException {
        assumeServerReachable();
        assertNotNull(sharedToken, "Run T2 first to obtain a token");

        api.setToken(sharedToken);
        String body = api.get("/dashboard");
        JsonNode stats = mapper.readTree(body).path("stats");

        assertFalse(stats.isMissingNode(), "Response must contain 'stats'");

        String[] required = {
            "topicsParticipated", "totalPosts", "quizAttempts",
            "availableQuizzes", "avgScore", "recentTopics", "recentAttempts"
        };
        for (String field : required) {
            assertFalse(stats.path(field).isMissingNode(),
                "Missing field in stats: " + field);
        }

        // Numeric fields must be non-negative integers
        assertTrue(stats.path("topicsParticipated").asInt(-1) >= 0);
        assertTrue(stats.path("totalPosts").asInt(-1)         >= 0);
        assertTrue(stats.path("quizAttempts").asInt(-1)       >= 0);
        assertTrue(stats.path("availableQuizzes").asInt(-1)   >= 0);

        // recentTopics and recentAttempts must be arrays
        assertTrue(stats.path("recentTopics").isArray(),   "recentTopics must be array");
        assertTrue(stats.path("recentAttempts").isArray(), "recentAttempts must be array");

        System.out.println("[T4] Stats: topics=" + stats.path("topicsParticipated").asInt()
            + " posts=" + stats.path("totalPosts").asInt()
            + " attempts=" + stats.path("quizAttempts").asInt());
    }

    // ── Test 5: Dashboard requires auth ──────────────────────────────────

    @Test
    @Order(5)
    @DisplayName("T5 – /api/dashboard without token returns HTTP 401")
    void dashboardRequiresAuth() {
        assumeServerReachable();

        ApiClient unauthApi = new ApiClient();
        // No token set — expect IOException wrapping HTTP 401
        IOException ex = assertThrows(IOException.class,
            () -> unauthApi.get("/dashboard"),
            "Unauthenticated request must throw IOException (HTTP 401)");

        assertTrue(ex.getMessage().contains("401"),
            "Exception message must mention 401, got: " + ex.getMessage());
    }

    // ── Test 6: Session persisted to local cache ──────────────────────────

    @Test
    @Order(6)
    @DisplayName("T6 – Successful login persists session to SQLite cache")
    void loginPersistsSessionToCache() throws Exception {
        assumeServerReachable();
        assertNotNull(sharedToken, "Run T2 first");

        // Re-login to ensure cache is fresh
        authService.login(TEST_EMAIL, TEST_PASSWORD);

        // Verify session_cache row exists
        try (var conn = cache.connect();
             var ps   = conn.prepareStatement(
                 "SELECT email, token FROM session_cache WHERE id = 1");
             var rs   = ps.executeQuery()) {

            assertTrue(rs.next(), "session_cache must have a row after login");
            assertEquals(TEST_EMAIL, rs.getString("email"));
            assertFalse(rs.getString("token").isBlank());
        }
    }

    // ── Test 7: Logout revokes token ──────────────────────────────────────

    @Test
    @Order(7)
    @DisplayName("T7 – Logout revokes token; subsequent dashboard call returns 401")
    void logoutRevokesToken() throws Exception {
        assumeServerReachable();

        // Ensure we have a fresh token
        AuthUser user = authService.login(TEST_EMAIL, TEST_PASSWORD);
        String token  = user.getToken();

        // Confirm token works
        api.setToken(token);
        assertDoesNotThrow(() -> api.get("/dashboard"), "Token should be valid before logout");

        // Logout
        authService.logout();
        assertFalse(authService.isLoggedIn(), "isLoggedIn must be false after logout");

        // Token should now be revoked
        ApiClient revokedApi = new ApiClient();
        revokedApi.setToken(token);
        IOException ex = assertThrows(IOException.class,
            () -> revokedApi.get("/dashboard"),
            "Revoked token must return 401");

        assertTrue(ex.getMessage().contains("401"),
            "Expected 401 after logout, got: " + ex.getMessage());
    }

    // ── Test 8: Offline fallback uses cached session ──────────────────────

    @Test
    @Order(8)
    @DisplayName("T8 – Offline fallback returns cached session for matching email")
    void offlineFallbackReturnsCachedSession() throws Exception {
        assumeServerReachable();

        // Seed the cache with a known session first
        authService.login(TEST_EMAIL, TEST_PASSWORD);

        // Point to a non-routable address so the connection times out immediately
        ApiClient offlineApi = new ApiClient() {
            @Override
            public String post(String endpoint, java.util.Map<String, Object> body) throws java.io.IOException {
                throw new java.io.IOException("Simulated network failure");
            }
            @Override
            public boolean isOnline() { return false; }
        };
        AuthService offlineAuth = new AuthService(offlineApi, cache);

        AuthUser cached = offlineAuth.login(TEST_EMAIL, "any-password-ignored-offline");

        assertNotNull(cached,                       "Cached session must be returned offline");
        assertEquals(TEST_EMAIL, cached.getEmail(), "Cached email must match");
    }

    // ── Helper ────────────────────────────────────────────────────────────

    private static void assumeServerReachable() {
        org.junit.jupiter.api.Assumptions.assumeTrue(
            serverReachable(),
            "Skipping — Laravel server not reachable at " + BASE_URL
        );
    }
}
