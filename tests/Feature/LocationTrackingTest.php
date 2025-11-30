<?php

use App\Models\User;
use App\Models\Location;

test('location recording creates record', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/locations', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

    $response->assertStatus(201);
    expect(Location::where('user_id', $user->id)->count())->toBe(1);
});

test('location history retrieval', function () {
    $user = User::factory()->create();

    // Create multiple locations
    for ($i = 0; $i < 5; $i++) {
        Location::create([
            'user_id' => $user->id,
            'latitude' => 40.7128 + $i,
            'longitude' => -74.0060,
            'recorded_at' => now()->subMinutes($i),
        ]);
    }

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/locations/history');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'locations');
});

test('latest location returns most recent', function () {
    $user = User::factory()->create();

    Location::create([
        'user_id' => $user->id,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'recorded_at' => now()->subHours(2),
    ]);

    $latest = Location::create([
        'user_id' => $user->id,
        'latitude' => 41.0000,
        'longitude' => -75.0000,
        'recorded_at' => now(),
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/locations/latest');

    $response->assertStatus(200)
        ->assertJsonPath('location.id', $latest->id);
});
