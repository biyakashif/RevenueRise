<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'name',
        'mobile_number',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(GuestChatMessage::class);
    }
}