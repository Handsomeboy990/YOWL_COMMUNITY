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
