<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdatePaymentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Find payments with status "Completed" that are due for "Deposited"
        $payments = Payment::where('status', 'Completed')
            ->where('payment_date_time', '<=', Carbon::now())
            ->get();

        foreach ($payments as $payment) {
            $payment->status = 'Deposited';
            $payment->save();
        }
    }
}
