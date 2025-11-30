<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class UserManagementService
{
    /**
     * Get all users with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function listUsers(array $filters = []): Collection
    {
        $query = User::with('roles');

        // Filter by role
        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by search term (name or email)
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->get();
    }

    /**
     * Search users by email or name.
     *
     * @param string $searchTerm
     * @return Collection
     */
    public function searchUsers(string $searchTerm): Collection
    {
        return User::with('roles')
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orWhere('email', 'like', '%' . $searchTerm . '%')
            ->get();
    }

    /**
     * Activate a user account.
     *
     * @param int $userId
     * @return User
     * @throws ValidationException
     */
    public function activateUser(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.'],
            ]);
        }

        $user->is_active = true;
        $user->save();

        return $user;
    }

    /**
     * Deactivate a user account.
     *
     * @param int $userId
     * @return User
     * @throws ValidationException
     */
    public function deactivateUser(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.'],
            ]);
        }

        $user->is_active = false;
        $user->save();

        // Invalidate all tokens when deactivating
        $user->tokens()->delete();

        return $user;
    }

    /**
     * Get user details by ID.
     *
     * @param int $userId
     * @return User
     * @throws ValidationException
     */
    public function getUserById(int $userId): User
    {
        $user = User::with('roles')->find($userId);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['User not found.'],
            ]);
        }

        return $user;
    }
}
