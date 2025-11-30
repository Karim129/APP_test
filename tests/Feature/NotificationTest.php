<?php

use App\Models\User;
use App\Models\Notification;

test('notification creation', function () {
    $user = User::factory()->create();

    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'info',
        'title' => 'Test Notification',
        'message' => 'This is a test',
        'read' => false,
    ]);

    expect($notification->user_id)->toBe($user->id)
        ->and($notification->read)->toBeFalse();
});

test('user can retrieve notifications', function () {
    $user = User::factory()->create();

    Notification::create([
        'user_id' => $user->id,
        'type' => 'info',
        'title' => 'Notification 1',
        'message' => 'Message 1',
    ]);

    Notification::create([
        'user_id' => $user->id,
        'type' => 'warning',
        'title' => 'Notification 2',
        'message' => 'Message 2',
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'notifications');
});

test('mark notification as read', function () {
    $user = User::factory()->create();

    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'info',
        'title' => 'Test',
        'message' => 'Test message',
        'read' => false,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson("/api/notifications/{$notification->id}/read");

    $response->assertStatus(200);

    $notification->refresh();
    expect($notification->read)->toBeTrue();
});

test('mark all notifications as read', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => "Notification $i",
            'message' => "Message $i",
            'read' => false,
        ]);
    }

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->putJson('/api/notifications/read-all');

    $response->assertStatus(200);

    expect(Notification::where('user_id', $user->id)->where('read', false)->count())->toBe(0);
});
