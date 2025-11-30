<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;

// Public authentication routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::post('/auth/password/reset-request', [AuthController::class, 'requestPasswordReset']);
Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user/profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::put('/user/profile', [App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::post('/user/register-seller', [App\Http\Controllers\Api\ProfileController::class, 'registerAsSeller']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Role management routes (admin only)
    Route::middleware('role:Admin')->group(function () {
        Route::post('/roles/assign', [RoleController::class, 'assignRole']);
        Route::post('/roles/remove', [RoleController::class, 'removeRole']);

        // User management routes
        Route::get('/admin/users', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'index']);
        Route::get('/admin/users/search', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'search']);
        Route::get('/admin/users/{id}', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'show']);
        Route::put('/admin/users/{id}/activate', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'activate']);
        Route::put('/admin/users/{id}/deactivate', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'deactivate']);
    });

    Route::get('/users/{userId}/permissions', [RoleController::class, 'getUserPermissions']);

    // Location Tracking
    Route::post('/locations', [App\Http\Controllers\Api\LocationController::class, 'record']);
    Route::get('/locations/history', [App\Http\Controllers\Api\LocationController::class, 'history']);
    Route::get('/locations/latest', [App\Http\Controllers\Api\LocationController::class, 'latest']);

    // Rescue Requests
    Route::post('/rescue/request', [App\Http\Controllers\Api\RescueController::class, 'create']);
    Route::get('/rescue/pending', [App\Http\Controllers\Api\RescueController::class, 'pending']);
    Route::put('/rescue/{id}/assign', [App\Http\Controllers\Api\RescueController::class, 'assign']);
    Route::put('/rescue/{id}/status', [App\Http\Controllers\Api\RescueController::class, 'updateStatus']);
    Route::get('/rescue/my-assignments', [App\Http\Controllers\Api\RescueController::class, 'myAssignments']);

    // Teams
    Route::post('/teams', [App\Http\Controllers\Api\TeamController::class, 'create']);
    Route::post('/teams/{id}/members', [App\Http\Controllers\Api\TeamController::class, 'addMember']);
    Route::delete('/teams/{id}/members', [App\Http\Controllers\Api\TeamController::class, 'removeMember']);
    Route::get('/teams/{id}/members', [App\Http\Controllers\Api\TeamController::class, 'members']);
    Route::delete('/teams/{id}', [App\Http\Controllers\Api\TeamController::class, 'delete']);

    // Groups
    Route::post('/groups', [App\Http\Controllers\Api\GroupController::class, 'create']);
    Route::post('/groups/join', [App\Http\Controllers\Api\GroupController::class, 'join']);
    Route::delete('/groups/{id}/leave', [App\Http\Controllers\Api\GroupController::class, 'leave']);

    // Events
    Route::post('/events', [App\Http\Controllers\Api\EventController::class, 'create']);
    Route::get('/groups/{groupId}/events', [App\Http\Controllers\Api\EventController::class, 'groupEvents']);

    // Venues & Reservations
    Route::post('/venues', [App\Http\Controllers\Api\VenueController::class, 'create']);
    Route::get('/venues/search', [App\Http\Controllers\Api\VenueController::class, 'search']);
    Route::post('/venues/{id}/reserve', [App\Http\Controllers\Api\VenueController::class, 'reserve']);
    Route::delete('/reservations/{id}', [App\Http\Controllers\Api\VenueController::class, 'cancelReservation']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});
