<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\ChatHistoryDeleted;

class ChatController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Support');
    }

    public function getUsers()
    {
        // Get regular users for real-time chat
        $users = \App\Models\User::where('role', '!=', 'admin')
            ->select('id', 'mobile_number', 'name', 'avatar_url')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mobile_number' => $user->mobile_number,
                    'avatar_url' => $user->avatar_url,
                    'unread_count' => 0,
                    'last_message_at' => null,
                ];
            });

        // Get guest users from chat messages
        $guestUsers = \App\Models\ChatMessage::where('sender_type', 'guest')
            ->select('guest_session_id', 'guest_name', 'guest_mobile')
            ->distinct()
            ->get()
            ->map(function ($guest) {
                return [
                    'id' => 'guest_' . $guest->guest_session_id,
                    'name' => $guest->guest_name,
                    'mobile_number' => $guest->guest_mobile,
                    'avatar_url' => null,
                    'unread_count' => 0,
                    'last_message_at' => null,
                    'is_guest' => true,
                    'session_id' => $guest->guest_session_id
                ];
            });

        return response()->json($users->concat($guestUsers));
    }

    public function getMessages($userId)
    {
        $messages = \App\Models\ChatMessage::where(function($query) use ($userId) {
            $query->where('sender_id', auth()->id())->where('recipient_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)->where('recipient_id', auth()->id());
        })->where('sender_type', '!=', 'guest')
        ->orderBy('created_at', 'asc')
        ->get();
        
        return response()->json($messages);
    }

    public function sendMessage(Request $request, $userId)
    {
        try {
            $adminId = auth()->id();
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720'
            ]);

            if ($request->hasFile('video') && $request->file('video')->getSize() > 30720 * 1024) {
                return response()->json(['error' => 'The video file size must not exceed 30MB.'], 400);
            }

            $messageId = 'msg_' . time() . '_' . rand(1000, 9999);
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
                'sender_id' => $adminId,
                'recipient_id' => $userId,
                'message' => $request->message,
                'image_path' => $imagePath,
                'video_path' => $videoPath,
                'sender_type' => 'admin'
            ]);

            $messageData = [
                'id' => $chatMessage->id,
                'sender_id' => $chatMessage->sender_id,
                'recipient_id' => $chatMessage->recipient_id,
                'message' => $chatMessage->message,
                'image_path' => $chatMessage->image_path,
                'video_path' => $chatMessage->video_path,
                'created_at' => $chatMessage->created_at->toISOString(),
            ];

            // Broadcast immediately for real-time delivery
            broadcast(new \App\Events\NewChatMessage((object)$messageData));

            return response()->json($messageData);
        } catch (\Exception $e) {
            \Log::error('Error in admin sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Direct broadcast method for instant messaging
    public function broadcastMessage(Request $request, $userId)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $messageId = 'msg_' . time() . '_' . rand(1000, 9999);
        
        // Save to database
        $chatMessage = \App\Models\ChatMessage::create([
            'message_id' => $messageId,
            'sender_id' => auth()->id(),
            'recipient_id' => $userId,
            'message' => $request->message,
            'sender_type' => 'admin'
        ]);

        $messageData = [
            'id' => $chatMessage->id,
            'sender_id' => $chatMessage->sender_id,
            'recipient_id' => $chatMessage->recipient_id,
            'message' => $chatMessage->message,
            'created_at' => $chatMessage->created_at->toISOString(),
        ];

        // Broadcast instantly
        broadcast(new \App\Events\NewChatMessage((object)$messageData));

        return response()->json($messageData);
    }

    public function deleteChatHistory($userId)
    {
        // Delete from database
        \App\Models\ChatMessage::where(function($query) use ($userId) {
            $query->where('sender_id', auth()->id())->where('recipient_id', $userId);
        })->orWhere(function($query) use ($userId) {
            $query->where('sender_id', $userId)->where('recipient_id', auth()->id());
        })->where('sender_type', '!=', 'guest')->delete();
        
        // Broadcast chat deletion event
        broadcast(new \App\Events\ChatDeleted($userId, false));
        
        return response()->json(['message' => 'Chat cleared successfully.']);
    }
}