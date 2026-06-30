<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authentication Interface
 * Defines the contract for user authentication operations
 */
interface IAuthentication
{
    /**
     * Register a new user with Eloquent ORM
     *
     * @param array $userData User registration data
     * @param bool $acceptedRules Whether forum rules were accepted
     * @return User
     * @throws \Exception if rules not accepted or validation fails
     */
    public function register(array $userData, bool $acceptedRules): User;

    /**
     * Authenticate and login a user
     *
     * @param array $credentials Login credentials (email/password)
     * @param bool $remember Remember me option
     * @return bool True if authentication successful
     */
    public function login(array $credentials, bool $remember = false): bool;

    /**
     * Logout the authenticated user
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request): void;

    /**
     * Initiate password reset process
     *
     * @param string $email User's email address
     * @return string Password reset token status
     */
    public function resetPassword(string $email): string;

    /**
     * Update password using reset token
     *
     * @param array $credentials Reset token, email, new password
     * @return bool True if password reset successful
     */
    public function updatePasswordWithToken(array $credentials): bool;

    /**
     * Check if user has accepted forum rules
     *
     * @param int $userId
     * @return bool
     */
    public function hasAcceptedForumRules(int $userId): bool;
}
