<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(\App\Console\Commands\ImportCustomersFromMinio::class)
        ->daily()
        //->everyMinute()
        ->appendOutputTo(storage_path('logs/import.log'));
