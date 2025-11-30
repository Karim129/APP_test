<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthService
{
    /**
     * Register a new user with email and password.
     *
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function register(array $data): User
    {
        // Validate input data
        $validator = Validator::make($data, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        // Create the user
        $user = User::create($data);

        // Assign default "Regular User" role
        $regularUserRole = Role::where('name', 'Regular User')->first();
        if ($regularUserRole) {
            $user->roles()->attach($regularUserRole->id);
        }

        // Reload the user with roles
        $user->load('roles');

        return $user;
    }

    /**
     * Login a user with email and password.
     *
     * @param string $email
     * @param string $password
     * @param array $metadata Login metadata (ip_address, device_info)
     * @return array Contains access_token, refresh_token, user, and expires_at
     * @throws ValidationException
     */
    public function login(string $email, string $password, array $metadata = []): array
    {
        // Find user by email
        $user = User::where('email', $email)->first();

        // Check if user exists
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if account is locked
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $remainingMinutes = now()->diffInMinutes($user->locked_until);
            throw ValidationException::withMessages([
                'email' => ["Account is locked. Please try again in {$remainingMinutes} minutes."],
            ]);
        }

        // Check if account is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            // Increment failed login attempts
            $user->increment('failed_login_attempts');

            // Lock account after 5 failed attempts
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = now()->addMinutes(15);
                $user->save();

                throw ValidationException::withMessages([
                    'email' => ['Too many failed login attempts. Account locked for 15 minutes.'],
                ]);
            }

            $user->save();

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Reset failed login attempts on successful login
        $user->failed_login_attempts = 0;
        $user->locked_until = null;

        // Record login metadata
        $user->last_login_at = now();
        $user->last_login_ip = $metadata['ip_address'] ?? null;
        $user->last_login_device = $metadata['device_info'] ?? null;
        $user->save();

        // Load user relationships
        $user->load('roles');

        // Create access token with expiration (24 hours)
        $accessToken = $user->createToken('access_token', ['*'], now()->addHours(24));

        // Create refresh token with longer expiration (7 days)
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(7));

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ];
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @param string $refreshToken
     * @return array Contains new access_token and expires_at
     * @throws ValidationException
     */
    public function refreshToken(string $refreshToken): array
    {
        // Parse the token to get the token ID
        $tokenParts = explode('|', $refreshToken);
        if (count($tokenParts) !== 2) {
            throw ValidationException::withMessages([
                'token' => ['Invalid refresh token format.'],
            ]);
        }

        $tokenId = $tokenParts[0];

        // Find the token in the database
        $token = \Laravel\Sanctum\PersonalAccessToken::find($tokenId);

        if (!$token || !Hash::check($tokenParts[1], $token->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired refresh token.'],
            ]);
        }

        // Check if token has refresh ability
        if (!in_array('refresh', $token->abilities)) {
            throw ValidationException::withMessages([
                'token' => ['This token cannot be used for refresh.'],
            ]);
        }

        // Check if token is expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['Refresh token has expired. Please login again.'],
            ]);
        }

        // Get the user
        $user = $token->tokenable;

        // Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'token' => ['This account has been deactivated.'],
            ]);
        }

        // Load user relationships
        $user->load('roles');

        // Create new access token
        $newAccessToken = $user->createToken('access_token', ['*'], now()->addHours(24));

        return [
            'access_token' => $newAccessToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ];
    }

    /**
     * Logout the user (invalidate current token).
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        // Revoke the current access token
        $user->currentAccessToken()->delete();
    }

    /**
     * Request a password reset token.
     *
     * @param string $email
     * @return string The reset token
     * @throws ValidationException
     */
    public function requestPasswordReset(string $email): string
    {
        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $hashedToken = Hash::make($token);

        // Delete any existing tokens for this email
        \DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Store token in database (Laravel default schema: email, token, created_at)
        \DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        // In production, send email with reset link containing $token
        // For now, we just return the token for testing
        return $token;
    }

    /**
     * Reset password using a reset token.
     *
     * @param string $email
     * @param string $token
     * @param string $newPassword
     * @return void
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        // Find the most recent token for this email (within 1 hour)
        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired reset token.'],
            ]);
        }

        // Verify the token
        if (!Hash::check($token, $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid reset token.'],
            ]);
        }

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        // Update password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Delete the used token
        \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Invalidate all existing tokens/sessions for security
        $user->tokens()->delete();
    }

    /**
     * Update user profile.
     *
     * @param User $user
     * @param array $data
     * @param string|null $currentPassword Required for sensitive updates (email, password)
     * @return User
     * @throws ValidationException
     */
    public function updateProfile(User $user, array $data, ?string $currentPassword = null): User
    {
        // Check if sensitive fields are being updated
        $sensitiveFields = ['email', 'password'];
        $isSensitiveUpdate = !empty(array_intersect(array_keys($data), $sensitiveFields));

        // Require password confirmation for sensitive updates
        if ($isSensitiveUpdate) {
            if (!$currentPassword) {
                throw ValidationException::withMessages([
                    'current_password' => ['Current password is required for this update.'],
                ]);
            }

            if (!Hash::check($currentPassword, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }
        }

        // Validate input data
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'nickname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|string|max:255',
            'password' => 'sometimes|string|min:8',
            'privacy_settings' => 'sometimes|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);

            // Invalidate all tokens if password changed
            $user->tokens()->delete();
        }

        // Update user
        $user->update($data);
        $user->refresh();

        return $user;
    }

    /**
     * Register a user as a seller or add seller role to existing user.
     *
     * @param User $user
     * @param array $businessInfo Optional business information
     * @return User
     * @throws ValidationException
     */
    public function registerAsSeller(User $user, array $businessInfo = []): User
    {
        // Check if user already has Seller role
        if ($user->hasRole('Seller')) {
            throw ValidationException::withMessages([
                'role' => ['User is already registered as a seller.'],
            ]);
        }

        // Validate business info if provided
        if (!empty($businessInfo)) {
            $validator = Validator::make($businessInfo, [
                'business_name' => 'sometimes|string|max:255',
                'business_description' => 'sometimes|string|max:1000',
                'business_phone' => 'sometimes|string|max:20',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Store business info in user's bio or a separate field
            // For now, we'll append to bio
            if (isset($businessInfo['business_name'])) {
                $user->bio = ($user->bio ? $user->bio . "\n\n" : '') .
                    "Business: " . $businessInfo['business_name'];
                $user->save();
            }
        }

        // Assign Seller role
        $user->assignRole('Seller');
        $user->load('roles');

        return $user;
    }
}
