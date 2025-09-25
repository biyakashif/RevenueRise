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
        $cacheKey = "admin_guest_chat_users";
        
        return \Cache::remember($cacheKey, 5, function() { // Cache for 5 seconds
            $guestChats = GuestChat::withCount(['messages as unread_count' => function ($query) {
                $query->where('is_guest', true)->where('is_read', false);
            }])
            ->with(['messages' => function($query) {
                $query->select('created_at', 'guest_chat_id')
                      ->latest()
                      ->limit(1);
            }])
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
                    'last_message_at' => $guest->messages->first()?->created_at,
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

            return response()->json($guestChats);
        });
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

        // Clear cache after marking as read
        \Cache::forget("admin_guest_chat_users");

        // Broadcast user status update for real-time UI updates
        broadcast(new \App\Events\UserChatStatusUpdated($guestChat->id, true, 'message_read', [
            'unread_count' => 0,
        ]))->toOthers();

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

        // Clear cache
        \Cache::forget("admin_guest_chat_users");

        // Broadcast guest chat message
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
        $sessionId = $guestChat->session_id;
        
        // Delete all messages first
        GuestChatMessage::where('guest_chat_id', $guestChat->id)->delete();
        
        // Delete the guest chat record
        $guestChat->delete();

        // Clear cache
        \Cache::forget("admin_guest_chat_users");

        // Broadcast to guest that chat was deleted
        broadcast(new \App\Events\GuestChatDeleted($sessionId))->toOthers();

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