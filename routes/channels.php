<?php
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int)$user->id === (int)$userId;
});

Broadcast::channel('aviator.user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});