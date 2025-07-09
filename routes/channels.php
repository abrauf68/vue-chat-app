<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('presence.chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
Broadcast::channel('group.chat.{id}', function ($user, $id) {
    return $user->groups->contains('id', $id);
});

Broadcast::channel('user-status', function ($user) {
    return $user;
});
