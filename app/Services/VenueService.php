<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VenueService
{
    public function createVenue(int $sellerId, array $data): Venue
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'price_per_hour' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Venue::create([
            'seller_id' => $sellerId,
            'name' => $data['name'],
            'type' => $data['type'],
            'location' => $data['location'],
            'capacity' => $data['capacity'],
            'price_per_hour' => $data['price_per_hour'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    public function searchVenues(array $filters)
    {
        $query = Venue::where('is_active', true);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        if (isset($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }

        return $query->get();
    }

    public function createReservation(int $userId, int $venueId, array $data): Reservation
    {
        $validator = Validator::make($data, [
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $venue = Venue::findOrFail($venueId);

        // Calculate total price
        $hours = (strtotime($data['end_time']) - strtotime($data['start_time'])) / 3600;
        $totalPrice = $hours * $venue->price_per_hour;

        return Reservation::create([
            'user_id' => $userId,
            'venue_id' => $venueId,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);
    }

    public function cancelReservation(int $reservationId, int $userId): Reservation
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->user_id !== $userId) {
            throw ValidationException::withMessages([
                'authorization' => ['You can only cancel your own reservations.'],
            ]);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return $reservation;
    }
}
