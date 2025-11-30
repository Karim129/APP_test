<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleService
{
    /**
     * Assign a role to a user (admin only).
     *
     * @param int $userId
     * @param string $roleName
     * @param int $adminId
     * @return bool
     * @throws \Exception
     */
    public function assignRole(int $userId, string $roleName, int $adminId): bool
    {
        // Check if the admin has permission
        $admin = User::findOrFail($adminId);
        if (!$admin->hasRole('Admin')) {
            throw new \Exception('Unauthorized: Only admins can assign roles', 403);
        }

        $user = User::findOrFail($userId);
        $role = Role::where('name', $roleName)->firstOrFail();

        // Check if user already has this role
        if ($user->hasRole($roleName)) {
            return true; // Already has the role
        }

        DB::beginTransaction();
        try {
            // Assign the role
            $user->roles()->attach($role->id);

            // Log the role assignment
            $this->logRoleChange('role_assigned', $userId, $roleName, $adminId);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role assignment failed', [
                'user_id' => $userId,
                'role_name' => $roleName,
                'admin_id' => $adminId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a role from a user (admin only).
     *
     * @param int $userId
     * @param string $roleName
     * @param int $adminId
     * @return bool
     * @throws \Exception
     */
    public function removeRole(int $userId, string $roleName, int $adminId): bool
    {
        // Check if the admin has permission
        $admin = User::findOrFail($adminId);
        if (!$admin->hasRole('Admin')) {
            throw new \Exception('Unauthorized: Only admins can remove roles', 403);
        }

        $user = User::findOrFail($userId);
        $role = Role::where('name', $roleName)->firstOrFail();

        // Check if user has this role
        if (!$user->hasRole($roleName)) {
            return true; // Already doesn't have the role
        }

        DB::beginTransaction();
        try {
            // Remove the role
            $user->roles()->detach($role->id);

            // Log the role removal
            $this->logRoleChange('role_removed', $userId, $roleName, $adminId);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role removal failed', [
                'user_id' => $userId,
                'role_name' => $roleName,
                'admin_id' => $adminId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all permissions for a user (aggregated from all roles).
     *
     * @param int $userId
     * @return array
     */
    public function getUserPermissions(int $userId): array
    {
        $user = User::with('roles')->findOrFail($userId);

        // Aggregate permissions from all roles
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions ?? [];
        })->unique()->values()->toArray();

        return $permissions;
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param int $userId
     * @param string $permission
     * @return bool
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }

    /**
     * Get all roles for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        $user = User::with('roles')->findOrFail($userId);
        return $user->roles->pluck('name')->toArray();
    }

    /**
     * Log role changes for audit trail.
     *
     * @param string $action
     * @param int $userId
     * @param string $roleName
     * @param int $adminId
     * @return void
     */
    protected function logRoleChange(string $action, int $userId, string $roleName, int $adminId): void
    {
        // Create audit log entry
        AuditLog::create([
            'type' => 'role_management',
            'user_id' => $adminId,
            'operation' => $action,
            'data' => json_encode([
                'target_user_id' => $userId,
                'role_name' => $roleName,
                'admin_id' => $adminId,
                'timestamp' => now()->toIso8601String()
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'severity' => 'info'
        ]);

        Log::info("Role change: {$action}", [
            'target_user_id' => $userId,
            'role_name' => $roleName,
            'admin_id' => $adminId
        ]);
    }
}
