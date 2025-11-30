<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{
    public function createTeam(User $owner, array $data): Team
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $team = Team::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        // Owner automatically joins the team
        $team->members()->attach($owner->id);

        return $team->load('owner', 'members');
    }

    public function addMember(int $teamId, int $userId): Team
    {
        $team = Team::findOrFail($teamId);

        if (!$team->members()->where('user_id', $userId)->exists()) {
            $team->members()->attach($userId);
        }

        return $team->load('members');
    }

    public function removeMember(int $teamId, int $userId): Team
    {
        $team = Team::findOrFail($teamId);
        $team->members()->detach($userId);

        return $team->load('members');
    }

    public function getTeamMembers(int $teamId): Collection
    {
        $team = Team::findOrFail($teamId);
        return $team->members;
    }

    public function deleteTeam(int $teamId, int $userId): bool
    {
        $team = Team::findOrFail($teamId);

        if ($team->owner_id !== $userId) {
            throw ValidationException::withMessages([
                'authorization' => ['Only the team owner can delete the team.'],
            ]);
        }

        $team->members()->detach();
        return $team->delete();
    }
}
