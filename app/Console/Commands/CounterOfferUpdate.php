<?php

namespace App\Console\Commands;

use App\Models\CounterOffer;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CounterOfferUpdate extends Command
{
    protected $signature = 'app:counter-offer-update';

    protected $description = 'Command description';

    public function handle()
    {

        $invalidStatuses = ['Assigned', 'Inactive', 'Cancelled', 'Complete', 'Done'];

        $counterOffers = CounterOffer::query()
            ->where('status', 'Active')
            ->get();

        foreach ($counterOffers as $counterOff) {

            $infoCountOff = CounterOffer::query()
                ->findOrFail($counterOff->id);

            $workOrder = WorkOrder::query()
                ->where('work_order_unique_id', $counterOff->work_order_unique_id)
                ->first();

            if (!$workOrder) {
                // Log or handle the missing WorkOrder scenario
                $this->warn("WorkOrder not found for CounterOffer ID: {$counterOff->id}");
                continue; // Skip to the next iteration
            }
            if (in_array($workOrder->status, $invalidStatuses)) {
                $infoCountOff->status = 'Inactive';
                $infoCountOff->save();
            }

            if (Carbon::now()->greaterThan($infoCountOff->expired_request_time)) {
                $infoCountOff->status = 'Inactive';
                $infoCountOff->save();
            }
        }

        $this->info('Counter Offers updated successfully');
    }
}
