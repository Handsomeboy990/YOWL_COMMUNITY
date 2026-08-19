<?php

namespace App\Console\Commands;

use App\Models\ActivityPing;
use Illuminate\Console\Command;

class PruneActivityPings extends Command
{
    protected $signature = 'yowl:prune-pings {--days=120}';

    protected $description = 'Delete presence pings older than the retention window';

    /**
     * One row per member per minute of presence adds up fast, and nothing
     * reads past the last cohort window. Keeping four months covers D30 for
     * every cohort still on the dashboard.
     */
    public function handle(): int
    {
        $limite = now()->subDays((int) $this->option('days'));
        $supprimes = ActivityPing::where('pinged_at', '<', $limite)->delete();

        $this->info($supprimes.' signal(aux) de presence supprime(s).');

        return self::SUCCESS;
    }
}
