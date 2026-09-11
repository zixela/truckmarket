<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps public/sitemap.xml current with new/updated listings (cron runs schedule:run every minute).
Schedule::command('sitemap:generate')->hourly();
