<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\NewGuestChatMessage;

class GuestChatController extends Controller
{
    public function getUsers()
    {
        // Get guest users from chat messages, excluding blocked ones
        $guestUsers = \App\Models\ChatMessage::where('sender_type', 'guest')
            ->select('guest_session_id', 'guest_name', 'guest_mobile')
            ->distinct()
            ->get()
            ->filter(function ($guest) {
                // Only include guests that are not blocked
                return !\App\Models\BlockedGuest::where('session_id', $guest->guest_session_id)->exists();
            })
            ->map(function ($guest) {
                return [
                    'id' => 'guest_' . $guest->guest_session_id,
                    'name' => $guest->guest_name,
                    'mobile_number' => $guest->guest_mobile,
                    'avatar_url' => null,
                    'unread_count' => 0,
                    'last_message_at' => null,
                    'is_guest' => true,
                    'session_id' => $guest->guest_session_id,
                    'is_blocked' => false
                ];
            })
            ->values(); // Reset array keys

        return response()->json($guestUsers);
    }

    public function getMessages($sessionId)
    {
        $messages = \App\Models\ChatMessage::where('guest_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'image_path' => $msg->image_path,
                    'video_path' => $msg->video_path,
                    'is_guest' => $msg->sender_type === 'guest',
                    'created_at' => $msg->created_at->toISOString(),
                    'sender_id' => $msg->sender_type === 'guest' ? $msg->guest_session_id : $msg->sender_id
                ];
            });
        
        return response()->json($messages);
    }

    public function sendMessage(Request $request, $sessionId)
    {
        try {
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720'
            ]);

            $messageId = 'admin_' . time() . '_' . rand(1000, 9999);
            $imagePath = null;
            $videoPath = null;

            // Handle file uploads
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('chat-images', 'public');
                $imagePath = '/storage/' . $path;
            }

            if ($request->hasFile('video')) {
                $path = $request->file('video')->store('chat-videos', 'public');
                $videoPath = '/storage/' . $path;
            }

            // Save to database
            $chatMessage = \App\Models\ChatMessage::create([
                'message_id' => $messageId,
                'message' => $request->message,
                'sender_id' => auth()->id(),
                'sender_type' => 'admin',
                'guest_session_id' => $sessionId,
                'image_path' => $imagePath,
                'video_path' => $videoPath
            ]);

            $messageData = [
                'id' => $chatMessage->id,
                'message' => $chatMessage->message,
                'is_guest' => false,
                'created_at' => $chatMessage->created_at->toISOString(),
                'sender_id' => $chatMessage->sender_id,
                'image_path' => $chatMessage->image_path,
                'video_path' => $chatMessage->video_path,
            ];

            broadcast(new NewGuestChatMessage($messageData, $sessionId));

            return response()->json($messageData);
        } catch (\Exception $e) {
            \Log::error('Error in admin guest sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function broadcastMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        // Save to database
        $chatMessage = \App\Models\ChatMessage::create([
            'message_id' => 'admin_' . time() . '_' . rand(1000, 9999),
            'message' => $request->message,
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'guest_session_id' => $sessionId
        ]);

        $messageData = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'is_guest' => false,
            'created_at' => $chatMessage->created_at->toISOString(),
            'sender_id' => $chatMessage->sender_id,
        ];

        broadcast(new NewGuestChatMessage($messageData, $sessionId));

        return response()->json($messageData);
    }

    public function deleteChatHistory($sessionId)
    {
        // Delete from database
        \App\Models\ChatMessage::where('guest_session_id', $sessionId)->delete();
        
        // Clear auto-reply tracking so auto-replies can be sent again
        \DB::table('auto_reply_sent')->where('guest_session_id', $sessionId)->delete();
        
        // Broadcast chat deletion event
        broadcast(new \App\Events\ChatDeleted($sessionId, true));
        
        return response()->json(['message' => 'Chat cleared successfully.']);
    }

    public function blockUser($sessionId)
    {
        // Get guest info from chat messages
        $guestMessage = \App\Models\ChatMessage::where('guest_session_id', $sessionId)
            ->where('sender_type', 'guest')
            ->first();
            
        if ($guestMessage) {
            \App\Models\BlockedGuest::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'name' => $guestMessage->guest_name,
                    'mobile_number' => $guestMessage->guest_mobile,
                    'blocked_at' => now()
                ]
            );
            
            // Delete chat history
            \App\Models\ChatMessage::where('guest_session_id', $sessionId)->delete();
            
            // Clear session data
            session()->forget("guest_{$sessionId}_name");
            session()->forget("guest_{$sessionId}_mobile");
        }
        
        return response()->json(['message' => 'User blocked successfully']);
    }

    public function unblockUser($sessionId)
    {
        \App\Models\BlockedGuest::where('session_id', $sessionId)->delete();
        return response()->json(['message' => 'User unblocked successfully']);
    }
    
    public function getBlockedGuests()
    {
        $blockedGuests = \App\Models\BlockedGuest::orderBy('blocked_at', 'desc')->get();
        return response()->json($blockedGuests);
    }
}