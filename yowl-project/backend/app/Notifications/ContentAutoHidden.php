<?php

namespace App\Notifications;

use App\Models\User;

/**
 * Tells an author that their content left the feed pending a decision.
 *
 * Hiding without saying so reads as a bug, and leaves nobody a way to
 * contest it. The message names the reason and points at the recourse.
 */
class ContentAutoHidden extends ActivityNotification
{
    public function __construct(User $actor, private int $reviewId, private int $reports)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'auto_hidden';
    }

    public function title(): string
    {
        return 'Ton avis a été retiré du fil';
    }

    public function body(): string
    {
        return "Il a reçu {$this->reports} signalements et attend la décision d'un modérateur. "
            .'Écris-nous par le formulaire de suggestion si tu penses que c\'est une erreur.';
    }

    public function url(): string
    {
        return '/reviews/'.$this->reviewId;
    }
}
