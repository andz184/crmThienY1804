<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

trait ReusableAuthentication
{
    /**
     * Authenticate a user by email and password
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return \App\Models\User
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticateUser(string $email, string $password, bool $remember = false)
    {
        // Attempt to authenticate the user
        if (!Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            // Log the failed attempt
            Log::warning('Failed login attempt', [
                'email' => $email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Log the successful login
        Log::info('User logged in', [
            'id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip()
        ]);

        return $user;
    }

    /**
     * Generate an API token for a user
     *
     * @param \App\Models\User $user
     * @param string $deviceName
     * @return string The API token
     */
    public function generateApiToken(User $user, string $deviceName = 'API Token')
    {
        // Revoke old tokens for this device if they exist
        $user->tokens()->where('name', $deviceName)->delete();

        // Create a new token
        $token = $user->createToken($deviceName);

        return $token->plainTextToken;
    }

    /**
     * Verify user password
     *
     * @param \App\Models\User $user
     * @param string $password
     * @return bool
     */
    public function verifyUserPassword(User $user, string $password)
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Logout the current user
     *
     * @param bool $logoutFromAllDevices Whether to logout from all devices
     * @return void
     */
    public function logoutUser(bool $logoutFromAllDevices = false)
    {
        $user = Auth::user();

        if ($logoutFromAllDevices && $user) {
            // Revoke all tokens
            $user->tokens()->delete();
        } else {
            // Revoke the current token
            $user?->currentAccessToken()?->delete();
        }

        // Logout from session
        Auth::logout();

        // Invalidate and regenerate the session
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Update a user's password and regenerate the token
     *
     * @param \App\Models\User $user
     * @param string $newPassword
     * @param string|null $deviceName
     * @return string|null The new token or null if no device name was provided
     */
    public function updateUserPassword(User $user, string $newPassword, ?string $deviceName = null)
    {
        // Update the password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Generate a new token if device name is provided
        return $deviceName ? $this->generateApiToken($user, $deviceName) : null;
    }
}
