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
        ],
    ];

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
