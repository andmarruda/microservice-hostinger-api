<?php

use App\Jobs\ExpireAccessGrants;
use App\Jobs\ExpireInvitations;
use App\Jobs\FlagStaleAccessGrants;
use App\Jobs\PruneAuditLogs;
use App\Jobs\WarmHostingerCache;
use App\Modules\DriftModule\Jobs\ArchiveOldDriftReports;
use App\Modules\DriftModule\Jobs\RunDriftScan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending invitations that have passed their deadline.
Schedule::job(new ExpireInvitations)->hourly()->withoutOverlapping(10);

// Remove VPS access grants and security permissions that have passed their expiry date.
Schedule::job(new ExpireAccessGrants)->hourly()->withoutOverlapping(10);

// Warm the Hostinger read-only cache so users never hit a cold cache.
Schedule::job(new WarmHostingerCache)->dailyAt('03:00')->withoutOverlapping(10);

// Remove audit log entries older than the configured retention window.
Schedule::job(new PruneAuditLogs)->dailyAt('02:00')->withoutOverlapping(10);

// Flag access grants whose VPS no longer exists in Hostinger.
Schedule::job(new FlagStaleAccessGrants)->dailyAt('04:00')->withoutOverlapping(10);

// Scan for configuration drift between Hostinger and local records.
Schedule::job(new RunDriftScan)->dailyAt('04:30')->withoutOverlapping(10);

// Archive resolved/dismissed drift reports older than the retention window.
Schedule::job(new ArchiveOldDriftReports)->dailyAt('03:30')->withoutOverlapping(10);
