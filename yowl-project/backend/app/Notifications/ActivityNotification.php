<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
        return ['database', 'broadcast', WebPushChannel::class];
    }

    /**
     * Same payload as the stored row, pushed over the socket.
     *
     * The bell used to poll every sixty seconds per connected member. The
     * socket makes the counter move when something happens instead.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    // Pas de broadcastOn ici : Laravel diffuse par defaut sur le canal prive
    // du destinataire. Le redefinir envoyait la notification sur le canal de
    // celui qui l'a declenchee, c'est-a-dire a la mauvaise personne.

    public function broadcastType(): string
    {
        return $this->type();
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
