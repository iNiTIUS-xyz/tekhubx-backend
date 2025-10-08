<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\WorkOrder;
use Illuminate\Console\Command;
use App\Models\ProviderCheckout;
use App\Notifications\AtRiskNotification;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ConfirmationNotification;

class CheckWorkOrderConfirmation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-work-order-confirmation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workOrders = WorkOrder::whereIn('schedule_type', [
            'Arrive at a specific date and time - (Hard Start)',
            'Complete work between specific hours',
            'Complete work anytime over a date range',
        ])
            ->whereNotNull('assigned_id')
            ->where(function ($query) {
                $query->where('schedule_date', Carbon::tomorrow())
                    ->orWhere('schedule_date_between_1', Carbon::tomorrow())
                    ->orWhere('schedule_date_between_2', Carbon::tomorrow())
                    ->orWhere('between_date', Carbon::tomorrow())
                    ->orWhere('through_date', Carbon::tomorrow());
            })
            ->get();

        foreach ($workOrders as $workOrder) {

            $provider_checkout = ProviderCheckout::where('work_order_unique_id', $workOrder->work_order_unique_id)->first();

            if ($provider_checkout) {
                // Notify provider at 6:00 AM
                if (now()->format('H:i') === '06:00') {
                    $provider_checkout->update(['confirmed' => 'yes']);
                    $provider = $workOrder->provider->email; // Assuming a relation
                    Notification::send($provider, new ConfirmationNotification($workOrder));
                }

                // Mark "At-Risk" if not confirmed by 12:00 PM
                if (now()->format('H:i') === '12:00') {
                    $provider_checkout->update(['at_risk' => 'yes']);
                    $provider = $workOrder->provider->email;
                    Notification::send($provider, new AtRiskNotification($workOrder));
                }
            }
            else {
                // Handle case where no provider checkout exists
                $this->info("No provider checkout found for Work Order ID: {$workOrder->work_order_unique_id}");
            }
        }

        $this->info('Confirmation status check completed.');
    }
}
