<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;

class CommentLiked extends ActivityNotification
{
    public function __construct(User $actor, private Comment $comment)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'comment_like';
    }

    public function title(): string
    {
        return 'Nouvelle réaction';
    }

    public function body(): string
    {
        return "{$this->actor->username} a aimé ton commentaire.";
    }

    public function url(): string
    {
        return '/reviews/'.$this->comment->review_id;
    }
}
