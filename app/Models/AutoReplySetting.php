<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReplySetting extends Model
{
    protected $fillable = ['is_enabled', 'include_contact_info'];
    
    protected $casts = [
        'is_enabled' => 'boolean',
        'include_contact_info' => 'boolean',
    ];
}
