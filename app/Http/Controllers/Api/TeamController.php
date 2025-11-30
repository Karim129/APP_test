<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $team = $this->teamService->createTeam($request->user(), $request->all());
            return response()->json(['message' => 'Team created successfully', 'team' => $team], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'TEAM_CREATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function addMember(Request $request, int $id): JsonResponse
    {
        try {
            $team = $this->teamService->addMember($id, $request->user_id);
            return response()->json(['message' => 'Member added successfully', 'team' => $team], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'ADD_MEMBER_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function removeMember(Request $request, int $id): JsonResponse
    {
        try {
            $team = $this->teamService->removeMember($id, $request->user_id);
            return response()->json(['message' => 'Member removed successfully', 'team' => $team], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'REMOVE_MEMBER_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function members(int $id): JsonResponse
    {
        try {
            $members = $this->teamService->getTeamMembers($id);
            return response()->json(['members' => $members], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'GET_MEMBERS_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $this->teamService->deleteTeam($id, $request->user()->id);
            return response()->json(['message' => 'Team deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'DELETE_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }
}
