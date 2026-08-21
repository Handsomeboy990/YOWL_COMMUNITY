<?php

namespace App\Console\Commands;

use App\Models\ActivityPing;
use App\Models\PageVisit;
use Illuminate\Console\Command;

class PruneActivityPings extends Command
{
    protected $signature = 'yowl:prune-pings {--days=120}';

    protected $description = 'Delete presence pings and audience visits older than the retention window';

    /**
     * Deux mesures brutes, une seule règle de conservation.
     *
     * Un signal de présence par membre et par minute, une ligne par page
     * consultée : les deux tables grossissent vite et rien ne les relit
     * au-delà de la fenêtre affichée. Quatre mois couvrent le J+30 de chaque
     * cohorte encore au tableau de bord, et la plus large fenêtre d'audience,
     * qui est de quatre-vingt-dix jours.
     *
     * Conserver au-delà n'apporte rien et va contre le principe de
     * minimisation : une donnée que personne ne lit n'a pas à être gardée.
     *
     * Le nom de la commande n'a pas changé alors qu'elle fait désormais deux
     * choses : il sert de clé aux marqueurs de fraîcheur du réveil planifié,
     * et le renommer remettrait ces marqueurs à zéro pour rien.
     */
    public function handle(): int
    {
        $limite = now()->subDays((int) $this->option('days'));

        $signaux = ActivityPing::where('pinged_at', '<', $limite)->delete();
        $visites = PageVisit::where('visited_at', '<', $limite)->delete();

        $this->info($signaux.' signal(aux) de presence supprime(s).');
        $this->info($visites.' visite(s) supprimee(s).');

        return self::SUCCESS;
    }
}
