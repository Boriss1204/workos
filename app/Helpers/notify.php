<?php

use App\Models\Notification;

if (!function_exists('notify_user')) {
    function notify_user(int $userId, string $type, string $title, ?string $body = null, ?string $url = null, array $data = []): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'data' => $data ?: null,
        ]);
    }
}
