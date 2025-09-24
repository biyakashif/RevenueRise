# Guest Chat with Laravel Echo & Pusher Setup

## 🚀 Real-time Guest Chat System

Your Laravel application now has a complete real-time guest chat system using Laravel Echo and Pusher.

## ✅ Features Implemented

### Guest Features:
- **Floating Help Icon**: Bottom-right corner on guest pages
- **Captcha Verification**: Simple math captcha to prevent bots
- **Real-time Messaging**: Instant message delivery via Pusher
- **Sound Notifications**: Audio alerts for admin replies
- **Auto-scroll**: Messages automatically scroll to bottom

### Admin Features:
- **Guest User Management**: See all guest users with "Guest" labels
- **Real-time Notifications**: Instant alerts for new guest messages
- **Block/Unblock**: Control guest user access
- **Delete Chat History**: Remove entire conversations
- **Visual Indicators**: Blocked users shown with red border

## 🔧 Setup Instructions

### 1. Configure Pusher
Add these to your `.env` file:
```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

ADMIN_USER_ID=1
```

### 2. Install Pusher (if not already installed)
```bash
composer require pusher/pusher-php-server
npm install --save-dev laravel-echo pusher-js
```

### 3. Start Queue Worker (for broadcasting)
```bash
php artisan queue:work
```

### 4. Test the System
1. Visit any guest page (login/register)
2. Click the floating help icon (bottom-right)
3. Fill the form with captcha
4. Start chatting!

## 📡 How Real-time Works

### Broadcasting Channels:
- **Admin Channel**: `chat.{admin_id}` (private) - Admin receives all messages
- **Guest Channel**: `guest-chat.{session_id}` (public) - Guest receives admin replies

### Events:
- **NewGuestChatMessage**: Fired when any message is sent
- **Broadcasts to**: Both admin and guest channels simultaneously

### Frontend:
- **Laravel Echo**: Handles WebSocket connections
- **Pusher**: Real-time message delivery
- **Fallback**: Automatic fallback if Echo fails

## 🎯 Usage Flow

1. **Guest clicks help icon** → Opens chat modal
2. **Guest fills form + captcha** → Creates session
3. **Guest sends message** → Broadcasts to admin
4. **Admin sees notification** → Can reply instantly
5. **Admin replies** → Guest receives real-time
6. **Sound notifications** → Both sides get audio alerts

## 🔒 Security Features

- **Captcha verification** prevents bot spam
- **Session-based identification** for guests
- **Block functionality** stops unwanted users
- **Input validation** on all messages
- **CSRF protection** on all requests

## 🎨 UI Features

- **Floating help button** with gradient styling
- **Modal chat interface** with smooth animations
- **Guest/Admin message styling** (different colors)
- **Blocked user indicators** (red border, opacity)
- **Real-time typing indicators** (visual feedback)

## 🚨 Troubleshooting

### If real-time doesn't work:
1. Check Pusher credentials in `.env`
2. Ensure queue worker is running
3. Check browser console for Echo errors
4. Verify broadcasting driver is set to 'pusher'

### If messages don't appear:
1. Check database migrations ran successfully
2. Verify routes are properly registered
3. Check Laravel logs for errors
4. Ensure CSRF tokens are valid

## 📱 Mobile Responsive

The guest chat system is fully responsive and works on:
- ✅ Desktop browsers
- ✅ Mobile devices
- ✅ Tablets
- ✅ Touch interfaces

## 🎉 You're All Set!

Your real-time guest chat system is now live! Guests can contact support instantly, and admins can manage all conversations in real-time without page refreshes.