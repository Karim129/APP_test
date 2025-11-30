<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Group;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function createEvent(int $groupId, array $data): Event
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'is_paid' => 'boolean',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Event::create([
            'group_id' => $groupId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'location' => $data['location'] ?? null,
            'is_paid' => $data['is_paid'] ?? false,
            'price' => $data['price'] ?? 0,
        ]);
    }

    public function getGroupEvents(int $groupId)
    {
        return Event::where('group_id', $groupId)
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();
    }
}
