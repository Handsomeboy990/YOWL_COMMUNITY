<?php

namespace App\Console\Commands;

use Database\Seeders\LegalPageSeeder;
use Illuminate\Console\Command;

class SeedSitePages extends Command
{
    protected $signature = 'yowl:seed-pages {--reset : Rétablir le texte livré, en écrasant les modifications}';

    protected $description = 'Create the six editable site pages that are missing';

    /**
     * The six pages the site links to from its own footer.
     *
     * A fresh deployment migrates its tables but seeds nothing, so À propos,
     * la FAQ, la charte, la confidentialité, les conditions et les mentions
     * légales répondaient 404 alors que le pied de page y renvoie.
     *
     * Runs at every container start, and creates only what is missing: an
     * administrator's edits survive a restart. --reset is the way back to the
     * shipped text, and it says what it will do first.
     */
    public function handle(): int
    {
        if ($this->option('reset')) {
            $this->warn('Les six pages vont être rétablies dans leur texte livré.');
            $this->warn('Toute modification faite depuis la console sera perdue.');

            if (! $this->option('no-interaction') && ! $this->confirm('Continuer ?', false)) {
                $this->info('Rien n\'a été touché.');

                return self::SUCCESS;
            }

            LegalPageSeeder::$reinitialiser = true;
        }

        $this->callSilent('db:seed', ['--class' => LegalPageSeeder::class, '--force' => true]);

        LegalPageSeeder::$reinitialiser = false;

        $this->info('Pages du site à jour.');

        return self::SUCCESS;
    }
}
