<?php

namespace App\Services;

use App\Contracts\IAuthentication;
use App\Models\User;
use App\Models\Member;
use App\Models\Lecturer;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthenticationService implements IAuthentication
{
    /**
     * Register a new user with Eloquent ORM
     */
    public function register(array $userData, bool $acceptedRules): User
    {
        // Gate: Enforce forum rules acceptance
        if (!$acceptedRules) {
            throw new \Exception('You must accept the forum rules before creating an account.');
        }

        DB::beginTransaction();
        try {
            // Create user with Eloquent ORM
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'] ?? 'member',
                'avatar' => $userData['avatar'] ?? null,
                'bio' => $userData['bio'] ?? null,
                'is_active' => true,
            ]);

            // Record forum rules acceptance
            DB::table('forum_rules_acceptances')->insert([
                'user_id' => $user->id,
                'accepted_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            // Create role-specific profile
            $this->createRoleProfile($user, $userData);

            DB::commit();
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create role-specific profile (Member, Lecturer, or Admin)
     */
    protected function createRoleProfile(User $user, array $userData): void
    {
        switch ($user->role) {
            case 'member':
                Member::create([
                    'user_id' => $user->id,
                    'student_id' => $userData['student_id'] ?? null,
                    'programme' => $userData['programme'] ?? null,
                    'year_of_study' => $userData['year_of_study'] ?? null,
                    'reputation' => 0,
                ]);
                break;

            case 'lecturer':
                Lecturer::create([
                    'user_id' => $user->id,
                    'staff_id' => $userData['staff_id'] ?? null,
                    'department' => $userData['department'] ?? null,
                    'specialisation' => $userData['specialisation'] ?? null,
                ]);
                break;

            case 'admin':
                Admin::create([
                    'user_id' => $user->id,
                    'super_admin' => $userData['super_admin'] ?? false,
                ]);
                break;
        }
    }

    /**
     * Authenticate and login a user
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        // Attempt authentication
        if (!Auth::attempt($credentials, $remember)) {
            return false;
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        // Check if user is banned
        if ($user->isBanned()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Your account has been banned.',
            ]);
        }

        // Regenerate session to prevent fixation attacks
        request()->session()->regenerate();

        return true;
    }

    /**
     * Logout the authenticated user
     */
    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Initiate password reset process
     */
    public function resetPassword(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Update password using reset token
     */
    public function updatePasswordWithToken(array $credentials): bool
    {
        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        return $status === Password::PASSWORD_RESET;
    }

    /**
     * Check if user has accepted forum rules
     */
    public function hasAcceptedForumRules(int $userId): bool
    {
        return DB::table('forum_rules_acceptances')
            ->where('user_id', $userId)
            ->exists();
    }
}
