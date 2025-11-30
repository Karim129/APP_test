<?php

use App\Models\User;

test('user can register as seller', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/user/register-seller', [
            'business_name' => 'My Business',
            'business_description' => 'We sell outdoor gear',
            'business_phone' => '+1234567890',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('user.roles', fn($roles) => in_array('Seller', $roles));

    $user->refresh();
    expect($user->bio)->toContain('Business: My Business');
});

test('seller cannot register twice', function () {
    $user = User::factory()->create();
    $user->assignRole('Seller');
    $user->load('roles'); // Ensure roles are loaded

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/user/register-seller', [
            'business_name' => 'Another Business',
        ]);

    $response->assertStatus(400);
});

test('seller registration validates business info', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Try with invalid business name (too long)
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/user/register-seller', [
            'business_name' => str_repeat('A', 300), // Exceeds max length
        ]);

    $response->assertStatus(400);
});

test('seller role grants venue creation access', function () {
    $seller = User::factory()->create();
    $seller->assignRole('Seller');

    $token = $seller->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/venues', [
            'name' => 'Test Venue',
            'type' => 'Conference Room',
            'location' => 'NYC',
            'capacity' => 50,
            'price_per_hour' => 100,
        ]);

    $response->assertStatus(201);
});
