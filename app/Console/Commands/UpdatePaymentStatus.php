<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Payment;
use Illuminate\Console\Command;

class UpdatePaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment statuses from Completed to Deposited if due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating payment statuses...');

        $payments = Payment::where('status', 'Completed')
            ->where('payment_date_time', '<=', Carbon::now())
            ->get();

        foreach ($payments as $payment) {
            $payment->status = 'Deposited';
            $payment->save();
        }

        $this->info('Payment statuses updated successfully.');
    }
}
