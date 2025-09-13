<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->mobile_number === $id;
});

Broadcast::channel('orders.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('admin.orders', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('chat.{mobileNumber}', function ($user, $mobileNumber) {
    if (!$user) return false;
    // Allow if user is the owner of the channel or is an admin
    return ($user->mobile_number === $mobileNumber) || ($user->role === 'admin');
});

Broadcast::channel('sliders', function ($user) {
    // Allow all authenticated users to listen to slider updates
    return $user !== null;
});