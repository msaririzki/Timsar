<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CellTower extends Model
{
    protected $fillable = [
        'identity_key',
        'radio_type',
        'operator_name',
        'operator_label',
        'network_operator_code',
        'mcc',
        'mnc',
        'cell_id',
        'tac_or_lac',
        'pci_or_psc',
        'estimated_latitude',
        'estimated_longitude',
        'observation_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_latitude' => 'float',
            'estimated_longitude' => 'float',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function observations()
    {
        return $this->hasMany(CellObservation::class);
    }
}
