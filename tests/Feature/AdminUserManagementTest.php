<?php

use App\Models\User;

test('admin can list all users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    // Create regular users
    User::factory()->count(5)->create();

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/admin/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'users',
            'total',
        ]);

    expect($response->json('total'))->toBeGreaterThan(5);
});

test('admin can search users by email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create(['email' => 'john.doe@example.com']);

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/admin/users/search?q=john.doe');

    $response->assertStatus(200);

    $users = $response->json('users');
    expect(count($users))->toBeGreaterThan(0);
});

test('admin can activate user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create(['is_active' => false]);

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/admin/users/{$user->id}/activate");

    $response->assertStatus(200);

    $user->refresh();
    expect($user->is_active)->toBeTrue();
});

test('admin can deactivate user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create(['is_active' => true]);
    $userToken = $user->createToken('test')->plainTextToken;

    $adminToken = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
        ->putJson("/api/admin/users/{$user->id}/deactivate");

    $response->assertStatus(200);

    $user->refresh();
    expect($user->is_active)->toBeFalse()
        ->and($user->tokens()->count())->toBe(0); // Tokens should be invalidated
});

test('non-admin cannot access user management', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('admin can filter users by role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $seller = User::factory()->create();
    $seller->assignRole('Seller');

    User::factory()->count(3)->create(); // Regular users

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/admin/users?role=Seller');

    $response->assertStatus(200);

    $users = $response->json('users');
    expect(count($users))->toBe(1);
});
