<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    protected UserManagementService $userManagementService;

    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

    /**
     * List all users with optional filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['role', 'is_active', 'search']);
            $users = $this->userManagementService->listUsers($filters);

            return response()->json([
                'users' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                        'roles' => $user->roles->pluck('name'),
                        'created_at' => $user->created_at,
                        'last_login_at' => $user->last_login_at,
                    ];
                }),
                'total' => $users->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'USER_LIST_FAILED',
                    'message' => 'Failed to retrieve user list',
                ],
            ], 500);
        }
    }

    /**
     * Search users by email or name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2',
            ]);

            $users = $this->userManagementService->searchUsers($request->q);

            return response()->json([
                'users' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                        'roles' => $user->roles->pluck('name'),
                    ];
                }),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $e->errors(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SEARCH_FAILED',
                    'message' => 'User search failed',
                ],
            ], 500);
        }
    }

    /**
     * Get user details by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->getUserById($id);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'avatar' => $user->avatar,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'last_login_at' => $user->last_login_at,
                    'last_login_ip' => $user->last_login_ip,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'User not found',
                    'details' => $e->errors(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'USER_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve user',
                ],
            ], 500);
        }
    }

    /**
     * Activate a user account.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->activateUser($id);

            return response()->json([
                'message' => 'User activated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'User not found',
                    'details' => $e->errors(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'ACTIVATION_FAILED',
                    'message' => 'Failed to activate user',
                ],
            ], 500);
        }
    }

    /**
     * Deactivate a user account.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $user = $this->userManagementService->deactivateUser($id);

            return response()->json([
                'message' => 'User deactivated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'User not found',
                    'details' => $e->errors(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DEACTIVATION_FAILED',
                    'message' => 'Failed to deactivate user',
                ],
            ], 500);
        }
    }
}
