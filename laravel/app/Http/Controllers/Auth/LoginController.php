<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Contracts\IAuthentication;
use App\Services\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected IAuthentication $authService;
    protected SessionManager $sessionManager;

    public function __construct(IAuthentication $authService, SessionManager $sessionManager)
    {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            // Attempt login using AuthenticationService
            $success = $this->authService->login(
                $request->only('email', 'password'),
                $request->boolean('remember')
            );

            if (!$success) {
                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }

            // Store success message
            $this->sessionManager->flash('success', 'Welcome back!');

            // Redirect based on role
            return $this->redirectBasedOnRole();

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput($request->only('email'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput($request->only('email'));
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request);
        
        $this->sessionManager->flash('success', 'You have been logged out successfully.');
        
        return redirect()->route('login');
    }

    // ── API endpoints for Java GUI (SDD §3.1 integration) ────────────

    /** POST /api/login — stateless, returns Sanctum token + user payload. */
    public function apiLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Use Auth::attempt directly — avoids session dependency on API routes
        if (!\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user  = \Illuminate\Support\Facades\Auth::user();
        $token = $user->createToken('java-gui')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }

    /** POST /api/logout — revokes the current Sanctum token. */
    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isLecturer()) {
            return redirect()->route('lecturer.dashboard');
        } else {
            return redirect()->route('dashboard');
        }
    }
}
