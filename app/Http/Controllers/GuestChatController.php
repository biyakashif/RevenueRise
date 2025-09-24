<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuestChat;
use App\Models\GuestChatMessage;
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

        $guestChat = GuestChat::create([
            'session_id' => Str::uuid(),
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'is_blocked' => false,
        ]);
        
        // Clear captcha from session
        session()->forget('captcha_code');

        return response()->json([
            'session_id' => $guestChat->session_id,
            'message' => 'Chat started successfully'
        ]);
    }

    public function getMessages($sessionId)
    {
        $guestChat = GuestChat::where('session_id', $sessionId)->firstOrFail();
        
        $messages = GuestChatMessage::where('guest_chat_id', $guestChat->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'is_guest' => $message->is_guest,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $sessionId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $guestChat = GuestChat::where('session_id', $sessionId)->firstOrFail();

        if ($guestChat->is_blocked) {
            return response()->json(['error' => 'You are blocked from sending messages'], 403);
        }

        $message = GuestChatMessage::create([
            'guest_chat_id' => $guestChat->id,
            'message' => $request->message,
            'is_guest' => true,
        ]);

        // Broadcast the message to admin
        broadcast(new NewGuestChatMessage($message, $guestChat));

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'is_guest' => $message->is_guest,
            'created_at' => $message->created_at,
        ]);
    }
}