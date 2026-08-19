<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_declared_setting_is_readable(): void
    {
        // La console lit chaque cle du registre : aucune ne doit manquer,
        // sinon la page tombe sur un index indefini.
        $values = Settings::all();

        foreach (array_keys(Settings::REGISTRY) as $key) {
            $this->assertArrayHasKey($key, $values, $key.' absent des réglages');
        }
    }

    public function test_a_stale_cache_entry_cannot_break_a_new_setting(): void
    {
        // On simule le cache d'avant l'ajout d'un reglage.
        Settings::all();
        Cache::put('settings.all', ['registration.open' => true]);

        $this->assertNotNull(Settings::get('moderation.auto_hide_threshold'));
        $this->assertArrayHasKey('moderation.auto_hide_threshold', Settings::all());
    }

    public function test_a_bound_set_to_empty_stays_empty(): void
    {
        Settings::set('registration.age_max', null);

        // Vide veut dire aucune limite, pas retour a la valeur par defaut.
        $this->assertNull(Settings::get('registration.age_max'));
    }

    public function test_an_unknown_key_returns_the_fallback(): void
    {
        $this->assertSame('secours', Settings::get('cle.inexistante', 'secours'));
    }
}
