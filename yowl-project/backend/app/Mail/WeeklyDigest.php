<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  \Illuminate\Support\Collection  $reviews
     * @param  array<string, int>  $activity
     */
    public function __construct(
        public User $user,
        public $reviews,
        public array $activity,
        public string $unsubscribeUrl,
    ) {}

    public function build(): self
    {
        $subject = $this->reviews->isNotEmpty()
            ? 'Ce que tu as raté cette semaine sur YOWL'
            : 'Ta semaine sur YOWL';

        return $this->subject($subject)
            ->view('emails.weekly-digest')
            ->text('emails.weekly-digest-texte')
            // Un client mail sérieux propose le désabonnement d'un clic,
            // sans ouvrir le message.
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrl.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
