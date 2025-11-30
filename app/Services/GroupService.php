<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GroupService
{
    public function createGroup(User $owner, array $data): Group
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_private' => $data['is_private'] ?? false,
            'invitation_code' => Str::random(8),
        ]);

        $group->members()->attach($owner->id);

        return $group->load('owner', 'members');
    }

    public function joinGroupByCode(User $user, string $invitationCode): Group
    {
        $group = Group::where('invitation_code', $invitationCode)->firstOrFail();

        if (!$group->members()->where('user_id', $user->id)->exists()) {
            $group->members()->attach($user->id);
        }

        return $group->load('members');
    }

    public function leaveGroup(int $groupId, int $userId): bool
    {
        $group = Group::findOrFail($groupId);

        if ($group->owner_id === $userId) {
            throw ValidationException::withMessages([
                'authorization' => ['Group owner cannot leave. Transfer ownership or delete the group.'],
            ]);
        }

        $group->members()->detach($userId);
        return true;
    }
}
