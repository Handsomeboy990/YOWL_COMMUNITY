<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    /**
     * Une version texte accompagne le HTML, et ce n'est pas une politesse.
     *
     * Un message transactionnel qui ne contient que du HTML est un signal de
     * courrier indésirable pour la plupart des filtres, et ce message-ci
     * arrivait mal alors que la réinitialisation de mot de passe, construite
     * par Laravel avec ses deux versions, arrivait normalement. Les deux
     * partent pourtant par le même relais.
     */
    public function build(): self
    {
        return $this->subject('Votre code de vérification')
            ->view('emails.verify-code')
            ->text('emails.verify-code-texte')
            ->with(['code' => $this->code]);
    }
}
