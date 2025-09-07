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
        $users = \App\Models\User::where('role', '!=', 'admin')
            ->select('mobile_number', 'name', 'avatar_url')
            ->withCount(['sentMessages' => function($query) {
                $query->where('recipient_id', auth()->user()->mobile_number)
                      ->whereNull('read_at');
            }])
            ->get();

        return response()->json($users);
    }

    public function getMessages($userMobile)
    {
        $adminMobile = auth()->user()->mobile_number;
        
        $messages = \App\Models\ChatMessage::where(function($query) use ($userMobile, $adminMobile) {
            $query->where('sender_id', $userMobile)
                  ->where('recipient_id', $adminMobile);
        })->orWhere(function($query) use ($userMobile, $adminMobile) {
            $query->where('sender_id', $adminMobile)
                  ->where('recipient_id', $userMobile);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark messages as read
        \App\Models\ChatMessage::where('sender_id', $userMobile)
            ->where('recipient_id', $adminMobile)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $userMobile)
    {
        try {
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720' // Updated max size to 30MB
            ]);

            if ($request->hasFile('video') && $request->file('video')->getSize() > 30720 * 1024) {
                return response()->json(['error' => 'The video file size must not exceed 30MB.'], 400);
            }

            $message = new \App\Models\ChatMessage();
            $message->sender_id = auth()->user()->mobile_number;
            $message->recipient_id = $userMobile;
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

            // Broadcast event
            broadcast(new \App\Events\NewChatMessage($message))->toOthers();

            return response()->json($message);
        } catch (\Exception $e) {
            \Log::error('Error in admin sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteChatHistory($userMobile)
    {
        try {
            $adminMobile = auth()->user()->mobile_number;

            // Fetch all messages between the admin and the user
            $messages = \App\Models\ChatMessage::where(function($query) use ($userMobile, $adminMobile) {
                $query->where('sender_id', $userMobile)
                      ->where('recipient_id', $adminMobile);
            })->orWhere(function($query) use ($userMobile, $adminMobile) {
                $query->where('sender_id', $adminMobile)
                      ->where('recipient_id', $userMobile);
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
            \App\Models\ChatMessage::where(function($query) use ($userMobile, $adminMobile) {
                $query->where('sender_id', $userMobile)
                      ->where('recipient_id', $adminMobile);
            })->orWhere(function($query) use ($userMobile, $adminMobile) {
                $query->where('sender_id', $adminMobile)
                      ->where('recipient_id', $userMobile);
            })->delete();

            // Broadcast the deletion event
            broadcast(new ChatHistoryDeleted($userMobile, $adminMobile));

            return response()->json(['message' => 'Chat history and associated files deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error deleting chat history: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete chat history.'], 500);
        }
    }
}