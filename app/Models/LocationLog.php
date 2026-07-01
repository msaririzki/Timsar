<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationLog extends Model
{
    protected $fillable = [
        'user_id',
        'assignment_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'network_type',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'speed' => 'float',
            'recorded_at' => 'datetime',
        ];
    }
}
