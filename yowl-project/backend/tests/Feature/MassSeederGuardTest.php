<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use Database\Seeders\MassCommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Le seed de démonstration n'est pas rejouable, et il doit le dire.
 *
 * Les pseudos sont dérivés du rang de chaque membre : une seconde exécution
 * régénère les mêmes adresses et bute sur la contrainte d'unicité, à
 * mi-parcours, après avoir téléchargé le fonds d'images. Rien ne le signalait
 * avant l'erreur SQL.
 */
class MassSeederGuardTest extends TestCase
{
    use RefreshDatabase;

    private function poserUnCompteDeDemonstration(string $pseudo): User
    {
        return User::factory()->create([
            'username' => $pseudo,
            'email' => $pseudo.'@yowl.test',
        ]);
    }

    public function test_the_seeder_refuses_to_run_over_an_existing_demonstration_set(): void
    {
        $this->poserUnCompteDeDemonstration('pixel_camille');

        $this->artisan('db:seed', ['--class' => MassCommunitySeeder::class])
            ->expectsOutputToContain('comptes de démonstration')
            ->expectsOutputToContain('YOWL_SEED_FRESH=1')
            ->assertSuccessful();

        // Le refus intervient avant toute écriture : rien n'a été ajouté.
        $this->assertSame(1, User::count());
    }

    public function test_the_environment_variable_opens_the_way_through(): void
    {
        $this->poserUnCompteDeDemonstration('pixel_camille');

        $garde = new ReflectionMethod(MassCommunitySeeder::class, 'laBaseEstPrete');
        $seeder = new MassCommunitySeeder;

        $this->assertFalse($garde->invoke($seeder), 'sans la variable, le seed doit refuser');

        putenv('YOWL_SEED_FRESH=1');
        $_ENV['YOWL_SEED_FRESH'] = '1';

        try {
            $this->assertTrue($garde->invoke($seeder), 'avec la variable, le seed doit passer');
            $this->assertSame(0, User::where('email', 'like', '%@yowl.test')->count());
        } finally {
            putenv('YOWL_SEED_FRESH');
            unset($_ENV['YOWL_SEED_FRESH']);
        }
    }

    public function test_the_purge_removes_the_demonstration_set_and_nothing_else(): void
    {
        $demo = $this->poserUnCompteDeDemonstration('pixel_camille');
        $avisDemo = Review::factory()->create(['user_id' => $demo->id]);
        Suggestion::create([
            'user_id' => $demo->id,
            'subject' => Suggestion::SUBJECTS[0],
            'message' => 'Suggestion venue du jeu de démonstration.',
            'status' => Suggestion::STATUSES[0],
        ]);

        // Un compte réel, avec du contenu, qui doit survivre intact.
        $vrai = User::factory()->create([
            'username' => 'handsomeboy',
            'email' => 'chachamike4@gmail.com',
        ]);
        $avisReel = Review::factory()->create(['user_id' => $vrai->id]);
        $suggestionReelle = Suggestion::create([
            'user_id' => $vrai->id,
            'subject' => Suggestion::SUBJECTS[0],
            'message' => 'Suggestion écrite par un vrai membre.',
            'status' => Suggestion::STATUSES[0],
        ]);

        $purge = new ReflectionMethod(MassCommunitySeeder::class, 'purgerLeSeedPrecedent');
        $purge->invoke(new MassCommunitySeeder, 1);

        $this->assertDatabaseMissing('users', ['id' => $demo->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $avisDemo->id]);
        // La clé est nullable : sans suppression explicite, la suggestion
        // survivrait à son auteur, orpheline et indiscernable d'une vraie.
        $this->assertDatabaseMissing('suggestions', ['user_id' => $demo->id]);

        $this->assertDatabaseHas('users', ['id' => $vrai->id]);
        $this->assertDatabaseHas('reviews', ['id' => $avisReel->id]);
        $this->assertDatabaseHas('suggestions', ['id' => $suggestionReelle->id, 'user_id' => $vrai->id]);
    }
}
