<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Contracts\IAuthentication;
use App\Services\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    protected IAuthentication $authService;
    protected SessionManager $sessionManager;

    public function __construct(IAuthentication $authService, SessionManager $sessionManager)
    {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $status = $this->authService->resetPassword($request->email);

            $this->sessionManager->flash('success', 'Password reset link sent to your email!');
            
            return redirect()->back();

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Unable to send password reset link.'])
                ->withInput();
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            $success = $this->authService->updatePasswordWithToken([
                'token' => $request->token,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if (!$success) {
                return redirect()->back()
                    ->withErrors(['email' => 'Invalid or expired reset token.'])
                    ->withInput($request->only('email'));
            }

            $this->sessionManager->flash('success', 'Password reset successfully! Please login with your new password.');
            
            return redirect()->route('login');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput($request->only('email'));
        }
    }
}
