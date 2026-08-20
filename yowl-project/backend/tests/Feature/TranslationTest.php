<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Error messages must read as sentences, not as translation keys.
 *
 * Laravel 11 ships without a lang directory, so every __() call returned its
 * own key: a member submitting an empty form was told "validation.required",
 * and a wrong password answered "auth.failed". Both were visible in
 * production.
 */
class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public static function langues(): array
    {
        return ['français' => ['fr'], 'anglais' => ['en']];
    }

    /**
     * @dataProvider langues
     */
    public function test_a_failed_login_reads_as_a_sentence(string $langue): void
    {
        config(['app.locale' => $langue]);

        $message = $this->postJson('/api/login', [
            'email' => 'inexistant@exemple.fr',
            'password' => 'peu-importe',
        ])->assertStatus(422)->json('message');

        $this->assertStringNotContainsString('auth.', $message);
        $this->assertGreaterThan(15, strlen($message), 'Un message utile fait plus de quinze caractères.');
    }

    /**
     * @dataProvider langues
     */
    public function test_a_missing_field_reads_as_a_sentence(string $langue): void
    {
        config(['app.locale' => $langue]);

        $erreurs = $this->postJson('/api/register', ['email' => 'pas-un-email'])
            ->assertStatus(422)
            ->json('error');

        foreach ($erreurs as $champ => $messages) {
            foreach ($messages as $message) {
                $this->assertStringNotContainsString('validation.', $message, "Clé brute sur le champ {$champ}.");
            }
        }
    }

    public function test_field_names_are_readable_in_french(): void
    {
        config(['app.locale' => 'fr']);

        $erreurs = $this->postJson('/api/register', [])->assertStatus(422)->json('error');

        // Sans la table des attributs, le message parlerait de « birthdate ».
        $this->assertStringContainsString('date de naissance', $erreurs['birthdate'][0]);
        $this->assertStringNotContainsString('birthdate', $erreurs['birthdate'][0]);
    }

    public function test_the_api_root_answers_instead_of_failing(): void
    {
        // Ouvrir l'adresse de l'API pour vérifier qu'elle tourne rendait
        // « Server Error » : c'était la dernière route du groupe web.
        $this->getJson('/')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['service', 'status', 'site']);
    }
}
