<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedGuest extends Model
{
    protected $fillable = [
        'session_id',
        'name', 
        'mobile_number',
        'blocked_at'
    ];

    protected $casts = [
        'blocked_at' => 'datetime'
    ];
}