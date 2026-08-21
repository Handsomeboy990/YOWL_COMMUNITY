<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sending an email that fails must not look like a broken application.
 *
 * With MAIL_MAILER=smtp and an unreachable relay, Symfony throws and the
 * request answers 500 with the word "Server Error". A visitor learns nothing,
 * and neither does the operator: the message names no variable and points at
 * no configuration. Registration failed outright, so nobody could even create
 * an account.
 *
 * A transport failure is our problem, not the visitor's. It is logged with
 * what it takes to fix it, and the caller decides what to answer.
 */
class MailDelivery
{
    /**
     * Run a send, and say whether it went out.
     *
     * @param  callable  $envoi  the actual sending
     * @param  array<string, mixed>  $contexte  what the logs need to be useful
     */
    public static function attempt(callable $envoi, array $contexte = []): bool
    {
        try {
            $envoi();

            return true;
        } catch (Throwable $exception) {
            // Toute exception, et non les seules erreurs de transport.
            //
            // Un email peut échouer avant même de partir : sans en-tête From,
            // la couche Mime lève une LogicException, qui n'appartient pas à
            // la famille des erreurs de transport. Elle échappait donc à ce
            // filet et emportait la requête entière. À l'inscription, cela
            // donnait un compte créé, une réponse en erreur, et personne pour
            // comprendre lequel des deux croire.
            //
            // Le nom de la classe part au journal : c'est lui qui distingue
            // un relais injoignable d'un email mal formé, et les deux ne se
            // corrigent pas au même endroit.
            Log::error("L'email n'a pas pu être remis.", $contexte + [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'expediteur' => config('mail.from.address') ?: 'NON RENSEIGNE',
                'indice' => 'Vérifie MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD '
                    .'et MAIL_FROM_ADDRESS, qui ne doit pas être vide. '
                    .'MAIL_MAILER=log écrit dans les journaux sans rien envoyer.',
            ]);

            return false;
        }
    }

    /**
     * The answer to give when the message could not leave.
     *
     * 503 rather than 500: the request was valid, the service is momentarily
     * unable to honour it, and that distinction is the whole difference
     * between "you did something wrong" and "come back in a minute".
     */
    public static function unavailable(string $quoi): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "L'envoi de {$quoi} est momentanément impossible. "
                .'Réessaie dans quelques minutes.',
        ], 503);
    }
}
