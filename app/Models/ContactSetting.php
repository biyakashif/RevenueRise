<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'show_email',
        'email',
        'show_whatsapp',
        'whatsapp',
        'show_telegram',
        'telegram',
        'show_office',
        'office_address',
    ];

    protected $casts = [
        'show_email' => 'boolean',
        'show_whatsapp' => 'boolean',
        'show_telegram' => 'boolean',
        'show_office' => 'boolean',
    ];
}
