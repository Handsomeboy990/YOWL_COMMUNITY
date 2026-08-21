<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        /*
         * Mailjet par son API HTTPS.
         *
         * Le port 443 n'est jamais filtre, contrairement aux ports SMTP que
         * beaucoup d'offres gratuites ferment pour decourager le spam. Voir
         * App\Mail\Transport\MailjetTransport.
         */
        'mailjet' => [
            'transport' => 'mailjet',
            'key' => env('MAILJET_API_KEY'),
            'secret' => env('MAILJET_SECRET_KEY'),
            'timeout' => (int) (env('MAIL_TIMEOUT') ?: 10),
        ],

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            /*
             * Délai d'attente du relais, en secondes.
             *
             * À null, Symfony s'en remet à default_socket_timeout, soit
             * soixante secondes chez PHP. Un relais qui ne répond pas, parce
             * que le port est filtré ou l'hôte injoignable, ne provoque alors
             * aucune erreur : la requête reste suspendue jusqu'à ce que le
             * serveur web renonce et réponde 504. L'inscription se terminait
             * ainsi sur une passerelle expirée, avec le compte déjà créé, et
             * le filet de MailDelivery n'avait jamais l'occasion de servir.
             *
             * Dix secondes laissent le temps à un relais lent de répondre,
             * tout en échouant bien avant la limite d'un hébergeur.
             */
            'timeout' => (int) (env('MAIL_TIMEOUT') ?: 10),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Expediteur
    |--------------------------------------------------------------------------
    |
    | Le repli passe par ?: et non par le second argument de env(). Une
    | variable declaree vide, ce que produit un champ laisse blanc dans le
    | tableau de bord d'un hebergeur, rend une chaine vide et non null : le
    | defaut de env() ne s'applique pas, l'email part sans en-tete From, et
    | la couche Mime leve une exception au moment de la construction.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS') ?: 'hello@example.com',
        'name' => env('MAIL_FROM_NAME') ?: 'Example',
    ],

];
