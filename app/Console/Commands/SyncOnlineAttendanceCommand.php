<?php

namespace App\Console\Commands;

use App\Jobs\SyncOnlineAttendance;
use Illuminate\Console\Command;

class SyncOnlineAttendanceCommand extends Command
{
    protected $signature = 'hris:sync-attendance';

    protected $description = 'Fetch unsynced attendance records from the Online HRIS API and save them locally.';

    public function handle(): void
    {
        $this->info('Starting Online HRIS attendance sync...');

        (new SyncOnlineAttendance)->handle();

        $this->info('Sync complete. Check storage/logs/laravel.log for details.');
    }
}
