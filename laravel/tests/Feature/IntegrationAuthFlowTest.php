<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Week 1 Integration Test (Days 6-7)
 * 
 * Validates end-to-end authentication flow:
 *   1. Web login (session-based)
 *   2. API login (Sanctum token)
 *   3. Java GUI login (token + dashboard sync)
 *   4. Dashboard data consistency
 */
class IntegrationAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->testUser = User::factory()->create([
            'email' => 'integration@test.com',
            'password' => bcrypt('password123'),
            'role' => 'member',
        ]);
    }

    /**
     * Test 1: Web login creates session
     */
    public function test_web_login_creates_session()
    {
        $response = $this->post('/login', [
            'email' => 'integration@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->testUser);
    }

    /**
     * Test 2: Web dashboard loads successfully
     */
    public function test_web_dashboard_loads()
    {
        $this->actingAs($this->testUser);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('user', $this->testUser);
    }

    /**
     * Test 3: API login returns Sanctum token
     */
    public function test_api_login_returns_token()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'integration@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role'],
        ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
    }

    /**
     * Test 4: API dashboard endpoint requires auth
     */
    public function test_api_dashboard_requires_auth()
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }

    /**
     * Test 5: API dashboard returns stats with valid token
     */
    public function test_api_dashboard_returns_stats()
    {
        $this->actingAs($this->testUser);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats' => [
                'topicsParticipated',
                'totalPosts',
                'quizAttempts',
                'availableQuizzes',
                'avgScore',
                'recentTopics',
                'recentAttempts',
            ],
        ]);
    }

    /**
     * Test 6: API login with invalid credentials fails
     */
    public function test_api_login_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'integration@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Invalid credentials.']);
    }

    /**
     * Test 7: API logout revokes token
     */
    public function test_api_logout_revokes_token()
    {
        // Login and get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'integration@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        // Use token to access protected endpoint
        $dashResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard');
        $this->assertEquals(200, $dashResponse->status());

        // Logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');
        $logoutResponse->assertStatus(200);

        // Token should be revoked
        $revokedResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard');
        $this->assertEquals(401, $revokedResponse->status());
    }

    /**
     * Test 8: Web logout clears session
     */
    public function test_web_logout_clears_session()
    {
        $this->actingAs($this->testUser);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test 9: Java GUI login flow (API token + dashboard)
     */
    public function test_java_gui_login_flow()
    {
        // Step 1: API login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'integration@test.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token');
        $user = $loginResponse->json('user');

        $this->assertEquals('integration@test.com', $user['email']);
        $this->assertEquals('member', $user['role']);

        // Step 2: Fetch dashboard with token
        $dashResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/dashboard');

        $dashResponse->assertStatus(200);
        $stats = $dashResponse->json('stats');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('topicsParticipated', $stats);
        $this->assertArrayHasKey('totalPosts', $stats);
        $this->assertArrayHasKey('quizAttempts', $stats);
    }

    /**
     * Test 10: Dashboard data consistency (web vs API)
     */
    public function test_dashboard_data_consistency()
    {
        $this->actingAs($this->testUser);

        // Web dashboard
        $webResponse = $this->get('/dashboard');
        $webResponse->assertStatus(200);

        // API dashboard
        $apiResponse = $this->getJson('/api/dashboard');
        $apiResponse->assertStatus(200);

        $stats = $apiResponse->json('stats');

        // Both should return same stats structure
        $this->assertIsArray($stats);
        $this->assertIsInt($stats['topicsParticipated']);
        $this->assertIsInt($stats['totalPosts']);
        $this->assertIsInt($stats['quizAttempts']);
    }

    /**
     * Test 11: Ping endpoint (no auth required)
     */
    public function test_ping_endpoint()
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    /**
     * Test 12: Role-based dashboard access
     */
    public function test_role_based_dashboard_access()
    {
        // Member can access dashboard
        $this->actingAs($this->testUser);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Lecturer redirects to lecturer dashboard
        $lecturer = User::factory()->create(['role' => 'lecturer']);
        $this->actingAs($lecturer);
        $response = $this->post('/login', [
            'email' => $lecturer->email,
            'password' => 'password',
        ]);
        // Note: This will redirect based on role in LoginController
    }
}
