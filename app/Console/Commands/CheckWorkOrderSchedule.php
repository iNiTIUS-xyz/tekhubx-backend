<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\WorkOrder;
use Illuminate\Console\Command;
use App\Models\ProviderCheckout;

class CheckWorkOrderSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workorder:check-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check work order schedules and notify assigned providers of conflicts';


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
                $query->where('schedule_date', today()->format('Y-m-d'))
                    ->orWhere('schedule_date_between_1', today()->format('Y-m-d'))
                    ->orWhere('schedule_date_between_2', today()->format('Y-m-d'))
                    ->orWhere('between_date', today()->format('Y-m-d'))
                    ->orWhere('through_date', today()->format('Y-m-d'));
            })
            ->get();

        foreach ($workOrders as $workOrder) {
            $check = ProviderCheckout::where('work_order_unique_id', $workOrder->work_order_unique_id)->first();

            $workOrderTime = $this->getWorkOrderTime($workOrder);
            $scheduleConflict = false;

            if (!$check) {
                if ($workOrder->schedule_type === 'Arrive at a specific date and time - (Hard Start)') {
                    $scheduleConflict = $this->checkSpecificDateTime($workOrder, $workOrderTime);
                } elseif ($workOrder->schedule_type === 'Complete work between specific hours') {
                    $scheduleConflict = $this->checkSpecificHours($workOrder, $workOrderTime);
                } elseif ($workOrder->schedule_type === 'Complete work anytime over a date range') {
                    $scheduleConflict = $this->checkDateRange($workOrder, $workOrderTime);
                }

                if ($scheduleConflict) {
                    $this->notifyProvider($workOrder, 'The scheduled time is too close to the current time. Please adjust the schedule.');
                }
            }
        }

        $this->info('Work order schedule check completed.');
    }

    /**
     * Get the estimated time required for the work order.
     */
    private function getWorkOrderTime($workOrder)
    {
        if ($workOrder->pay_type === 'Hourly') {
            return $workOrder->max_hours;
        }

        if (in_array($workOrder->pay_type, ['Approximate Hourly', 'Per Device'])) {
            return $workOrder->approximate_hour_complete;
        }

        if ($workOrder->pay_type === 'Fixed') {
            return $workOrder->fixed_hours;
        }

        return 0; // Default fallback
    }

    /**
     * Check scheduling conflicts for specific date and time.
     */
    private function checkSpecificDateTime($workOrder, $workOrderTime)
    {
        $scheduleTime = Carbon::parse($workOrder->schedule_time);
        $minimumTime = $scheduleTime->copy()->subHours($workOrderTime);
        $currentTime = now();

        return $currentTime->greaterThanOrEqualTo($minimumTime);
    }

    /**
     * Check scheduling conflicts for specific hours.
     */
    private function checkSpecificHours($workOrder, $workOrderTime)
    {
        $scheduleTime1 = Carbon::parse($workOrder->schedule_time_between_1);
        $scheduleTime2 = Carbon::parse($workOrder->schedule_time_between_2);
        $minimumTime = min($scheduleTime1->copy()->subHours($workOrderTime), $scheduleTime2->copy()->subHours($workOrderTime));
        $currentTime = now();

        return $currentTime->greaterThanOrEqualTo($minimumTime);
    }

    /**
     * Check scheduling conflicts for a date range.
     */
    private function checkDateRange($workOrder, $workOrderTime)
    {
        $scheduleTime1 = Carbon::parse($workOrder->between_time);
        $scheduleTime2 = Carbon::parse($workOrder->through_time);
        $minimumTime = min($scheduleTime1->copy()->subHours($workOrderTime), $scheduleTime2->copy()->subHours($workOrderTime));
        $currentTime = now();

        return $currentTime->greaterThanOrEqualTo($minimumTime);
    }

    /**
     * Notify the assigned provider about the schedule conflict.
     */
    private function notifyProvider($workOrder, $message)
    {
            $provider_checkout = new ProviderCheckout();
            $provider_checkout->uuid = $workOrder->assigned_uuid;
            $provider_checkout->user_id = $workOrder->assigned_id;
            $provider_checkout->work_order_unique_id = $workOrder->work_order_unique_id;
            $provider_checkout->message = $message;
            $provider_checkout->save();
    }
}
