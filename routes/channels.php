<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chat-channel.{receiverId}.{senderId}', function (User $user, $receiverId, $senderId) {
    return (int) $user->id === (int) $receiverId || (int) $user->id === (int) $senderId;
});

// Broadcast::channel('notification-channel.{receiverId}.{senderId}', function (User $user, $receiverId, $senderId) {
//     return (int) $user->id === (int) $receiverId || (int) $user->id === (int) $senderId;
// });
Broadcast::channel('notification-channel.{receiverId}', function (User $user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});
