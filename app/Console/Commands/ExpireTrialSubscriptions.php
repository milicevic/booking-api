<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ExpireTrialSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Expire tenants whose trial period has ended';

    public function handle(): int
    {
        $expired = Tenant::where('subscription_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->update(['subscription_status' => 'expired']);

        $this->info("Expired {$expired} tenant(s) with overdue trials.");

        return self::SUCCESS;
    }
}
