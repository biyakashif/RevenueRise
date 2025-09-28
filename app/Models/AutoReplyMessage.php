<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReplyMessage extends Model
{
    protected $fillable = ['message', 'order', 'is_active', 'include_contact_info', 'image_path', 'video_path'];
    
    protected $casts = [
        'is_active' => 'boolean',
        'include_contact_info' => 'boolean',
    ];
}
