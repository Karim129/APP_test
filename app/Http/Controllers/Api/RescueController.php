<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RescueController extends Controller
{
    protected LocationTrackingService $locationService;

    public function __construct(LocationTrackingService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $rescueRequest = $this->locationService->createRescueRequest(
                $request->user(),
                $request->all()
            );

            return response()->json([
                'message' => 'Rescue request created successfully',
                'rescue_request' => $rescueRequest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => ['code' => 'RESCUE_REQUEST_FAILED', 'message' => $e->getMessage()],
            ], 400);
        }
    }

    public function pending(): JsonResponse
    {
        $requests = $this->locationService->getPendingRescueRequests();
        return response()->json(['rescue_requests' => $requests], 200);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        try {
            $rescueRequest = $this->locationService->assignRescueRequest(
                $id,
                $request->rescuer_id
            );

            return response()->json([
                'message' => 'Rescue request assigned successfully',
                'rescue_request' => $rescueRequest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => ['code' => 'ASSIGNMENT_FAILED', 'message' => $e->getMessage()],
            ], 400);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $rescueRequest = $this->locationService->updateRescueStatus($id, $request->status);

            return response()->json([
                'message' => 'Status updated successfully',
                'rescue_request' => $rescueRequest,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => ['code' => 'STATUS_UPDATE_FAILED', 'message' => $e->getMessage()],
            ], 400);
        }
    }

    public function myAssignments(Request $request): JsonResponse
    {
        $assignments = $this->locationService->getRescuerAssignments($request->user()->id);
        return response()->json(['assignments' => $assignments], 200);
    }
}
