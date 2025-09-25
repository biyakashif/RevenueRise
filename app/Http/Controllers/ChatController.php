<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ChatMessage;
use App\Events\NewChatMessage;

class ChatController extends Controller
{
    public function index()
    {
        try {
            $admin = \App\Models\User::where('role', 'admin')->first();
            
            return Inertia::render('Chat/Index', [
                'adminAvatar' => '/assets/avatar/admin.png'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ChatController@index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMessages(Request $request)
    {
        $admin = \App\Models\User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;
        
        $messages = \App\Models\ChatMessage::where(function($query) use ($adminId) {
            $query->where('sender_id', auth()->id())->where('recipient_id', $adminId);
        })->orWhere(function($query) use ($adminId) {
            $query->where('sender_id', $adminId)->where('recipient_id', auth()->id());
        })->where('sender_type', '!=', 'guest')
        ->orderBy('created_at', 'asc')
        ->get();
        
        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720'
            ]);

            if ($request->hasFile('video') && $request->file('video')->getSize() > 30720 * 1024) {
                return response()->json(['error' => 'The video file size must not exceed 30MB.'], 400);
            }

            $sender = auth()->user();
            $admin = \App\Models\User::where('role', 'admin')->first();
            $adminId = $admin ? $admin->id : 1;
            
            $messageId = 'user_' . time() . '_' . rand(1000, 9999);
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
                'sender_id' => $sender->id,
                'recipient_id' => $adminId,
                'message' => $request->message,
                'image_path' => $imagePath,
                'video_path' => $videoPath,
                'sender_type' => 'user'
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
            broadcast(new NewChatMessage((object)$messageData));

            return response()->json($messageData);
        } catch (\Exception $e) {
            \Log::error('Error in ChatController@sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Direct broadcast method for instant messaging
    public function broadcastMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $admin = \App\Models\User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;
        
        // Save to database
        $chatMessage = \App\Models\ChatMessage::create([
            'message_id' => 'user_' . time() . '_' . rand(1000, 9999),
            'sender_id' => auth()->id(),
            'recipient_id' => $adminId,
            'message' => $request->message,
            'sender_type' => 'user'
        ]);

        $messageData = [
            'id' => $chatMessage->id,
            'sender_id' => $chatMessage->sender_id,
            'recipient_id' => $chatMessage->recipient_id,
            'message' => $chatMessage->message,
            'created_at' => $chatMessage->created_at->toISOString(),
        ];

        // Broadcast instantly
        broadcast(new NewChatMessage((object)$messageData));

        return response()->json($messageData);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048'
        ]);

        $path = $request->file('image')->store('chat-images', 'public');
        
        return response()->json([
            'path' => '/storage/' . $path
        ]);
    }

    protected function getAdminId()
    {
        $admin = \App\Models\User::where('role', 'admin')->first();
        if (!$admin) {
            throw new \Exception('No admin user found in the system.');
        }
        return $admin->id;
    }

    public function unreadCount()
    {
        // Return 0 for pure real-time chat
        return response()->json(['count' => 0]);
    }
}
