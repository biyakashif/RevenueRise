<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\NewGuestChatMessage;

class GuestChatController extends Controller
{
    public function startChat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
        ]);

        // Check if mobile number is blocked
        if (\App\Models\BlockedGuest::where('mobile_number', $request->mobile_number)->exists()) {
            return response()->json(['blocked' => true], 200);
        }

        $sessionId = Str::uuid();
        
        // Store guest info in session with proper session handling
        session()->put("guest_{$sessionId}_name", $request->name);
        session()->put("guest_{$sessionId}_mobile", $request->mobile_number);
        session()->save();
        
        session()->forget('captcha_code');

        return response()->json([
            'session_id' => $sessionId,
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'message' => 'Chat started successfully'
        ]);
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

    public function broadcastMessage(Request $request, $sessionId)
    {
        // Check if guest is blocked in database
        if (\App\Models\BlockedGuest::where('session_id', $sessionId)->exists()) {
            return response()->json(['error' => 'You have been blocked. You cannot send messages.'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:1000',
            'guest_name' => 'nullable|string',
            'guest_mobile' => 'nullable|string'
        ]);

        // Try to get from session first, then from request
        $guestName = session("guest_{$sessionId}_name") ?: $request->guest_name ?: 'Guest';
        $guestMobile = session("guest_{$sessionId}_mobile") ?: $request->guest_mobile ?: 'N/A';

        // Save to database
        $chatMessage = \App\Models\ChatMessage::create([
            'message_id' => 'guest_' . time() . '_' . rand(1000, 9999),
            'message' => $request->message,
            'sender_type' => 'guest',
            'guest_session_id' => $sessionId,
            'guest_name' => $guestName,
            'guest_mobile' => $guestMobile
        ]);

        $messageData = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'is_guest' => true,
            'created_at' => $chatMessage->created_at->toISOString(),
            'sender_id' => $sessionId,
            'guest_name' => $guestName,
            'guest_mobile' => $guestMobile,
        ];

        broadcast(new NewGuestChatMessage($messageData, $sessionId));

        return response()->json($messageData);
    }

    public function sendFile(Request $request, $sessionId)
    {
        // Check if guest is blocked in database
        if (\App\Models\BlockedGuest::where('session_id', $sessionId)->exists()) {
            return response()->json(['error' => 'You have been blocked. You cannot send messages.'], 403);
        }

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video' => 'nullable|mimes:mp4,mov,avi,wmv|max:30720', // 30MB
        ]);

        $messageId = 'guest_file_' . time() . '_' . rand(1000, 9999);
        $guestName = session("guest_{$sessionId}_name", 'Guest');
        $guestMobile = session("guest_{$sessionId}_mobile", 'N/A');
        $imagePath = null;
        $videoPath = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat-images', 'public');
            $imagePath = '/storage/' . $path;
        }

        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('chat-videos', 'public');
            $videoPath = '/storage/' . $path;
        }

        if (!$imagePath && !$videoPath) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        // Save to database
        $chatMessage = \App\Models\ChatMessage::create([
            'message_id' => $messageId,
            'message' => '',
            'sender_type' => 'guest',
            'guest_session_id' => $sessionId,
            'guest_name' => $guestName,
            'guest_mobile' => $guestMobile,
            'image_path' => $imagePath,
            'video_path' => $videoPath
        ]);

        $messageData = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'is_guest' => true,
            'created_at' => $chatMessage->created_at->toISOString(),
            'sender_id' => $sessionId,
            'guest_name' => $guestName,
            'guest_mobile' => $guestMobile,
            'image_path' => $chatMessage->image_path,
            'video_path' => $chatMessage->video_path,
        ];

        broadcast(new NewGuestChatMessage($messageData, $sessionId));

        return response()->json($messageData);
    }

    public function checkBlockStatus($sessionId)
    {
        $isBlocked = \App\Models\BlockedGuest::where('session_id', $sessionId)->exists();
        return response()->json(['is_blocked' => $isBlocked]);
    }
}