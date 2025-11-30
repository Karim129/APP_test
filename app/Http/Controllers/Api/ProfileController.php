<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nickname' => $user->nickname,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'privacy_settings' => $user->privacy_settings,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], 200);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->updateProfile(
                $request->user(),
                $request->except(['current_password']),
                $request->current_password
            );

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nickname' => $user->nickname,
                    'phone' => $user->phone,
                    'bio' => $user->bio,
                    'avatar' => $user->avatar,
                    'updated_at' => $user->updated_at,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'PROFILE_UPDATE_FAILED',
                    'message' => 'Profile update failed',
                    'details' => $e->errors(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PROFILE_UPDATE_FAILED',
                    'message' => 'Profile update failed',
                ],
            ], 500);
        }
    }

    /**
     * Register the authenticated user as a seller.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAsSeller(Request $request): JsonResponse
    {
        try {
            $businessInfo = $request->only(['business_name', 'business_description', 'business_phone']);

            $user = $this->authService->registerAsSeller($request->user(), $businessInfo);

            return response()->json([
                'message' => 'Successfully registered as seller',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'SELLER_REGISTRATION_FAILED',
                    'message' => 'Seller registration failed',
                    'details' => $e->errors(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SELLER_REGISTRATION_FAILED',
                    'message' => 'Seller registration failed',
                ],
            ], 500);
        }
    }
}
