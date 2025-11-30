<?php

use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('middleware blocks non-admin users', function () {
    $seller = User::factory()->create();
    $seller->assignRole('Seller');

    // Verify seller has Seller role
    expect($seller->hasRole('Seller'))->toBeTrue()
        ->and($seller->hasRole('Admin'))->toBeFalse();

    $token = $seller->createToken('test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])->postJson('/api/roles/assign', [
        'user_id' => 1,
        'role_name' => 'Seller'
    ]);

    // Should get 403
    expect($response->status())->toBe(403);
});

test('middleware allows admin users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    // Verify admin has Admin role
    expect($admin->hasRole('Admin'))->toBeTrue();

    $token = $admin->createToken('test')->plainTextToken;

    $targetUser = User::factory()->create();
    $targetUser->assignRole('Regular User');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])->postJson('/api/roles/assign', [
        'user_id' => $targetUser->id,
        'role_name' => 'Seller'
    ]);

    // Should get 200
    expect($response->status())->toBe(200);
});
