<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = ['team_id', 'user_id', 'position', 'is_leader'];

    protected function casts(): array
    {
        return ['is_leader' => 'boolean'];
    }
}
