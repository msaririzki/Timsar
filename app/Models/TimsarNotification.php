<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimsarNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'report_id', 'title', 'message', 'type', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }
}
