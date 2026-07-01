<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['team_code', 'team_name', 'leader_id', 'vehicle_type', 'member_count', 'status'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members')->withPivot(['position', 'is_leader']);
    }
}
