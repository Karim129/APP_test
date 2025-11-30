<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescueRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'latitude',
        'longitude',
        'status',
        'description',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'resolved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedRescuer()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
