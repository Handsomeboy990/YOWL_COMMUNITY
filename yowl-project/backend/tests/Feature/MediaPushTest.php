<?php

namespace Tests\Feature;

use Database\Seeders\MassCommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Lignes distantes et images locales : le défaut le plus silencieux du seed.
 *
 * Lancé depuis une machine de développement contre la base de production, il
 * écrivait ses lignes chez l'hébergeur et ses images ici. Le site affichait
 * des cadres vides, sans rien dans les journaux ni dans la console.
 */
class MediaPushTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Décrit la configuration sans changer de connexion.
     *
     * Basculer database.default ferait migrer RefreshDatabase sur une base
     * qui n'existe pas ici : seuls le pilote et l'hôte de la connexion en
     * cours sont réécrits.
     */
    private function simuler(string $media, string $pilote, string $hote): void
    {
        $connexion = config('database.default');

        config([
            'filesystems.media' => $media,
            'database.connections.'.$connexion.'.driver' => $pilote,
            'database.connections.'.$connexion.'.host' => $hote,
        ]);
    }

    /**
     * Vérifie qu'un pilote sqlite reste considéré comme local, quel que soit
     * ce que traîne la clé host : c'est un fichier, pas un serveur.
     */
    public function test_a_sqlite_file_is_never_treated_as_remote(): void
    {
        $this->simuler(media: 'public', pilote: 'sqlite', hote: 'ep-lucky-tooth.eu-west-2.aws.neon.tech');

        $this->assertTrue($this->garde());
    }

    private function garde(): bool
    {
        $methode = new ReflectionMethod(MassCommunitySeeder::class, 'lesImagesIrontAuBonEndroit');

        return $methode->invoke(new MassCommunitySeeder);
    }

    public function test_the_seeder_refuses_a_remote_database_with_a_local_media_disk(): void
    {
        $this->simuler(media: 'public', pilote: 'pgsql', hote: 'ep-lucky-tooth.eu-west-2.aws.neon.tech');

        $this->assertFalse($this->garde());
    }

    public function test_the_seeder_proceeds_when_both_sides_are_local(): void
    {
        $this->simuler(media: 'public', pilote: 'pgsql', hote: '127.0.0.1');

        $this->assertTrue($this->garde());
    }

    public function test_the_seeder_proceeds_when_media_goes_to_object_storage(): void
    {
        $this->simuler(media: 's3', pilote: 'pgsql', hote: 'ep-lucky-tooth.eu-west-2.aws.neon.tech');

        $this->assertTrue($this->garde());
    }

    public function test_pushing_copies_what_is_missing_and_leaves_the_rest_alone(): void
    {
        $source = Storage::fake('public');
        $cible = Storage::fake('s3');

        $source->put('seed/yowl-illu-0.jpg', 'première image');
        $source->put('seed/avatars/yowl-avatar-0.jpg', 'un avatar');
        $source->put('autre/hors-perimetre.jpg', 'ne doit pas bouger');

        // Déjà présent à destination, avec un contenu différent : la commande
        // ne doit pas l'écraser, sinon relancer après une coupure réécrirait
        // tout ce qui a déjà été envoyé.
        $cible->put('seed/yowl-illu-0.jpg', 'déjà en place');

        $this->artisan('yowl:media-push', ['--source' => 'public', '--target' => 's3', '--prefix' => 'seed'])
            ->assertSuccessful();

        $this->assertSame('déjà en place', $cible->get('seed/yowl-illu-0.jpg'));
        $this->assertSame('un avatar', $cible->get('seed/avatars/yowl-avatar-0.jpg'));
        $this->assertFalse($cible->exists('autre/hors-perimetre.jpg'));

        // Rien n'est retiré du disque de départ.
        $this->assertTrue($source->exists('seed/yowl-illu-0.jpg'));
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $source = Storage::fake('public');
        $cible = Storage::fake('s3');
        $source->put('seed/yowl-illu-0.jpg', 'une image');

        $this->artisan('yowl:media-push', [
            '--source' => 'public', '--target' => 's3', '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertFalse($cible->exists('seed/yowl-illu-0.jpg'));
    }

    public function test_the_same_disk_on_both_sides_is_refused(): void
    {
        $this->artisan('yowl:media-push', ['--source' => 'public', '--target' => 'public'])
            ->expectsOutputToContain('les mêmes')
            ->assertFailed();
    }
}
