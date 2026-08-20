<?php

namespace App\Support;

/**
 * The starting text for each kind of campaign.
 *
 * An empty editor is the reason most announcements never get written, and a
 * single generic template is the reason they all sound the same. One per
 * purpose, each saying something specific, all of them editable afterwards:
 * these are a starting point, not a format.
 */
class CampaignTemplate
{
    /** Ce que l'administrateur peut vouloir dire. */
    public const PURPOSES = [
        'announcement' => 'Annonce',
        'promotion' => 'Mise en avant',
        'feedback' => 'Demande de retour',
        'opinion' => 'Demande d\'avis',
        'reactivation' => 'Reprise de contact',
    ];

    /**
     * Placeholders replaced per recipient at send time.
     *
     * @var array<string, string>
     */
    public const PLACEHOLDERS = [
        '{{pseudo}}' => 'Le pseudo du membre',
        '{{nom}}' => 'Son nom affiché, ou son pseudo à défaut',
        '{{site}}' => 'Le nom de la communauté',
    ];

    /**
     * @return array{subject: string, body: string}
     */
    public static function get(string $purpose): array
    {
        return self::all()[$purpose] ?? self::all()['announcement'];
    }

    /**
     * @return array<string, array{subject: string, body: string}>
     */
    public static function all(): array
    {
        return [
            'announcement' => [
                'subject' => 'Du nouveau sur {{site}}',
                'body' => '<p>Bonjour {{pseudo}},</p>'
                    .'<p>Une nouveauté vient d\'arriver sur {{site}} et on voulait '
                    .'que tu sois au courant.</p>'
                    .'<h3>Ce qui change</h3>'
                    .'<p>Décris ici ce qui est nouveau, en une ou deux phrases. '
                    .'Le plus utile est de dire ce que ça permet de faire, pas '
                    .'comment c\'est construit.</p>'
                    .'<h3>Comment en profiter</h3>'
                    .'<p>Dis où aller et quoi cliquer. Une seule action par message.</p>'
                    .'<p>À bientôt sur {{site}}.</p>',
            ],
            'promotion' => [
                'subject' => 'À découvrir cette semaine sur {{site}}',
                'body' => '<p>Bonjour {{pseudo}},</p>'
                    .'<p>Voici ce qui fait parler la communauté en ce moment.</p>'
                    .'<h3>La discussion du moment</h3>'
                    .'<p>Un ou deux avis qui méritent d\'être lus, avec pourquoi. '
                    .'Un lien vers chacun.</p>'
                    .'<h3>Un sujet à suivre</h3>'
                    .'<p>Le fil thématique qui se remplit le plus vite en ce moment.</p>'
                    .'<p>Bonne lecture.</p>',
            ],
            'feedback' => [
                'subject' => 'Deux minutes pour nous dire ce qui cloche ?',
                'body' => '<p>Bonjour {{pseudo}},</p>'
                    .'<p>Tu utilises {{site}} depuis un moment, et ton avis nous '
                    .'serait vraiment utile.</p>'
                    .'<h3>Une seule question</h3>'
                    .'<p>Qu\'est-ce qui t\'agace le plus quand tu utilises la '
                    .'plateforme ? Réponds directement à cet email, même en une '
                    .'ligne. On lit tout.</p>'
                    .'<p>Merci pour le temps que tu y consacres.</p>',
            ],
            'opinion' => [
                'subject' => 'Ton avis sur une décision à prendre',
                'body' => '<p>Bonjour {{pseudo}},</p>'
                    .'<p>On hésite sur un point, et ça concerne assez tout le monde '
                    .'pour qu\'on préfère demander.</p>'
                    .'<h3>La question</h3>'
                    .'<p>Pose ici la question, avec les options envisagées et ce '
                    .'que chacune impliquerait.</p>'
                    .'<h3>Pour répondre</h3>'
                    .'<p>Réponds à cet email, ou passe par le formulaire de '
                    .'suggestion sur le site.</p>'
                    .'<p>Merci d\'avance.</p>',
            ],
            'reactivation' => [
                'subject' => 'Ça fait un moment, {{pseudo}}',
                'body' => '<p>Bonjour {{pseudo}},</p>'
                    .'<p>On ne t\'a pas vu depuis quelque temps sur {{site}}, et il '
                    .'s\'est passé des choses entre-temps.</p>'
                    .'<h3>Depuis ton dernier passage</h3>'
                    .'<p>Ce qui a changé, en trois points au maximum.</p>'
                    .'<p>Si tu ne souhaites plus recevoir ces messages, le lien de '
                    .'désinscription est en bas de cet email. Aucun souci.</p>',
            ],
        ];
    }

    /**
     * Replace the placeholders for one recipient.
     */
    public static function render(string $texte, \App\Models\User $membre, string $site): string
    {
        return strtr($texte, [
            '{{pseudo}}' => $membre->username,
            '{{nom}}' => $membre->fullname ?: $membre->username,
            '{{site}}' => $site,
        ]);
    }
}
