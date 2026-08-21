<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

/**
 * Envoi par l'API HTTPS de Mailjet, et non par SMTP.
 *
 * La raison est le réseau, pas le fournisseur. Les offres gratuites de
 * beaucoup d'hébergeurs filtrent les ports sortants 25, 465 et 587 pour
 * décourager le spam. Un port filtré n'est pas refusé : il absorbe la
 * connexion sans jamais répondre, la requête reste suspendue jusqu'à ce que
 * le serveur web renonce, et l'écran affiche une panne dont rien n'indique la
 * cause. Changer de fournisseur SMTP n'y change rien, ils sont tous derrière
 * les mêmes ports.
 *
 * Le port 443, lui, n'est jamais filtré : un hébergeur qui le fermerait
 * couperait son propre service.
 *
 * Mailjet accepte par ailleurs un expéditeur validé à l'adresse, sans exiger
 * la propriété d'un domaine, ce qui est la contrainte du moment.
 */
class MailjetTransport extends AbstractTransport
{
    private const POINT_DE_TERMINAISON = 'https://api.mailjet.com/v3.1/send';

    public function __construct(
        private readonly string $cle,
        private readonly string $secret,
        private readonly int $delai = 10,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->cle === '' || $this->secret === '') {
            throw new TransportException(
                'Mailjet : MAILJET_API_KEY ou MAILJET_SECRET_KEY est absent.'
            );
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            $reponse = Http::withBasicAuth($this->cle, $this->secret)
                ->timeout($this->delai)
                ->acceptJson()
                ->post(self::POINT_DE_TERMINAISON, ['Messages' => [$this->composer($email)]]);
        } catch (Throwable $exception) {
            // Toute panne de transport est rhabillée en exception que la
            // couche mail reconnaît, sinon elle traverse l'application
            // jusqu'à la réponse HTTP.
            throw new TransportException('Mailjet injoignable : '.$exception->getMessage(), 0, $exception);
        }

        if ($reponse->successful()) {
            return;
        }

        // Le corps de la réponse porte la vraie raison, souvent un expéditeur
        // non validé. Sans lui, le journal ne dit que « échec ».
        throw new TransportException(
            'Mailjet a refusé le message ('.$reponse->status().') : '.mb_substr($reponse->body(), 0, 400)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function composer(Email $email): array
    {
        $expediteur = $email->getFrom()[0] ?? null;

        if (! $expediteur) {
            throw new TransportException('Mailjet : aucun expéditeur. Renseignez MAIL_FROM_ADDRESS.');
        }

        $charge = [
            'From' => $this->adresse($expediteur),
            'To' => array_map($this->adresse(...), $email->getTo()),
            'Subject' => $email->getSubject() ?? '',
        ];

        foreach (['Cc' => $email->getCc(), 'Bcc' => $email->getBcc(), 'ReplyTo' => $email->getReplyTo()] as $champ => $adresses) {
            if ($adresses) {
                // ReplyTo n'accepte qu'une adresse, les autres une liste.
                $charge[$champ] = $champ === 'ReplyTo'
                    ? $this->adresse($adresses[0])
                    : array_map($this->adresse(...), $adresses);
            }
        }

        if ($texte = $email->getTextBody()) {
            $charge['TextPart'] = $texte;
        }

        if ($html = $email->getHtmlBody()) {
            $charge['HTMLPart'] = $html;
        }

        foreach ($email->getAttachments() as $piece) {
            $entetes = $piece->getPreparedHeaders();
            $charge['Attachments'][] = [
                'ContentType' => $entetes->get('Content-Type')?->getBody() ?? 'application/octet-stream',
                'Filename' => $piece->getFilename() ?? 'piece-jointe',
                'Base64Content' => base64_encode($piece->getBody()),
            ];
        }

        return $charge;
    }

    /**
     * @return array{Email: string, Name?: string}
     */
    private function adresse(Address $adresse): array
    {
        $rendu = ['Email' => $adresse->getAddress()];

        if ($adresse->getName() !== '') {
            $rendu['Name'] = $adresse->getName();
        }

        return $rendu;
    }

    public function __toString(): string
    {
        return 'mailjet';
    }
}
