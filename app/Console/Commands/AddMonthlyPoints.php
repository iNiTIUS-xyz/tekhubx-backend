<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Console\Command;
use App\Services\UniqueIdentifierService;

class AddMonthlyPoints extends Command
{
    protected $signature = 'points:add-monthly';
    protected $description = 'Add monthly subscription points to all users';

    public function handle()
    {
        $subscriptions = Subscription::where('status', 'Active')->get();

        foreach ($subscriptions as $subscription) {
            $userUuid = $subscription->uuid;
            $planPoints = $subscription->point;

            // Get the user's latest transaction
            $latestTransaction = Transaction::where('client_id', $userUuid)
                ->orderBy('created_at', 'desc')
                ->first();

            // Create a new transaction
            Transaction::create([
                'transaction_unique_id' => UniqueIdentifierService::generateUniqueIdentifier(new Transaction(), 'transaction_unique_id', 'uuid'),
                'client_id' => $userUuid,
                'transaction_type' => 'Subscription',
                'point_credit' => $planPoints,
                'point_balance' => $latestTransaction ? $latestTransaction->point_balance + $planPoints : $planPoints,
                'description' => 'Monthly subscription points added',
                'status' => 'Completed',
                'gateway' => 'System',
            ]);
        }

        $this->info('Monthly points added to all subscribed users.');
    }
}
