<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Event;

test('event creation for group', function () {
    $owner = User::factory()->create();
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);

    $token = $owner->createToken('test')->plainTextToken;

    $startTime = now()->addDays(7);
    $endTime = $startTime->copy()->addHours(3);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/events', [
            'group_id' => $group->id,
            'title' => 'Group Hike',
            'description' => 'Weekend hiking trip',
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'location' => 'Mountain Trail',
            'is_paid' => false,
        ]);

    $response->assertStatus(201);

    $event = Event::first();
    expect($event->group_id)->toBe($group->id)
        ->and($event->title)->toBe('Group Hike')
        ->and($event->is_paid)->toBeFalse();
});

test('paid event includes price', function () {
    $owner = User::factory()->create();
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);

    $token = $owner->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/events', [
            'group_id' => $group->id,
            'title' => 'Premium Workshop',
            'start_time' => now()->addDays(7)->toDateTimeString(),
            'end_time' => now()->addDays(7)->addHours(4)->toDateTimeString(),
            'is_paid' => true,
            'price' => 50.00,
        ]);

    $response->assertStatus(201);

    $event = Event::first();
    expect($event->is_paid)->toBeTrue()
        ->and((float)$event->price)->toBe(50.0);
});

test('event end time must be after start time', function () {
    $owner = User::factory()->create();
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);

    $token = $owner->createToken('test')->plainTextToken;

    $startTime = now()->addDays(7);
    $endTime = $startTime->copy()->subHour(); // End before start

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/events', [
            'group_id' => $group->id,
            'title' => 'Invalid Event',
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);

    $response->assertStatus(400);
});

test('group events retrieval shows upcoming only', function () {
    $owner = User::factory()->create();
    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);

    // Create past event
    Event::create([
        'group_id' => $group->id,
        'title' => 'Past Event',
        'start_time' => now()->subDays(7),
        'end_time' => now()->subDays(7)->addHours(2),
    ]);

    // Create upcoming event
    Event::create([
        'group_id' => $group->id,
        'title' => 'Future Event',
        'start_time' => now()->addDays(7),
        'end_time' => now()->addDays(7)->addHours(2),
    ]);

    $token = $owner->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson("/api/groups/{$group->id}/events");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'events');
});
