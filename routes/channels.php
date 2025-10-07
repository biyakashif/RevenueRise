<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin.orders', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('chat.{id}', function ($user, $id) {
    return true;
});

Broadcast::channel('admin.{id}', function ($user, $id) {
    return $user->role === 'admin' && (int) $user->id === (int) $id;
});

Broadcast::channel('sliders', function ($user) {
    // Allow all authenticated users to listen to slider updates
    return $user !== null;
});

Broadcast::channel('admin-chat-updates', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('guest-chat', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('crypto-updates', function ($user) {
    return $user !== null; // Allow all authenticated users
});