<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id', 'title', 'description', 'start_time',
        'end_time', 'location', 'price', 'is_paid'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
