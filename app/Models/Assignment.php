<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ON_THE_WAY = 'on_the_way';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_HANDLING = 'handling';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'report_id',
        'assigned_member_id',
        'assigned_team_id',
        'assigned_by',
        'assignment_type',
        'status',
        'assigned_at',
        'accepted_at',
        'started_at',
        'arrived_at',
        'completed_at',
        'distance_meters',
        'duration_seconds',
        'route_geometry_json',
        'route_steps_json',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
            'distance_meters' => 'float',
            'route_geometry_json' => 'array',
            'route_steps_json' => 'array',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'assigned_member_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }
}
