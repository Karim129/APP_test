<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VenueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    protected VenueService $venueService;

    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $venue = $this->venueService->createVenue($request->user()->id, $request->all());
            return response()->json(['message' => 'Venue created successfully', 'venue' => $venue], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'VENUE_CREATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $venues = $this->venueService->searchVenues($request->all());
        return response()->json(['venues' => $venues], 200);
    }

    public function reserve(Request $request, int $id): JsonResponse
    {
        try {
            $reservation = $this->venueService->createReservation($request->user()->id, $id, $request->all());
            return response()->json(['message' => 'Reservation created successfully', 'reservation' => $reservation], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'RESERVATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function cancelReservation(Request $request, int $id): JsonResponse
    {
        try {
            $reservation = $this->venueService->cancelReservation($id, $request->user()->id);
            return response()->json(['message' => 'Reservation cancelled successfully', 'reservation' => $reservation], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'CANCELLATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }
}
