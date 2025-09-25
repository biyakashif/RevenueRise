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
        $adminId = auth()->id();
        $cacheKey = "admin_chat_users_{$adminId}";
        
        return \Cache::remember($cacheKey, 5, function() use ($adminId) { // Cache for 5 seconds
            $users = \App\Models\User::where('role', '!=', 'admin')
                ->select('id', 'mobile_number', 'name', 'avatar_url')
                ->selectRaw('(
                    SELECT MAX(created_at)
                    FROM chat_messages
                    WHERE (sender_id = users.id AND recipient_id = ?) 
                       OR (sender_id = ? AND recipient_id = users.id)
                ) as last_message_at', [$adminId, $adminId])
                ->withCount(['sentMessages as unread_count' => function($query) use ($adminId) {
                    $query->where('recipient_id', $adminId)
                          ->whereNull('read_at');
                }])
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile_number' => $user->mobile_number,
                        'avatar_url' => $user->avatar_url,
                        'unread_count' => $user->unread_count,
                        'last_message_at' => $user->last_message_at,
                    ];
                })
                ->sortByDesc('last_message_at')
                ->values();

            return response()->json($users);
        });
    }

    public function getMessages($userId)
    {
        $adminId = auth()->id();
        
        $messages = \App\Models\ChatMessage::where(function($query) use ($userId, $adminId) {
            $query->where('sender_id', $userId)
                  ->where('recipient_id', $adminId);
        })->orWhere(function($query) use ($userId, $adminId) {
            $query->where('sender_id', $adminId)
                  ->where('recipient_id', $userId);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark messages as read
        \App\Models\ChatMessage::where('sender_id', $userId)
            ->where('recipient_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Clear cache after marking as read
        \Cache::forget("admin_chat_users_{$adminId}");

        // Broadcast user status update for real-time UI updates
        broadcast(new \App\Events\UserChatStatusUpdated($userId, false, 'message_read', [
            'unread_count' => 0,
        ]))->toOthers();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $userId)
    {
        try {
            $adminId = auth()->id();
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720' // Updated max size to 30MB
            ]);

            if ($request->hasFile('video') && $request->file('video')->getSize() > 30720 * 1024) {
                return response()->json(['error' => 'The video file size must not exceed 30MB.'], 400);
            }

            $message = new \App\Models\ChatMessage();
            $message->sender_id = auth()->id();
            $message->recipient_id = $userId;
            $message->message = $request->message;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('chat-images', 'public');
                $message->image_path = '/storage/' . $path;
            }

            if ($request->hasFile('video')) {
                $path = $request->file('video')->store('chat-videos', 'public');
                $message->video_path = '/storage/' . $path;
            }

            $message->save();

            // Clear cache for both admin and recipient
            \Cache::forget("admin_chat_users_{$adminId}");
            \Cache::forget("admin_chat_users_{$userId}");

            // Broadcast event
            broadcast(new \App\Events\NewChatMessage($message))->toOthers();

            // Broadcast user status update for real-time UI updates
            broadcast(new \App\Events\UserChatStatusUpdated($userId, false, 'new_message', [
                'unread_count' => 1,
                'last_message_at' => $message->created_at,
            ]))->toOthers();

            return response()->json($message);
        } catch (\Exception $e) {
            \Log::error('Error in admin sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteChatHistory($userId)
    {
        try {
            $adminId = auth()->id();

            // Fetch all messages between the admin and the user
            $messages = \App\Models\ChatMessage::where(function($query) use ($userId, $adminId) {
                $query->where('sender_id', $userId)
                      ->where('recipient_id', $adminId);
            })->orWhere(function($query) use ($userId, $adminId) {
                $query->where('sender_id', $adminId)
                      ->where('recipient_id', $userId);
            })->get();

            // Delete associated files (images and videos)
            foreach ($messages as $message) {
                if ($message->image_path) {
                    $imagePath = public_path(str_replace('/storage', 'storage', $message->image_path));
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }

                if ($message->video_path) {
                    $videoPath = public_path(str_replace('/storage', 'storage', $message->video_path));
                    if (file_exists($videoPath)) {
                        unlink($videoPath);
                    }
                }
            }

            // Delete all messages between the admin and the user
            \App\Models\ChatMessage::where(function($query) use ($userId, $adminId) {
                $query->where('sender_id', $userId)
                      ->where('recipient_id', $adminId);
            })->orWhere(function($query) use ($userId, $adminId) {
                $query->where('sender_id', $adminId)
                      ->where('recipient_id', $userId);
            })->delete();

            // Broadcast the deletion event
            broadcast(new ChatHistoryDeleted($userId, $adminId));

            // Broadcast user status update for real-time UI updates
            broadcast(new \App\Events\UserChatStatusUpdated($userId, false, 'chat_deleted'))->toOthers();

            return response()->json(['message' => 'Chat history and associated files deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error deleting chat history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete chat history.'], 500);
        }
    }
}