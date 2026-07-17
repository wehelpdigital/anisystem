<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckSubscriptions extends Command
{
    protected $signature = 'anisystem:check-subscriptions';

    protected $description = 'Sync pending subscriptions against mother-system orders, persist expirations, and send expiry notices';

    public function handle(SubscriptionService $service): int
    {
        $result = $service->runMaintenance();

        $this->info(sprintf(
            'Synced %d users — %d expired, %d expiry notices sent.',
            $result['synced'],
            $result['expired'],
            $result['notified'],
        ));

        return self::SUCCESS;
    }
}
