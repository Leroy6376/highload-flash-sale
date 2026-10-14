<?php

declare(strict_types=1);

use App\Domain\Orders\Actions\ExpireReservationsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn (ExpireReservationsAction $expireReservations) => $expireReservations->handle())->everyMinute();
