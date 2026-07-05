<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

    # for public channel
// Broadcast::channel('end-task', function () {
    //     return true;
// });

    # for private channel
Broadcast::channel('end-task.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

