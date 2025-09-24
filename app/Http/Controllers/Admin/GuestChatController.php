<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GuestChat;
use App\Models\GuestChatMessage;
use App\Events\NewGuestChatMessage;

class GuestChatController extends Controller
{
    public function getUsers()
    {
        $guestChats = GuestChat::withCount(['messages as unread_count' => function ($query) {
            $query->where('is_guest', true)->where('is_read', false);
        }])
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($guest) {
            return [
                'id' => $guest->id,
                'name' => $guest->name,
                'mobile_number' => $guest->mobile_number,
                'unread_count' => $guest->unread_count,
                'is_guest' => true,
                'is_blocked' => $guest->is_blocked,
                'avatar_url' => null,
            ];
        });

        return response()->json($guestChats);
    }

    public function getMessages($guestId)
    {
        $guestChat = GuestChat::findOrFail($guestId);
        
        $messages = GuestChatMessage::where('guest_chat_id', $guestChat->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($guestChat) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->is_guest ? $guestChat->id : auth()->id(),
                    'recipient_id' => $message->is_guest ? auth()->id() : $guestChat->id,
                    'created_at' => $message->created_at,
                    'is_guest' => $message->is_guest,
                ];
            });

        // Mark guest messages as read
        GuestChatMessage::where('guest_chat_id', $guestChat->id)
            ->where('is_guest', true)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $guestId)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $guestChat = GuestChat::findOrFail($guestId);

        $message = GuestChatMessage::create([
            'guest_chat_id' => $guestChat->id,
            'message' => $request->message,
            'is_guest' => false,
        ]);

        $guestChat->touch(); // Update the updated_at timestamp

        // Broadcast the message
        broadcast(new NewGuestChatMessage($message, $guestChat));

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'sender_id' => auth()->id(),
            'recipient_id' => $guestChat->id,
            'created_at' => $message->created_at,
            'is_guest' => false,
        ]);
    }

    public function deleteChatHistory($guestId)
    {
        $guestChat = GuestChat::findOrFail($guestId);
        
        GuestChatMessage::where('guest_chat_id', $guestChat->id)->delete();
        $guestChat->delete();

        return response()->json(['message' => 'Chat history deleted successfully']);
    }

    public function blockUser($guestId)
    {
        $guestChat = GuestChat::findOrFail($guestId);
        $guestChat->update(['is_blocked' => true]);

        return response()->json(['message' => 'User blocked successfully']);
    }

    public function unblockUser($guestId)
    {
        $guestChat = GuestChat::findOrFail($guestId);
        $guestChat->update(['is_blocked' => false]);

        return response()->json(['message' => 'User unblocked successfully']);
    }
}