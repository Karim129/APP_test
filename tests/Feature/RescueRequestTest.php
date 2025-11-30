<?php

use App\Models\User;
use App\Models\RescueRequest;

test('rescue request creation with location', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/rescue/request', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'description' => 'Need help urgently',
        ]);

    $response->assertStatus(201);

    $rescue = RescueRequest::first();
    expect($rescue->user_id)->toBe($user->id)
        ->and($rescue->status)->toBe('pending')
        ->and($rescue->latitude)->toBe('40.71280000')
        ->and($rescue->description)->toBe('Need help urgently');
});

test('rescue request assignment requires rescue team role', function () {
    $user = User::factory()->create();
    $rescuer = User::factory()->create();
    $rescuer->assignRole('Rescue Team');

    $rescue = RescueRequest::create([
        'user_id' => $user->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'status' => 'pending',
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/rescue/{$rescue->id}/assign", [
            'rescuer_id' => $rescuer->id,
        ]);

    $response->assertStatus(200);

    $rescue->refresh();
    expect($rescue->assigned_to)->toBe($rescuer->id)
        ->and($rescue->status)->toBe('assigned');
});

test('rescue status progression', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $rescue = RescueRequest::create([
        'user_id' => $user->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'status' => 'assigned',
    ]);

    // Update to in_progress
    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/rescue/{$rescue->id}/status", [
            'status' => 'in_progress',
        ]);

    $rescue->refresh();
    expect($rescue->status)->toBe('in_progress');

    // Update to resolved
    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/rescue/{$rescue->id}/status", [
            'status' => 'resolved',
        ]);

    $rescue->refresh();
    expect($rescue->status)->toBe('resolved')
        ->and($rescue->resolved_at)->not->toBeNull();
});

test('rescuer can view assigned requests', function () {
    $rescuer = User::factory()->create();
    $rescuer->assignRole('Rescue Team');

    // Create assigned rescue
    RescueRequest::create([
        'user_id' => User::factory()->create()->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'status' => 'assigned',
        'assigned_to' => $rescuer->id,
    ]);

    // Create unassigned rescue
    RescueRequest::create([
        'user_id' => User::factory()->create()->id,
        'latitude' => 41.0000,
        'longitude' => -75.0000,
        'status' => 'pending',
    ]);

    $token = $rescuer->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/rescue/my-assignments');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'assignments');
});

test('pending rescue requests visible to all', function () {
    $user = User::factory()->create();

    RescueRequest::create([
        'user_id' => $user->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'status' => 'pending',
    ]);

    RescueRequest::create([
        'user_id' => $user->id,
        'latitude' => 41.0000,
        'longitude' => -75.0000,
        'status' => 'resolved',
    ]);

    $token = User::factory()->create()->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/rescue/pending');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'rescue_requests');
});
