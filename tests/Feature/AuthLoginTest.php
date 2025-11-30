<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('valid login returns token', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'refresh_token',
                'token_type',
                'expires_at',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                ],
            ],
        ]);
});

test('invalid credentials rejection', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct_password'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'details',
            ],
        ]);
});

test('login records metadata', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
        'is_active' => true,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ], ['User-Agent' => 'TestAgent']);

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_device)->toBe('TestAgent');
});

test('logout invalidates token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logged out successfully',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

test('logout requires authentication', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});
