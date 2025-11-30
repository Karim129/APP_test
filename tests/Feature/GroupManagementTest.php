<?php

use App\Models\User;
use App\Models\Group;

test('group creation with invitation code', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/groups', [
            'name' => 'Hiking Group',
            'description' => 'For outdoor enthusiasts',
            'is_private' => true,
        ]);

    $response->assertStatus(201);

    $group = Group::first();
    expect($group->owner_id)->toBe($user->id)
        ->and($group->invitation_code)->not->toBeNull()
        ->and(strlen($group->invitation_code))->toBe(8)
        ->and($group->is_private)->toBeTrue()
        ->and($group->members()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('join group by invitation code', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
        'is_private' => true,
    ]);
    $group->members()->attach($owner->id);

    $token = $member->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/groups/join', [
            'invitation_code' => 'ABC12345',
        ]);

    $response->assertStatus(200);
    expect($group->members()->where('user_id', $member->id)->exists())->toBeTrue();
});

test('owner cannot leave group', function () {
    $owner = User::factory()->create();

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);
    $group->members()->attach($owner->id);

    $token = $owner->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson("/api/groups/{$group->id}/leave");

    $response->assertStatus(400);
    expect($group->members()->where('user_id', $owner->id)->exists())->toBeTrue();
});

test('member can leave group', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Test Group',
        'invitation_code' => 'ABC12345',
    ]);
    $group->members()->attach([$owner->id, $member->id]);

    $token = $member->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->deleteJson("/api/groups/{$group->id}/leave");

    $response->assertStatus(200);
    expect($group->members()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('private group requires invitation code', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $group = Group::create([
        'owner_id' => $owner->id,
        'name' => 'Private Group',
        'invitation_code' => 'SECRET99',
        'is_private' => true,
    ]);

    $token = $member->createToken('test')->plainTextToken;

    // Try to join with wrong code
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/groups/join', [
            'invitation_code' => 'WRONGCODE',
        ]);

    $response->assertStatus(404);
});
