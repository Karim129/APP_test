<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Assign a role to a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'role_name' => 'required|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data',
                    'details' => $validator->errors(),
                    'timestamp' => now()->toIso8601String()
                ]
            ], 400);
        }

        try {
            $adminId = $request->user()->id;
            $this->roleService->assignRole(
                $request->input('user_id'),
                $request->input('role_name'),
                $adminId
            );

            return response()->json([
                'message' => 'Role assigned successfully',
                'data' => [
                    'user_id' => $request->input('user_id'),
                    'role_name' => $request->input('role_name'),
                    'permissions' => $this->roleService->getUserPermissions($request->input('user_id'))
                ]
            ], 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 ? 403 : 500;
            return response()->json([
                'error' => [
                    'code' => $statusCode === 403 ? 'UNAUTHORIZED' : 'SERVER_ERROR',
                    'message' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String()
                ]
            ], $statusCode);
        }
    }

    /**
     * Remove a role from a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'role_name' => 'required|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid input data',
                    'details' => $validator->errors(),
                    'timestamp' => now()->toIso8601String()
                ]
            ], 400);
        }

        try {
            $adminId = $request->user()->id;
            $this->roleService->removeRole(
                $request->input('user_id'),
                $request->input('role_name'),
                $adminId
            );

            return response()->json([
                'message' => 'Role removed successfully',
                'data' => [
                    'user_id' => $request->input('user_id'),
                    'role_name' => $request->input('role_name'),
                    'permissions' => $this->roleService->getUserPermissions($request->input('user_id'))
                ]
            ], 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 ? 403 : 500;
            return response()->json([
                'error' => [
                    'code' => $statusCode === 403 ? 'UNAUTHORIZED' : 'SERVER_ERROR',
                    'message' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String()
                ]
            ], $statusCode);
        }
    }

    /**
     * Get user permissions.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserPermissions(Request $request, int $userId): JsonResponse
    {
        try {
            $permissions = $this->roleService->getUserPermissions($userId);
            $roles = $this->roleService->getUserRoles($userId);

            return response()->json([
                'data' => [
                    'user_id' => $userId,
                    'roles' => $roles,
                    'permissions' => $permissions
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String()
                ]
            ], 500);
        }
    }
}
