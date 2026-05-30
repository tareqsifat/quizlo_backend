<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Modules\League\Jobs\ResetWeeklyLeague;
use App\Modules\League\Jobs\ProcessWeeklyLeaguePromotion;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quizlo:reset-weekly-league', function () {
    ResetWeeklyLeague::dispatchSync();
    $this->info('Weekly league reset successfully.');
})->purpose('Reset weekly league and open new season');

Artisan::command('quizlo:process-league-promotion {--season=}', function () {
    $seasonId = $this->option('season');
    ProcessWeeklyLeaguePromotion::dispatchSync($seasonId ? (int) $seasonId : null);
    $this->info('League promotions processed successfully.');
})->purpose('Process weekly league promotions');

Artisan::command('quizlo:reset-daily-goals', function () {
    $this->info('Daily goals reset successfully.');
})->purpose('Reset daily goals');
