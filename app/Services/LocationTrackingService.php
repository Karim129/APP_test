<?php

namespace App\Services;

use App\Models\Location;
use App\Models\RescueRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class LocationTrackingService
{
    /**
     * Record a location for a user.
     *
     * @param User $user
     * @param float $latitude
     * @param float $longitude
     * @return Location
     */
    public function recordLocation(User $user, float $latitude, float $longitude): Location
    {
        return Location::create([
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Get user's location history.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getLocationHistory(int $userId, int $limit = 50): Collection
    {
        return Location::where('user_id', $userId)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest location for a user.
     *
     * @param int $userId
     * @return Location|null
     */
    public function getLatestLocation(int $userId): ?Location
    {
        return Location::where('user_id', $userId)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Create a rescue request.
     *
     * @param User $user
     * @param array $data
     * @return RescueRequest
     * @throws ValidationException
     */
    public function createRescueRequest(User $user, array $data): RescueRequest
    {
        $validator = Validator::make($data, [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return RescueRequest::create([
            'user_id' => $user->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Assign a rescue request to a rescue team member.
     *
     * @param int $requestId
     * @param int $rescuerId
     * @return RescueRequest
     * @throws ValidationException
     */
    public function assignRescueRequest(int $requestId, int $rescuerId): RescueRequest
    {
        $request = RescueRequest::find($requestId);

        if (!$request) {
            throw ValidationException::withMessages([
                'request' => ['Rescue request not found.'],
            ]);
        }

        $rescuer = User::find($rescuerId);

        if (!$rescuer || !$rescuer->hasRole('Rescue Team')) {
            throw ValidationException::withMessages([
                'rescuer' => ['Invalid rescuer. Must have Rescue Team role.'],
            ]);
        }

        $request->assigned_to = $rescuerId;
        $request->status = 'assigned';
        $request->save();

        return $request;
    }

    /**
     * Update rescue request status.
     *
     * @param int $requestId
     * @param string $status
     * @return RescueRequest
     * @throws ValidationException
     */
    public function updateRescueStatus(int $requestId, string $status): RescueRequest
    {
        $validStatuses = ['pending', 'assigned', 'in_progress', 'resolved', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status.'],
            ]);
        }

        $request = RescueRequest::find($requestId);

        if (!$request) {
            throw ValidationException::withMessages([
                'request' => ['Rescue request not found.'],
            ]);
        }

        $request->status = $status;

        if ($status === 'resolved') {
            $request->resolved_at = now();
        }

        $request->save();

        return $request;
    }

    /**
     * Get all pending rescue requests.
     *
     * @return Collection
     */
    public function getPendingRescueRequests(): Collection
    {
        return RescueRequest::with(['user', 'assignedRescuer'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get rescue requests assigned to a rescuer.
     *
     * @param int $rescuerId
     * @return Collection
     */
    public function getRescuerAssignments(int $rescuerId): Collection
    {
        return RescueRequest::with('user')
            ->where('assigned_to', $rescuerId)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
