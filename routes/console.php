<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('balls:remind')
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->dailyAt('08:30')
    ->withoutOverlapping(10);

Schedule::command('balls:remind')
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->dailyAt('13:00')
    ->withoutOverlapping(10);

Schedule::command('topics:remind')
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->dailyAt('08:30')
    ->withoutOverlapping(10);

Schedule::command('topics:remind')
    ->timezone('Asia/Tokyo')
    ->weekdays()
    ->dailyAt('13:00')
    ->withoutOverlapping(10);
