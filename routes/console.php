<?php

use App\Jobs\SyncFollowedMangasJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncFollowedMangasJob)->dailyAt('04:00');
