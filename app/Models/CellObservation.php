<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CellObservation extends Model
{
    protected $fillable = [
        'cell_tower_id',
        'user_id',
        'assignment_id',
        'location_log_id',
        'latitude',
        'longitude',
        'accuracy',
        'signal_dbm',
        'rsrp_dbm',
        'rsrq_db',
        'sinr_db',
        'is_registered',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'rsrq_db' => 'float',
            'sinr_db' => 'float',
            'is_registered' => 'boolean',
            'observed_at' => 'datetime',
        ];
    }

    public function cellTower()
    {
        return $this->belongsTo(CellTower::class);
    }
}
