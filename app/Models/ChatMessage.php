<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Optional model - chat now works primarily via WebSocket broadcasts
// Database storage is for history/persistence only
class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'sender_id',
        'recipient_id', 
        'message',
        'image_path',
        'video_path',
        'sender_type',
        'guest_session_id',
        'guest_name',
        'guest_mobile',
        'read_at'
    ];

    protected $casts = [
        'read_at' => 'datetime'
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // Create from broadcast data
    public static function fromBroadcast($data)
    {
        return (object) [
            'id' => $data['id'] ?? 'live_' . time(),
            'sender_id' => $data['sender_id'],
            'recipient_id' => $data['recipient_id'],
            'message' => $data['message'],
            'image_path' => $data['image_path'] ?? null,
            'video_path' => $data['video_path'] ?? null,
            'created_at' => $data['created_at'] ?? now()->toISOString(),
        ];
    }
}
