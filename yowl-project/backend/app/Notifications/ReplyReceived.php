<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;

class ReplyReceived extends ActivityNotification
{
    public function __construct(User $actor, private Comment $comment)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'reply';
    }

    public function title(): string
    {
        return 'Nouvelle réponse';
    }

    public function body(): string
    {
        return "{$this->actor->username} a répondu à ton commentaire.";
    }

    public function url(): string
    {
        return '/reviews/'.$this->comment->review_id;
    }
}
