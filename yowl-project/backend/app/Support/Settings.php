<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Registre des reglages modifiables depuis l'administration.
 *
 * Les cles sont declarees ici, avec leur type, leur valeur par defaut et leur
 * regle de validation. Un administrateur choisit parmi ces cles : il ne peut
 * pas en inventer une, ni ecrire une valeur d'un autre type. Ce qui est
 * volontairement absent, c'est toute forme de code saisi depuis l'interface,
 * qui reviendrait a offrir une execution arbitraire a un compte compromis.
 */
class Settings
{
    private const CACHE_PREFIX = 'settings.all';

    /**
     * @var array<string, array{type: string, default: mixed, rules: string, label: string, group: string}>
     */
    public const REGISTRY = [
        'registration.open' => [
            'type' => 'bool',
            'default' => true,
            'rules' => 'boolean',
            'label' => 'Inscriptions ouvertes',
            'group' => 'Inscription',
        ],
        'registration.age_min' => [
            'type' => 'int',
            'default' => 13,
            'rules' => 'nullable|integer|min:13|max:120',
            'label' => 'Âge minimum à l\'inscription',
            'group' => 'Inscription',
        ],
        'registration.age_max' => [
            'type' => 'int',
            'default' => 35,
            'rules' => 'nullable|integer|min:13|max:120',
            'label' => 'Âge maximum à l\'inscription, vide pour aucune limite',
            'group' => 'Inscription',
        ],
        'registration.verification_grace' => [
            'type' => 'int',
            'default' => 10,
            'rules' => 'nullable|integer|min:0|max:100',
            'label' => "Connexions autorisées avant d'exiger la vérification de l'adresse",
            'group' => 'Inscription',
        ],
        'suggestions.open' => [
            'type' => 'bool',
            'default' => true,
            'rules' => 'boolean',
            'label' => 'Formulaire de suggestion ouvert',
            'group' => 'Communauté',
        ],
        'moderation.auto_hide_threshold' => [
            'type' => 'int',
            'default' => 3,
            'rules' => 'nullable|integer|min:2|max:50',
            'label' => 'Masquer un contenu au-delà de N signalements, vide pour ne jamais masquer',
            'group' => 'Modération',
        ],
        'reviews.require_approval' => [
            'type' => 'bool',
            'default' => false,
            'rules' => 'boolean',
            'label' => 'Les nouvelles reviews attendent une validation',
            'group' => 'Modération',
        ],
        'community.name' => [
            'type' => 'string',
            'default' => 'YOWL Community',
            'rules' => 'string|max:60',
            'label' => 'Nom affiché de la communauté',
            'group' => 'Communauté',
            'public' => true,
        ],

        // Identite visible. Ces valeurs sont lues sans authentification :
        // un visiteur voit le logo et le pied de page avant d'avoir un compte.
        'identity.logo' => [
            'type' => 'image',
            'default' => '',
            'rules' => 'nullable|string|max:255',
            'label' => 'Logo du site',
            'help' => 'Affiché dans la barre latérale et sur la page d\'accueil. Vide pour garder le logo fourni.',
            'group' => 'Identité',
            'public' => true,
        ],
        'identity.favicon' => [
            'type' => 'image',
            'default' => '',
            'rules' => 'nullable|string|max:255',
            'label' => 'Icône de l\'onglet',
            'help' => 'Carrée, 512 pixels de côté au minimum.',
            'group' => 'Identité',
            'public' => true,
        ],
        'identity.tagline' => [
            'type' => 'string',
            'default' => 'Ton avis sur le web, sans filtre',
            'rules' => 'nullable|string|max:120',
            'label' => 'Accroche',
            'help' => 'Une phrase, affichée sous le nom du site.',
            'group' => 'Identité',
            'public' => true,
        ],
        'identity.footer' => [
            'type' => 'string',
            'default' => '© 2026 YOWL — LONG Corp',
            'rules' => 'nullable|string|max:120',
            'label' => 'Mention de pied de page',
            'group' => 'Identité',
            'public' => true,
        ],
        'identity.contact_email' => [
            'type' => 'string',
            'default' => '',
            'rules' => 'nullable|email|max:120',
            'label' => 'Adresse de contact',
            'help' => 'Publiée dans les pages légales et utilisée comme réponse aux emails.',
            'group' => 'Identité',
            'public' => true,
        ],

        // Referencement. Ce que les moteurs et les reseaux sociaux affichent
        // quand une adresse du site est partagee.
        'seo.description' => [
            'type' => 'text',
            'default' => 'Partage ton avis sur n\'importe quel contenu du web et rejoins la conversation.',
            'rules' => 'nullable|string|max:160',
            'label' => 'Description du site',
            'help' => '160 caractères au maximum : au-delà, les moteurs coupent.',
            'group' => 'Référencement',
            'public' => true,
        ],
        'seo.share_image' => [
            'type' => 'image',
            'default' => '',
            'rules' => 'nullable|string|max:255',
            'label' => 'Image de partage',
            'help' => 'Affichée quand un lien du site est partagé. 1200 sur 630 pixels.',
            'group' => 'Référencement',
            'public' => true,
        ],
        'seo.indexable' => [
            'type' => 'bool',
            'default' => true,
            'rules' => 'boolean',
            'label' => 'Autoriser l\'indexation par les moteurs',
            'help' => 'À décocher tant que le site n\'est pas prêt à être trouvé.',
            'group' => 'Référencement',
            'public' => true,
        ],
    ];

    /**
     * The settings a visitor may read, with no account.
     *
     * The logo, the footer line and the sharing metadata are painted before
     * anybody signs in, so they cannot live behind the administration guard.
     * Everything else stays private by omission rather than by an allow list
     * kept somewhere else.
     */
    public static function publicValues(): array
    {
        $valeurs = self::all();
        $publiques = [];

        foreach (self::REGISTRY as $cle => $definition) {
            if (empty($definition['public'])) {
                continue;
            }

            $valeur = array_key_exists($cle, $valeurs)
                ? $valeurs[$cle]
                : $definition['default'];

            // Sortie imbriquee : identity.logo devient identity->logo. Le
            // point est un separateur de chemin pour a peu pres tout ce qui
            // lira ce document, du client JavaScript aux assertions de test.
            [$groupe, $nom] = explode('.', $cle, 2);
            $publiques[$groupe][$nom] = $valeur;
        }

        return $publiques;
    }

    /**
     * The cache key, versioned by the shape of the registry.
     *
     * The entry used to be cached for ever under a fixed key. Adding a
     * setting therefore left a stale array in the cache that did not contain
     * the new key, and the console crashed on an undefined index. Deriving the
     * key from the declared keys means a registry change invalidates itself.
     */
    private static function cacheKey(): string
    {
        return self::CACHE_PREFIX.'.'.substr(md5(implode('|', array_keys(self::REGISTRY))), 0, 8);
    }

    /**
     * Read one setting, falling back to its declared default.
     */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        if (! isset(self::REGISTRY[$key])) {
            return $fallback;
        }

        $stored = self::all();

        // array_key_exists et non ?? : une borne mise a vide vaut null, ce
        // qui signifie "aucune limite" et non "reprends la valeur par defaut".
        return array_key_exists($key, $stored)
            ? $stored[$key]
            : self::REGISTRY[$key]['default'];
    }

    /**
     * Every setting, stored values merged over the declared defaults.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::cacheKey(), function () {
            $stored = Setting::pluck('value', 'key')->all();

            $resolved = [];
            foreach (self::REGISTRY as $key => $definition) {
                $resolved[$key] = array_key_exists($key, $stored)
                    ? self::cast($stored[$key], $definition['type'])
                    : $definition['default'];
            }

            return $resolved;
        });
    }

    /**
     * Write one setting. Unknown keys are refused rather than stored.
     */
    public static function set(string $key, mixed $value): void
    {
        if (! isset(self::REGISTRY[$key])) {
            return;
        }

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) (is_bool($value) ? (int) $value : $value)]
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * Turn the stored string back into its declared type.
     */
    private static function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool' => (bool) (int) $value,
            'int' => (int) $value,
            default => $value,
        };
    }
}
