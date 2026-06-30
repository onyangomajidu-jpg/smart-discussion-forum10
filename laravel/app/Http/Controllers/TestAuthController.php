<?php

namespace App\Http\Controllers;

use App\Contracts\IAuthentication;
use App\Services\SessionManager;
use Illuminate\Http\Request;

/**
 * Test Controller - Demonstrates authentication system usage
 * 
 * This controller shows how to use the IAuthentication interface
 * and SessionManager in your application.
 */
class TestAuthController extends Controller
{
    protected IAuthentication $authService;
    protected SessionManager $sessionManager;

    public function __construct(IAuthentication $authService, SessionManager $sessionManager)
    {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Display test dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        return response()->json([
            'message' => 'Authentication working!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_member' => $user->isMember(),
                'is_lecturer' => $user->isLecturer(),
                'is_admin' => $user->isAdmin(),
            ],
            'session_id' => $this->sessionManager->getCurrentSessionId(),
            'has_accepted_rules' => $this->authService->hasAcceptedForumRules($user->id),
        ]);
    }

    /**
     * Test programmatic registration
     */
    public function testRegister()
    {
        try {
            $user = $this->authService->register([
                'name' => 'Test User ' . rand(1000, 9999),
                'email' => 'test' . rand(1000, 9999) . '@example.com',
                'password' => 'Password123!',
                'role' => 'member',
                'student_id' => 'STU' . rand(100, 999),
                'programme' => 'Test Programme',
                'year_of_study' => 1,
            ], true);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test session management
     */
    public function testSession()
    {
        // Store test data
        $this->sessionManager->put('test_key', 'test_value');
        
        // Get sessions for current user
        $sessions = $this->sessionManager->getUserSessions(auth()->id());
        
        return response()->json([
            'current_session_id' => $this->sessionManager->getCurrentSessionId(),
            'test_value' => $this->sessionManager->get('test_key'),
            'total_sessions' => count($sessions),
            'session_info' => $this->sessionManager->getSessionInfo(
                $this->sessionManager->getCurrentSessionId()
            ),
        ]);
    }

    /**
     * Test role checking
     */
    public function testRoles()
    {
        $user = auth()->user();
        
        return response()->json([
            'user_role' => $user->role,
            'is_member' => $user->isMember(),
            'is_lecturer' => $user->isLecturer(),
            'is_admin' => $user->isAdmin(),
            'role_profile' => [
                'member' => $user->member,
                'lecturer' => $user->lecturer,
                'admin' => $user->admin,
            ],
        ]);
    }

    /**
     * Test middleware protection
     */
    public function memberOnly()
    {
        return response()->json([
            'message' => 'This is a member-only endpoint',
            'user' => auth()->user()->name,
            'role' => auth()->user()->role,
        ]);
    }

    public function lecturerOnly()
    {
        return response()->json([
            'message' => 'This is a lecturer-only endpoint',
            'user' => auth()->user()->name,
            'role' => auth()->user()->role,
        ]);
    }

    public function adminOnly()
    {
        return response()->json([
            'message' => 'This is an admin-only endpoint',
            'user' => auth()->user()->name,
            'role' => auth()->user()->role,
        ]);
    }
}
