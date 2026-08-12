<?php

namespace App\Notifications;

use App\Models\Review;
use App\Models\User;

class ReviewLiked extends ActivityNotification
{
    public function __construct(User $actor, private Review $review)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'review_like';
    }

    public function title(): string
    {
        return 'Nouvelle réaction';
    }

    public function body(): string
    {
        return "{$this->actor->username} a aimé ta review.";
    }

    public function url(): string
    {
        return '/reviews/'.$this->review->id;
    }
}
