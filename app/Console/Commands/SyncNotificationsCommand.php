<?php

namespace App\Console\Commands;

use App\Domains\Notification\Services\NotificationSyncService;
use Illuminate\Console\Command;

class SyncNotificationsCommand extends Command
{
    protected $signature = 'notifications:sync';
    protected $description = 'Sync system notifications (overdue payments, expiring contracts, vacant units)';

    public function handle(NotificationSyncService $service): void
    {
        $service->sync();
        $this->info('Notifications synced successfully.');
    }
}
