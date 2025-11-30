<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('profile update round-trip', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'bio' => 'Original Bio',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'bio' => 'Updated Bio',
        ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->bio)->toBe('Updated Bio');
});

test('sensitive update requires password', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password' => Hash::make('password123'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    // Try to update email without password
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/user/profile', [
            'email' => 'new@example.com',
        ]);

    $response->assertStatus(400)
        ->assertJsonPath('error.details.current_password', ['Current password is required for this update.']);

    // Update with correct password
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/user/profile', [
            'email' => 'new@example.com',
            'current_password' => 'password123',
        ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->email)->toBe('new@example.com');
});

test('profile update timestamps', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $originalUpdatedAt = $user->updated_at;

    // Wait a moment to ensure timestamp changes
    sleep(1);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/user/profile', [
            'name' => 'New Name',
        ]);

    $user->refresh();
    expect($user->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
});
