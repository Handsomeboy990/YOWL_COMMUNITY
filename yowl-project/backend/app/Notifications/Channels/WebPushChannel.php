<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private PushNotificationService $push) {}

    /**
     * Deliver the notification as a web push message.
     *
     * Delivery is fire and forget: the push service logs its own failures and
     * never interrupts the request that triggered the notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);
        if (! is_array($payload) || ! isset($payload['title'], $payload['body'])) {
            return;
        }

        $this->push->sendToUser(
            $notifiable,
            $payload['title'],
            $payload['body'],
            $payload['url'] ?? '/feed'
        );
    }
}
