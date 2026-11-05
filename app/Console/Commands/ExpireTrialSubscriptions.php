<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Console\Command;

class ExpireTrialSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Expire tenants whose trial period has ended';

    public function handle(TenantRepositoryInterface $tenants): int
    {
        $expired = $tenants->expireOverdueTrials();

        $this->info("Expired {$expired} tenant(s) with overdue trials.");

        return self::SUCCESS;
    }
}
