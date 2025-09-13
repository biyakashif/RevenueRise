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
        $mobile = auth()->user()->mobile_number;
        
        $messages = ChatMessage::where(function($query) use ($mobile) {
            $query->where('sender_id', $mobile)
                ->orWhere('recipient_id', $mobile);
        })
        ->with('sender:mobile_number,name,avatar_url')
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark messages as read
        ChatMessage::where('recipient_id', auth()->user()->mobile_number)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        try {
            \Log::info('Chat message request:', $request->all());
            
            $request->validate([
                'message' => 'required_without_all:image,video',
                'image' => 'nullable|image|max:2048',
                'video' => 'nullable|mimetypes:video/mp4,video/x-matroska|max:30720' // Updated max size to 30MB
            ]);

            if ($request->hasFile('video') && $request->file('video')->getSize() > 30720 * 1024) {
                return response()->json(['error' => 'The video file size must not exceed 30MB.'], 400);
            }

            $sender = auth()->user();
            $admin = \App\Models\User::where('role', 'admin')->first();

            \Log::info('Sender:', ['id' => $sender->mobile_number]);
            \Log::info('Admin:', $admin ? ['id' => $admin->mobile_number] : 'No admin found');

            $message = new ChatMessage();
            $message->sender_id = $sender->mobile_number;
            $message->recipient_id = $admin ? $admin->mobile_number : '1234567890';
            $message->message = $request->message;            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('chat-images', 'public');
                $message->image_path = '/storage/' . $path;
            }

            if ($request->hasFile('video')) {
                $path = $request->file('video')->store('chat-videos', 'public');
                $message->video_path = '/storage/' . $path;
            }

            $message->save();

            broadcast(new NewChatMessage($message))->toOthers();

            return response()->json($message);
        } catch (\Exception $e) {
            \Log::error('Error in ChatController@sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
        $count = ChatMessage::where('recipient_id', auth()->user()->mobile_number)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
