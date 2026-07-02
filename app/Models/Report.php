<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ON_THE_WAY = 'on_the_way';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_HANDLING = 'handling';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tracking_code',
        'reporter_name',
        'reporter_phone',
        'incident_type',
        'description',
        'latitude',
        'longitude',
        'accuracy',
        'status',
        'priority',
        'assigned_member_id',
        'assigned_team_id',
        'closed_at',
        'closure_notes',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'closed_at' => 'datetime',
        ];
    }

    public function assignedMember()
    {
        return $this->belongsTo(User::class, 'assigned_member_id');
    }

    public function assignedTeam()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function activeAssignment()
    {
        return $this->hasOne(Assignment::class)
            ->where('status', '!=', Assignment::STATUS_CANCELLED)
            ->latestOfMany();
    }
}
