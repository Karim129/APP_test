<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

test('password reset token generation', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/password/reset-request', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
        ]);

    // Verify token was stored in database
    $tokenRecord = DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->first();

    expect($tokenRecord)->not->toBeNull();
});

test('password reset round-trip', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old_password'),
    ]);

    // Request reset
    $response = $this->postJson('/api/auth/password/reset-request', [
        'email' => $user->email,
    ]);

    $token = $response->json('token');

    // Reset password
    $response = $this->postJson('/api/auth/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ]);

    $response->assertStatus(200);

    // Verify new password works
    $user->refresh();
    expect(Hash::check('new_password123', $user->password))->toBeTrue();
});

test('password reset invalidates sessions', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old_password'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // Request reset
    $resetResponse = $this->postJson('/api/auth/password/reset-request', [
        'email' => $user->email,
    ]);

    $resetToken = $resetResponse->json('token');

    // Reset password
    $this->postJson('/api/auth/password/reset', [
        'email' => $user->email,
        'token' => $resetToken,
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ]);

    // Old token should be invalid
    expect($user->tokens()->count())->toBe(0);
});

test('reset token single-use', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/password/reset-request', [
        'email' => $user->email,
    ]);

    $token = $response->json('token');

    // First use
    $this->postJson('/api/auth/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ])->assertStatus(200);

    // Second use should fail
    $this->postJson('/api/auth/password/reset', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'another_password',
        'password_confirmation' => 'another_password',
    ])->assertStatus(400);
});
