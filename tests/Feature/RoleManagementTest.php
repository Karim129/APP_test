<?php

use App\Models\User;
use App\Models\Role;
use App\Services\RoleService;

beforeEach(function () {
    // Seed roles before each test
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    $this->roleService = app(RoleService::class);
});

// Feature: user-authentication-roles, Property 18: Role assignment updates permissions
test('role assignment updates permissions', function () {
    // Create an admin user
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create a regular user
    $user = User::factory()->create();
    $user->assignRole('Regular User');

    // Get the seller role
    $sellerRole = Role::where('name', 'Seller')->first();
    expect($sellerRole)->not->toBeNull();

    // Get initial permissions
    $initialPermissions = $this->roleService->getUserPermissions($user->id);

    // Assign seller role
    $this->roleService->assignRole($user->id, 'Seller', $admin->id);

    // Get updated permissions
    $updatedPermissions = $this->roleService->getUserPermissions($user->id);

    // Verify that user now has seller permissions
    expect($updatedPermissions)->toBeArray()
        ->and(count($updatedPermissions))->toBeGreaterThan(count($initialPermissions));

    // Verify seller permissions are included
    $sellerPermissions = $sellerRole->permissions ?? [];
    foreach ($sellerPermissions as $permission) {
        expect($updatedPermissions)->toContain($permission);
    }

    // Verify user has the seller role
    $userRoles = $this->roleService->getUserRoles($user->id);
    expect($userRoles)->toContain('Seller');
})->repeat(100);

// Feature: user-authentication-roles, Property 19: Multiple roles aggregate permissions
test('multiple roles aggregate permissions', function () {
    // Create an admin user
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create a regular user
    $user = User::factory()->create();
    $user->assignRole('Regular User');

    // Get the seller and rescue team member roles
    $sellerRole = Role::where('name', 'Seller')->first();
    $rescueRole = Role::where('name', 'Rescue Team Member')->first();

    expect($sellerRole)->not->toBeNull();
    expect($rescueRole)->not->toBeNull();

    // Assign both seller and rescue team member roles
    $this->roleService->assignRole($user->id, 'Seller', $admin->id);
    $this->roleService->assignRole($user->id, 'Rescue Team Member', $admin->id);

    // Get aggregated permissions
    $permissions = $this->roleService->getUserPermissions($user->id);

    // Verify that user has permissions from all roles
    $sellerPermissions = $sellerRole->permissions ?? [];
    $rescuePermissions = $rescueRole->permissions ?? [];

    // Check that permissions from seller role are included
    foreach ($sellerPermissions as $permission) {
        expect($permissions)->toContain($permission);
    }

    // Check that permissions from rescue team member role are included
    foreach ($rescuePermissions as $permission) {
        expect($permissions)->toContain($permission);
    }

    // Verify user has all three roles
    $userRoles = $this->roleService->getUserRoles($user->id);
    expect($userRoles)->toContain('Regular User')
        ->and($userRoles)->toContain('Seller')
        ->and($userRoles)->toContain('Rescue Team Member');

    // Verify permissions are unique (no duplicates)
    expect(count($permissions))->toBe(count(array_unique($permissions)));
})->repeat(100);

// Feature: user-authentication-roles, Property 24: Permission-based access control
test('permission-based access control', function () {
    // Create a seller user (non-admin)
    $seller = User::factory()->create();
    $seller->assignRole('Seller');

    // Verify seller doesn't have Admin role
    expect($seller->fresh()->hasRole('Admin'))->toBeFalse();

    // Create target user
    $targetUser = User::factory()->create();
    $targetUser->assignRole('Regular User');

    // Create a Sanctum token for seller
    $sellerToken = $seller->createToken('test-token')->plainTextToken;

    // Test seller cannot access role assignment endpoint (should get 403)
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $sellerToken,
        'Accept' => 'application/json',
    ])->postJson('/api/roles/assign', [
        'user_id' => $targetUser->id,
        'role_name' => 'Rescue Team Member'
    ]);

    // Seller should get 403 from middleware
    expect($response->status())->toBe(403);
})->repeat(100);
