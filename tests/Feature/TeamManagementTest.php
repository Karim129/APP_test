<?php

use App\Models\User;
use App\Models\Team;

test('team creation assigns owner', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/teams', [
            'name' => 'Test Team',
            'description' => 'A test team',
        ]);

    $response->assertStatus(201);

    $team = Team::first();
    expect($team->owner_id)->toBe($user->id)
        ->and($team->members()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('team member addition', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::create([
        'owner_id' => $owner->id,
        'name' => 'Test Team',
    ]);
    $team->members()->attach($owner->id);

    $token = $owner->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/teams/{$team->id}/members", [
            'user_id' => $member->id,
        ]);

    $response->assertStatus(200);
    expect($team->members()->where('user_id', $member->id)->exists())->toBeTrue();
});

test('team member removal', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::create([
        'owner_id' => $owner->id,
        'name' => 'Test Team',
    ]);
    $team->members()->attach([$owner->id, $member->id]);

    $token = $owner->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson("/api/teams/{$team->id}/members", [
            'user_id' => $member->id,
        ]);

    $response->assertStatus(200);
    expect($team->members()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('only owner can delete team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::create([
        'owner_id' => $owner->id,
        'name' => 'Test Team',
    ]);

    $memberToken = $member->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $memberToken)
        ->deleteJson("/api/teams/{$team->id}");

    $response->assertStatus(400);
    expect(Team::find($team->id))->not->toBeNull();
});
