<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;

class CommentReceived extends ActivityNotification
{
    public function __construct(User $actor, private Comment $comment)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'comment';
    }

    public function title(): string
    {
        return 'Nouveau commentaire';
    }

    public function body(): string
    {
        return "{$this->actor->username} a commenté ta review.";
    }

    public function url(): string
    {
        return '/reviews/'.$this->comment->review_id;
    }
}
