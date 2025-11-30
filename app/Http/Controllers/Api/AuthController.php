<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->all());

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
            ], 201);
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
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'User registration failed',
                ],
            ], 500);
        }
    }

    /**
     * Login a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Collect login metadata
            $metadata = [
                'ip_address' => $request->ip(),
                'device_info' => $request->userAgent(),
            ];

            $result = $this->authService->login(
                $request->email,
                $request->password,
                $metadata
            );

            return response()->json([
                'message' => 'Login successful',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'AUTHENTICATION_FAILED',
                    'message' => 'Authentication failed',
                    'details' => $e->errors(),
                ],
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'LOGIN_FAILED',
                    'message' => 'Login failed',
                ],
            ], 500);
        }
    }

    /**
     * Refresh access token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            $result = $this->authService->refreshToken($request->refresh_token);

            return response()->json([
                'message' => 'Token refreshed successfully',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'TOKEN_REFRESH_FAILED',
                    'message' => 'Token refresh failed',
                    'details' => $e->errors(),
                ],
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REFRESH_FAILED',
                    'message' => 'Token refresh failed',
                ],
            ], 500);
        }
    }

    /**
     * Logout the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return response()->json([
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'LOGOUT_FAILED',
                    'message' => 'Logout failed',
                ],
            ], 500);
        }
    }

    /**
     * Request a password reset token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestPasswordReset(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $token = $this->authService->requestPasswordReset($request->email);

            return response()->json([
                'message' => 'Password reset token sent successfully',
                'token' => $token, // In production, this would be sent via email
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'PASSWORD_RESET_REQUEST_FAILED',
                    'message' => 'Password reset request failed',
                    'details' => $e->errors(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PASSWORD_RESET_REQUEST_FAILED',
                    'message' => 'Password reset request failed',
                ],
            ], 500);
        }
    }

    /**
     * Reset password using token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $this->authService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            return response()->json([
                'message' => 'Password reset successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code' => 'PASSWORD_RESET_FAILED',
                    'message' => 'Password reset failed',
                    'details' => $e->errors(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PASSWORD_RESET_FAILED',
                    'message' => 'Password reset failed',
                ],
            ], 500);
        }
    }
}
