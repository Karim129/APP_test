<?php

use App\Models\User;
use App\Models\Venue;
use App\Models\Reservation;

test('venue creation by seller', function () {
    $seller = User::factory()->create();
    $seller->assignRole('Seller');

    $token = $seller->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/venues', [
            'name' => 'Test Venue',
            'type' => 'Conference Room',
            'location' => 'New York',
            'capacity' => 50,
            'price_per_hour' => 100,
        ]);

    $response->assertStatus(201);
    expect(Venue::count())->toBe(1);
});

test('venue search with filters', function () {
    $seller = User::factory()->create();

    Venue::create([
        'seller_id' => $seller->id,
        'name' => 'Small Room',
        'type' => 'Meeting Room',
        'location' => 'NYC',
        'capacity' => 10,
        'price_per_hour' => 50,
        'is_active' => true,
    ]);

    Venue::create([
        'seller_id' => $seller->id,
        'name' => 'Large Hall',
        'type' => 'Conference Room',
        'location' => 'NYC',
        'capacity' => 100,
        'price_per_hour' => 200,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/venues/search?type=Conference Room&min_capacity=50');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'venues');
});

test('reservation calculates total price', function () {
    $seller = User::factory()->create();
    $user = User::factory()->create();

    $venue = Venue::create([
        'seller_id' => $seller->id,
        'name' => 'Test Venue',
        'type' => 'Meeting Room',
        'location' => 'NYC',
        'capacity' => 20,
        'price_per_hour' => 100,
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $startTime = now()->addDay();
    $endTime = $startTime->copy()->addHours(3);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/venues/{$venue->id}/reserve", [
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);

    $response->assertStatus(201);

    $reservation = Reservation::first();
    expect((float)$reservation->total_price)->toBe(300.0); // 3 hours * $100
});

test('reservation cancellation', function () {
    $user = User::factory()->create();
    $seller = User::factory()->create();

    $venue = Venue::create([
        'seller_id' => $seller->id,
        'name' => 'Test Venue',
        'type' => 'Meeting Room',
        'location' => 'NYC',
        'capacity' => 20,
        'price_per_hour' => 100,
        'is_active' => true,
    ]);

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'venue_id' => $venue->id,
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'total_price' => 200,
        'status' => 'confirmed',
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson("/api/reservations/{$reservation->id}");

    $response->assertStatus(200);

    $reservation->refresh();
    expect($reservation->status)->toBe('cancelled');
});
