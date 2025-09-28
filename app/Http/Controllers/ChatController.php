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
            // Check if user is blocked
            $isBlocked = \App\Models\UserBlock::where('user_id', auth()->id())->exists();
            if ($isBlocked) {
                return response()->json(['error' => 'You are blocked and cannot send messages.'], 403);
            }

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

            // Send auto-reply if enabled
            $this->sendAutoReply($chatMessage->sender_id);

            return response()->json($messageData);
        } catch (\Exception $e) {
            \Log::error('Error in ChatController@sendMessage: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Direct broadcast method for instant messaging
    public function broadcastMessage(Request $request)
    {
        // Check if user is blocked
        $isBlocked = \App\Models\UserBlock::where('user_id', auth()->id())->exists();
        if ($isBlocked) {
            return response()->json(['error' => 'You are blocked and cannot send messages.'], 403);
        }

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

        // Send auto-reply if enabled
        $this->sendAutoReply($chatMessage->sender_id);

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

    public function checkBlockStatus()
    {
        $isBlocked = \App\Models\UserBlock::where('user_id', auth()->id())->exists();
        return response()->json(['is_blocked' => $isBlocked]);
    }

    private function sendAutoReply($userId)
    {
        $autoReplySettings = \App\Models\AutoReplySetting::first();
        if (!$autoReplySettings || !$autoReplySettings->is_enabled) {
            return;
        }

        // Check if auto-reply already sent to this user
        $alreadySent = \DB::table('auto_reply_sent')->where('user_id', $userId)->exists();
        if ($alreadySent) {
            return;
        }

        $messages = \App\Models\AutoReplyMessage::where('is_active', true)
            ->orderBy('order')
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        $user = \App\Models\User::find($userId);
        $admin = \App\Models\User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;
        $contactSettings = \App\Models\ContactSetting::first();

        foreach ($messages as $message) {
            $messageText = $message->message;
            
            // Replace [user_name] placeholder
            if ($user) {
                $messageText = str_replace('[user_name]', $user->name, $messageText);
            }

            // Append contact information if enabled for this message
            if ($message->include_contact_info && $contactSettings) {
                $contactHtml = $this->generateContactHtml($contactSettings);
                if ($contactHtml) {
                    $messageText .= '<br><br>' . $contactHtml;
                }
            }

            // Create auto-reply message
            $autoReply = \App\Models\ChatMessage::create([
                'message_id' => 'auto_' . time() . '_' . rand(1000, 9999),
                'sender_id' => $adminId,
                'recipient_id' => $userId,
                'message' => $messageText,
                'sender_type' => 'admin',
                'image_path' => $message->image_path,
                'video_path' => $message->video_path
            ]);

            $messageData = [
                'id' => $autoReply->id,
                'sender_id' => $autoReply->sender_id,
                'recipient_id' => $autoReply->recipient_id,
                'message' => $autoReply->message,
                'image_path' => $autoReply->image_path,
                'video_path' => $autoReply->video_path,
                'created_at' => $autoReply->created_at->toISOString(),
            ];

            // Broadcast auto-reply
            broadcast(new NewChatMessage((object)$messageData));
        }

        // Mark auto-reply as sent
        \DB::table('auto_reply_sent')->insert([
            'user_id' => $userId,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function generateContactHtml($contactSettings)
    {
        $html = '';

        if ($contactSettings->show_email && $contactSettings->email) {
            $html .= '<div style="background: linear-gradient(to bottom right, rgba(6, 182, 212, 0.2), rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.2)); border-radius: 0.75rem; padding: 0.75rem; display: flex; align-items: center; margin-bottom: 0.5rem; border: 1px solid rgba(6, 182, 212, 0.3);">';
            $html .= '<div style="width: 2.5rem; height: 2.5rem; margin-right: 0.75rem; background: linear-gradient(to bottom right, #2563eb, #3b82f6); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">';
            $html .= '<svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
            $html .= '</div>';
            $html .= '<div style="flex: 1;"><h2 style="font-size: 0.875rem; font-weight: bold; color: #1e293b;">Email Support</h2><a href="mailto:' . $contactSettings->email . '" style="color: #2563eb; text-decoration: underline; font-size: 0.875rem; font-weight: 500;">' . $contactSettings->email . '</a></div>';
            $html .= '</div>';
        }

        if ($contactSettings->show_whatsapp && $contactSettings->whatsapp) {
            $whatsappNumber = preg_replace('/[^0-9]/', '', $contactSettings->whatsapp);
            $html .= '<div style="background: linear-gradient(to bottom right, rgba(34, 197, 94, 0.2), rgba(74, 222, 128, 0.15), rgba(34, 197, 94, 0.2)); border-radius: 0.75rem; padding: 0.75rem; display: flex; align-items: center; margin-bottom: 0.5rem; border: 1px solid rgba(34, 197, 94, 0.3);">';
            $html .= '<div style="width: 2.5rem; height: 2.5rem; margin-right: 0.75rem; background: linear-gradient(to bottom right, #16a34a, #22c55e); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">';
            $html .= '<svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/></svg>';
            $html .= '</div>';
            $html .= '<div style="flex: 1;"><h2 style="font-size: 0.875rem; font-weight: bold; color: #1e293b;">WhatsApp</h2><a href="https://wa.me/' . $whatsappNumber . '" target="_blank" style="color: #16a34a; text-decoration: underline; font-size: 0.875rem; font-weight: 500;">' . $contactSettings->whatsapp . '</a></div>';
            $html .= '</div>';
        }

        if ($contactSettings->show_telegram && $contactSettings->telegram) {
            $telegramLink = str_starts_with($contactSettings->telegram, 'http') 
                ? $contactSettings->telegram 
                : 'https://t.me/' . str_replace('@', '', $contactSettings->telegram);
            $html .= '<div style="background: linear-gradient(to bottom right, rgba(59, 130, 246, 0.2), rgba(96, 165, 250, 0.15), rgba(147, 197, 253, 0.2)); border-radius: 0.75rem; padding: 0.75rem; display: flex; align-items: center; margin-bottom: 0.5rem; border: 1px solid rgba(59, 130, 246, 0.3);">';
            $html .= '<div style="width: 2.5rem; height: 2.5rem; margin-right: 0.75rem; background: linear-gradient(to bottom right, #2563eb, #3b82f6); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">';
            $html .= '<svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
            $html .= '</div>';
            $html .= '<div style="flex: 1;"><h2 style="font-size: 0.875rem; font-weight: bold; color: #1e293b;">Telegram</h2><a href="' . $telegramLink . '" target="_blank" style="color: #2563eb; text-decoration: underline; font-size: 0.875rem; font-weight: 500;">' . $contactSettings->telegram . '</a></div>';
            $html .= '</div>';
        }

        if ($contactSettings->show_office && $contactSettings->office_address) {
            $html .= '<div style="background: linear-gradient(to bottom right, rgba(147, 51, 234, 0.2), rgba(168, 85, 247, 0.15), rgba(196, 181, 253, 0.2)); border-radius: 0.75rem; padding: 0.75rem; display: flex; align-items: start; margin-bottom: 0.5rem; border: 1px solid rgba(147, 51, 234, 0.3);">';
            $html .= '<div style="width: 2.5rem; height: 2.5rem; margin-right: 0.75rem; background: linear-gradient(to bottom right, #7c3aed, #8b5cf6); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">';
            $html .= '<svg style="width: 1.25rem; height: 1.25rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
            $html .= '</div>';
            $html .= '<div style="flex: 1;"><h2 style="font-size: 0.875rem; font-weight: bold; color: #1e293b; margin-bottom: 0.25rem;">Office Address</h2><p style="color: #374151; font-size: 0.875rem; line-height: 1.25;">' . $contactSettings->office_address . '</p></div>';
            $html .= '</div>';
        }

        return $html;
    }
}
