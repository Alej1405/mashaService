<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('support-chat.{chatId}', function ($user, $chatId) {
    if ($user->hasRole('super_admin')) {
        return true;
    }

    return \App\Models\SupportChat::where('id', $chatId)
        ->where('user_id', $user->id)
        ->exists();
});
