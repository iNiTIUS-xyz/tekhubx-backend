<?php

namespace App\Console\Commands;

use App\Models\SendWorkRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWorkRequestUpdate extends Command
{
    protected $signature = 'app:send-work-request-update';

    protected $description = 'Command description';

    public function handle()
    {
        $now = Carbon::now();

        SendWorkRequest::query()
            ->where('status', 'Active')
            ->where('expired_request_time', '<', $now)
            ->whereNotNull('after_withdraw')
            ->update([
                'status' => 'Inactive'
            ]);

        $this->info('Send Work Request updated successfully');
    }
}
