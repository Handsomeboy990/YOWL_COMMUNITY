<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Le score decroit avec le temps : il se recalcule meme sans nouvelle activite.
Schedule::command('yowl:refresh-scores')->hourly();

// Resume hebdomadaire, le lundi matin.
Schedule::command('yowl:send-digest')->weeklyOn(1, '09:00');

// Toutes les cinq minutes : l'heure choisie par un auteur est tenue a cinq
// minutes pres, ce qui est la precision qu'une interface de programmation
// laisse esperer.
Schedule::command('yowl:publish-scheduled')->everyFiveMinutes()->withoutOverlapping();

// Les signaux de presence au dela de la fenetre des cohortes ne sont plus lus.
Schedule::command('yowl:prune-pings')->weeklyOn(0, '03:00');
