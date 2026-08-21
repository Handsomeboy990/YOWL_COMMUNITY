<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Recopie des fichiers d'un disque vers un autre.
 *
 * Le cas qui l'a rendue nécessaire : un seed lancé depuis une machine de
 * développement écrit ses lignes dans la base de production, mais ses images
 * sur le disque configuré localement. La base référence alors des chemins que
 * le stockage de production ne contient pas, et le site affiche des cadres
 * vides sans la moindre erreur, ni côté serveur ni dans la console.
 *
 * Rien n'est supprimé, et un fichier déjà présent à destination n'est pas
 * réécrit : la commande peut être relancée après une coupure.
 */
class PushMedia extends Command
{
    protected $signature = 'yowl:media-push
        {--source=public : disque de départ}
        {--target= : disque d\'arrivée, par défaut le disque media configuré}
        {--prefix= : ne traiter que ce dossier, par exemple seed}
        {--dry-run : lister ce qui serait envoyé, sans rien écrire}';

    protected $description = 'Copy media files from one disk to another, skipping what is already there';

    public function handle(): int
    {
        $sourceNom = (string) $this->option('source');
        $cibleNom = (string) ($this->option('target') ?: config('filesystems.media'));

        if ($sourceNom === $cibleNom) {
            $this->error('Le disque de départ et celui d\'arrivée sont les mêmes ('.$sourceNom.').');
            $this->line('  Renseignez --target, par exemple : --target=s3');

            return self::FAILURE;
        }

        try {
            $source = Storage::disk($sourceNom);
            $cible = Storage::disk($cibleNom);
        } catch (Throwable $e) {
            $this->error('Disque introuvable : '.$e->getMessage());

            return self::FAILURE;
        }

        $prefixe = trim((string) $this->option('prefix'), '/');
        $fichiers = $source->allFiles($prefixe);

        if (! $fichiers) {
            $this->warn('Aucun fichier sous « '.($prefixe ?: '/').' » sur le disque '.$sourceNom.'.');

            return self::SUCCESS;
        }

        $this->info(count($fichiers).' fichier(s) sur '.$sourceNom.', destination '.$cibleNom.'.');

        if ($this->option('dry-run')) {
            foreach ($fichiers as $fichier) {
                $this->line(($cible->exists($fichier) ? '  = ' : '  + ').$fichier);
            }
            $this->newLine();
            $this->comment('Essai à blanc : rien n\'a été écrit.');

            return self::SUCCESS;
        }

        $barre = $this->output->createProgressBar(count($fichiers));
        $envoyes = 0;
        $ignores = 0;
        $echoues = [];

        foreach ($fichiers as $fichier) {
            try {
                if ($cible->exists($fichier)) {
                    $ignores++;
                } else {
                    // Flux plutôt que contenu en mémoire : un fichier de
                    // plusieurs mégaoctets multiplié par la boucle épuiserait
                    // la limite mémoire de PHP bien avant la fin.
                    $flux = $source->readStream($fichier);
                    $cible->writeStream($fichier, $flux);
                    if (is_resource($flux)) {
                        fclose($flux);
                    }
                    $envoyes++;
                }
            } catch (Throwable $e) {
                $echoues[$fichier] = $e->getMessage();
            }

            $barre->advance();
        }

        $barre->finish();
        $this->newLine(2);

        $this->info($envoyes.' envoyé(s), '.$ignores.' déjà présent(s).');

        if ($echoues) {
            $this->newLine();
            $this->error(count($echoues).' échec(s) :');
            foreach (array_slice($echoues, 0, 10, true) as $fichier => $raison) {
                $this->line('  '.$fichier.' : '.$raison);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
