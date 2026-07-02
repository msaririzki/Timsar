<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CellHandoverEvent extends Model
{
    protected $fillable = [
        'user_id',
        'assignment_id',
        'from_cell_tower_id',
        'to_cell_tower_id',
        'latitude',
        'longitude',
        'observed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'observed_at' => 'datetime',
        ];
    }

    public function fromCellTower()
    {
        return $this->belongsTo(CellTower::class, 'from_cell_tower_id');
    }

    public function toCellTower()
    {
        return $this->belongsTo(CellTower::class, 'to_cell_tower_id');
    }
}
