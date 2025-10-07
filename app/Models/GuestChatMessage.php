<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_chat_id',
        'message',
        'is_guest',
        'is_read',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'is_read' => 'boolean',
    ];

    public function guestChat()
    {
        return $this->belongsTo(GuestChat::class);
    }
}