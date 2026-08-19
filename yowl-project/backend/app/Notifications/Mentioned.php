<?php

namespace App\Notifications;

use App\Models\User;

class Mentioned extends ActivityNotification
{
    public function __construct(User $actor, private int $reviewId, private string $where)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'mention';
    }

    public function title(): string
    {
        return 'Tu as été mentionné';
    }

    public function body(): string
    {
        return "{$this->actor->username} t'a mentionné dans un {$this->where}.";
    }

    public function url(): string
    {
        return '/reviews/'.$this->reviewId;
    }
}
