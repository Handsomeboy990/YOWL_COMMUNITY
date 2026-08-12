<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Notifications\Notification;

/**
 * Base class for every in-app activity notification.
 *
 * Each notification is stored in the database, so the bell panel can show a
 * history, and pushed to the browser when the recipient has a subscription.
 */
abstract class ActivityNotification extends Notification
{
    public function __construct(protected User $actor) {}

    /**
     * The delivery channels: the stored history and the browser push.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * A stable machine readable key, used by the frontend to pick an icon.
     */
    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function body(): string;

    /**
     * The in-app destination opened when the notification is clicked.
     */
    abstract public function url(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'actor' => [
                'id' => $this->actor->id,
                'username' => $this->actor->username,
                'picture' => $this->actor->picture,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
        ];
    }
}
