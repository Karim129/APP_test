<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocationTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    protected LocationTrackingService $locationService;

    public function __construct(LocationTrackingService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function record(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $location = $this->locationService->recordLocation(
            $request->user(),
            $request->latitude,
            $request->longitude
        );

        return response()->json(['location' => $location], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $locations = $this->locationService->getLocationHistory($request->user()->id, $limit);

        return response()->json(['locations' => $locations], 200);
    }

    public function latest(Request $request): JsonResponse
    {
        $location = $this->locationService->getLatestLocation($request->user()->id);

        return response()->json(['location' => $location], 200);
    }
}
