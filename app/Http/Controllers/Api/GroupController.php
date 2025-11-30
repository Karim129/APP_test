<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $group = $this->groupService->createGroup($request->user(), $request->all());
            return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'GROUP_CREATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function join(Request $request): JsonResponse
    {
        try {
            $group = $this->groupService->joinGroupByCode($request->user(), $request->invitation_code);
            return response()->json(['message' => 'Joined group successfully', 'group' => $group], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'JOIN_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        try {
            $this->groupService->leaveGroup($id, $request->user()->id);
            return response()->json(['message' => 'Left group successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'LEAVE_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }
}
