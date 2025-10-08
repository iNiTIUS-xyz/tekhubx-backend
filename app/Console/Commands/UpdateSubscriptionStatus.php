<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Subscription;
use Illuminate\Console\Command;

class UpdateSubscriptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subscription status to Inactive if end_date_time is today';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();

        // Find subscriptions that need to be updated
        $subscriptions = Subscription::whereDate('end_date_time', '<=', $today)
            ->where('status', '!=', 'Inactive')
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update(['status' => 'Inactive']);
        }

        $this->info('Subscription statuses updated successfully.');
        return 0;
    }
}
