<?php

namespace App\Notifications;

use App\Models\Appeal;
use App\Models\User;

/**
 * Tells a member what came of their appeal.
 *
 * An appeal nobody answers is worse than no appeal at all: it makes the
 * platform look like it listens when it does not.
 */
class AppealAnswered extends ActivityNotification
{
    public function __construct(User $actor, private Appeal $appeal)
    {
        parent::__construct($actor);
    }

    public function type(): string
    {
        return 'appeal';
    }

    public function title(): string
    {
        return $this->appeal->status === Appeal::STATUS_GRANTED
            ? 'Ton contenu est rétabli'
            : 'Réponse à ta contestation';
    }

    public function body(): string
    {
        return $this->appeal->response
            ?: ($this->appeal->status === Appeal::STATUS_GRANTED
                ? 'Après relecture, ton contenu a été remis en ligne.'
                : 'Après relecture, la décision de modération est maintenue.');
    }

    public function url(): string
    {
        return '/user/activity';
    }
}
