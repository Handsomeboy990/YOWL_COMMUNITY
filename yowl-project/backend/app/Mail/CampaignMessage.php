<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $subjectLine,
        public string $bodyHtml,
        public string $unsubscribeUrl,
        public string $siteName,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.campaign')
            // Le desabonnement d'un clic, sans ouvrir le message. Sans cet
            // en-tete, les clients serieux classent l'envoi en indesirable.
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrl.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
