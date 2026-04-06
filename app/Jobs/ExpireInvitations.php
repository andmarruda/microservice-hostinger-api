<?php

namespace App\Jobs;

use App\Modules\AuthModule\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireInvitations implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Invitation::whereNull('accepted_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
