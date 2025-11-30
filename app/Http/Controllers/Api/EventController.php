<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $event = $this->eventService->createEvent($request->group_id, $request->all());
            return response()->json(['message' => 'Event created successfully', 'event' => $event], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'EVENT_CREATION_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }

    public function groupEvents(int $groupId): JsonResponse
    {
        try {
            $events = $this->eventService->getGroupEvents($groupId);
            return response()->json(['events' => $events], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'GET_EVENTS_FAILED', 'message' => $e->getMessage()]], 400);
        }
    }
}
