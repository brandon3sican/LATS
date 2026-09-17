<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\OneTimePassword;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup expired OTPs daily at midnight
Schedule::call(function () {
    OneTimePassword::where('expires_at', '<', now())
        ->where('used', false)
        ->delete();

    // Also clean up used OTPs older than 24 hours
    OneTimePassword::where('used', true)
        ->where('created_at', '<', now()->subHours(24))
        ->delete();
})->daily()->description('Cleanup expired OTPs');
