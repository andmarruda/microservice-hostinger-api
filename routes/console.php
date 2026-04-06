<?php

use App\Jobs\ExpireInvitations;
use App\Jobs\FlagStaleAccessGrants;
use App\Jobs\PruneAuditLogs;
use App\Jobs\WarmHostingerCache;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending invitations that have passed their deadline.
Schedule::job(new ExpireInvitations)->hourly()->withoutOverlapping(10);

// Warm the Hostinger read-only cache so users never hit a cold cache.
Schedule::job(new WarmHostingerCache)->dailyAt('03:00')->withoutOverlapping(10);

// Remove audit log entries older than the configured retention window.
Schedule::job(new PruneAuditLogs)->dailyAt('02:00')->withoutOverlapping(10);

// Flag access grants whose VPS no longer exists in Hostinger.
Schedule::job(new FlagStaleAccessGrants)->dailyAt('04:00')->withoutOverlapping(10);
