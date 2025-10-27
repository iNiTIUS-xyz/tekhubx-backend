<?php

namespace App\Http\Controllers\Provider;

use Carbon\Carbon;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use App\Models\LiveTracking;
use Illuminate\Http\Request;
use App\Utils\GlobalConstant;
use App\Models\ProviderCheckout;
use App\Helpers\ApiResponseHelper;
use App\Models\AdditionalLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Events\ProviderLocationUpdated;
use Illuminate\Support\Facades\Validator;

class WorkOrderCheckoutController extends Controller
{
    public function startTime(Request $request, $work_order_unique_id)
    {
        $rules = [
            'date' => 'required|date',
            'time' => 'required',
            'duration' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        $work_order = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not assigned to you',
            ], 404);
        }

        $tasks = json_decode($work_order->tasks, true);
        $notificationEmails = collect($tasks)
            ->firstWhere('name', 'Set Start Time')['notification_email'] ?? [];

        foreach ($notificationEmails as $email) {
            Mail::send('emails.task_started', [
                'taskName' => 'Set Start Time',
                'startTime' => now()->format('Y-m-d H:i:s')
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Task "Set Start Time" has started');
            });
        }
        $check = ProviderCheckout::where('work_order_unique_id', $work_order->work_order_unique_id)->first();

        if (!$check) {
            $provider_checkout = new ProviderCheckout();
            $provider_checkout->uuid = $work_order->assigned_uuid;
            $provider_checkout->user_id = $work_order->assigned_id;
            $provider_checkout->work_order_unique_id = $work_order->work_order_unique_id;
            $provider_checkout->start_date = $request->date;
            $provider_checkout->start_time = $request->time;
            $provider_checkout->duration = $request->duration;
            $provider_checkout->save();

            $history = new HistoryLog();
            $history->provider_id = Auth::user()->id;
            $history->work_order_unique_id = $work_order_unique_id;
            $history->description = 'Start Time Set';
            $history->type = 'provider';
            $history->date_time = now();
            $history->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Work order checkout successfully',
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order checkout already set',
            ]);
        }
    }

    public function confirmWorkOrder($work_order_unique_id)
    {
        $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();
        $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();
        if (!$provider_checkout) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order start time not found.',
            ], 404);
        }
        $provider_checkout->update(['confirmed' => 'yes']);

        $history = new HistoryLog();
        $history->provider_id = Auth::user()->id;
        $history->work_order_unique_id = $work_order_unique_id;
        $history->description = 'Confirm Work Order Set';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Work order confirmed successfully'
        ]);
    }

    // public function markOnMyWay(Request $request, $work_order_unique_id)
    // {
    //     $rules = [
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'speed' => 'nullable|numeric',
    //         'heading' => 'nullable|numeric',
    //         'accuracy' => 'nullable|numeric',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 500);
    //     }

    //     $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();
    //     $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();
    //     if (!$provider_checkout) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Work order start time not found.',
    //         ], 404);
    //     }
    //     // Ensure the provider is assigned and can mark "On My Way"
    //     if ($provider_checkout->confirmed !== 'yes') {
    //         return response()->json(['error' => 'Work Order must be confirmed before marking "On My Way".'], 400);
    //     }

    //     if ($provider_checkout->on_my_way === 'yes') {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'You are already marked as "On My Way".'
    //         ], 200);
    //     }

    //     $tasks = json_decode($workOrder->tasks, true);
    //     $notificationEmails = collect($tasks)
    //         ->firstWhere('name', 'Set Start Time')['notification_email'] ?? [];

    //     $location = LiveTracking::create([
    //         'work_order_unique_id' => $work_order_unique_id,
    //         'provider_id' => Auth::id(),
    //         'latitude' => $request->latitude,
    //         'longitude' => $request->longitude,
    //         'speed' => $request->speed ?? 0,
    //         'heading' => $request->heading ?? 0,
    //         'accuracy' => $request->accuracy,
    //         'status' => 'on_my_way',
    //         'tracked_at' => now(),
    //     ]);

    //     // Broadcast the location update
    //     broadcast(new ProviderLocationUpdated($location))->toOthers();

    //     foreach ($notificationEmails as $email) {
    //         Mail::send('emails.tracking', [
    //             'taskName' => 'Set Start Time',
    //             'startTime' => now()->format('Y-m-d H:i:s')
    //         ], function ($message) use ($email) {
    //             $message->to($email)
    //                 ->subject('Task "On My Way" has started');
    //         });
    //     }

    //     $provider_checkout->update([
    //         'on_my_way' => 'yes',
    //         'on_my_way_at' => now(),
    //     ]);

    //     $history = new HistoryLog();
    //     $history->provider_id = Auth::user()->id;
    //     $history->work_order_unique_id = $work_order_unique_id;
    //     $history->description = 'On My Way Set';
    //     $history->type = 'provider';
    //     $history->date_time = now();
    //     $history->save();

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Work Order status updated to On My Way.',
    //         'tracking_enabled' => true,
    //     ]);
    // }
    public function markOnMyWay(Request $request, $work_order_unique_id)
    {
        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        try {
            DB::beginTransaction();

            $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();
            $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();
            if (!$provider_checkout) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order start time not found.',
                ], 404);
            }

            // Ensure the provider is assigned and can mark "On My Way"
            if ($provider_checkout->confirmed !== 'yes') {
                return response()->json(['error' => 'Work Order must be confirmed before marking "On My Way".'], 400);
            }

            if ($provider_checkout->on_my_way === 'yes') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already marked as "On My Way".'
                ], 200);
            }

            $tasks = json_decode($workOrder->tasks, true);
            $notificationEmails = collect($tasks)
                ->firstWhere('name', 'Set Start Time')['notification_email'] ?? [];

            $location = LiveTracking::create([
                'work_order_unique_id' => $work_order_unique_id,
                'provider_id' => Auth::id(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'speed' => $request->speed ?? 0,
                'heading' => $request->heading ?? 0,
                'accuracy' => $request->accuracy,
                'status' => 'on_my_way',
                'tracked_at' => now(),
            ]);

            // Broadcast the location update
            broadcast(new ProviderLocationUpdated($location))->toOthers();

            foreach ($notificationEmails as $email) {
                Mail::send('emails.tracking', [
                    'taskName' => 'Set Start Time',
                    'startTime' => now()->format('Y-m-d H:i:s')
                ], function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Task "On My Way" has started');
                });
            }

            $provider_checkout->update([
                'on_my_way' => 'yes',
                'on_my_way_at' => now(),
            ]);

            $history = new HistoryLog();
            $history->provider_id = Auth::user()->id;
            $history->work_order_unique_id = $work_order_unique_id;
            $history->description = 'On My Way Set';
            $history->type = 'provider';
            $history->date_time = now();
            $history->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Order status updated to On My Way.',
                'tracking_enabled' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark On My Way Failed: ' . $e->getMessage());

            return response()->json([
                'errors' => ApiResponseHelper::formatErrors(
                    ApiResponseHelper::SYSTEM_ERROR,
                    [config('app.debug') ? $e->getMessage() : 'Server Error']
                ),
                'payload' => null,
            ], 500);
        }
    }

    // public function checkIn(Request $request, $work_order_unique_id)
    // {
    //     // 1. Validate input data
    //     $rules = [
    //         'latitude' => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //         'check_in_time' => 'required|date',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 500);
    //     }

    //     // 2. Get the work order details
    //     $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();
    //     if (!$provider_checkout) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Work order start time not found.',
    //         ], 404);
    //     }
    //     $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

    //     $tasks = json_decode($workOrder->tasks, true);
    //     $notificationEmails = collect($tasks)
    //         ->firstWhere('name', 'Check in')['notification_email'] ?? [];

    //     foreach ($notificationEmails as $email) {
    //         Mail::send('emails.task_started', [
    //             'taskName' => 'Check in',
    //             'startTime' => now()->format('Y-m-d H:i:s')
    //         ], function ($message) use ($email) {
    //             $message->to($email)
    //                 ->subject('Task "Check in" has started');
    //         });
    //     }
    //     // 3. Calculate time difference
    //     if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[0]) {
    //         $start_time = $workOrder->schedule_time;
    //     }
    //     if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[1]) {
    //         $start_time = $workOrder->schedule_time_between_1;
    //     }
    //     if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[2]) {
    //         $start_time = $workOrder->between_time;
    //     }
    //     $scheduledStartTime = Carbon::parse($start_time);
    //     $checkInTime = Carbon::parse($request->check_in_time);
    //     $timeDifference = $checkInTime->diffInMinutes($scheduledStartTime, false);


    //     // 4. Determine Check-In Status
    //     if ($timeDifference < -30) {
    //         $status = 'Too Early';
    //         $timelinessImpact = -1; // Decrease Timeliness Rate
    //     } elseif ($timeDifference >= -30 && $timeDifference <= 15) {
    //         $status = 'On-Time';
    //         $timelinessImpact = 1; // Increase Timeliness Rate
    //     } else {
    //         $status = 'Late';
    //         $timelinessImpact = -1; // Decrease Timeliness Rate
    //     }

    //     // 5. Log the check-in
    //     $provider_checkout->update([
    //         'check_in_time' => $request->check_in_time,
    //         'is_check_in' => 'yes',
    //         'timeliness_rate' => $timelinessImpact,
    //         'status' => $status
    //     ]);

    //     $history = new HistoryLog();
    //     $history->provider_id = Auth::user()->id;
    //     $history->work_order_unique_id = $work_order_unique_id;
    //     $history->description = 'Check In';
    //     $history->type = 'provider';
    //     $history->date_time = now();
    //     $history->save();

    //     // 6. GPS validation (optional)
    //     if ($workOrder->location_id == "remote") {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Work Order location is remote.'
    //         ]);
    //     } else {
    //         $additional_locations = AdditionalLocation::find($workOrder->location_id);
    //         $distance = $this->calculateDistance($additional_locations->latitude, $additional_locations->longitude, $request->latitude, $request->longitude);
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'You are more than 1 mile away from the work order location.'
    //         ]);
    //     }
    // }
    public function checkIn(Request $request, $work_order_unique_id)
    {
        // 1. Validate input data
        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'check_in_time' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        try {
            DB::beginTransaction();

            // 2. Get the work order details
            $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();
            if (!$provider_checkout) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order start time not found.',
                ], 404);
            }
            $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

            $tasks = json_decode($workOrder->tasks, true);
            $notificationEmails = collect($tasks)
                ->firstWhere('name', 'Check in')['notification_email'] ?? [];

            foreach ($notificationEmails as $email) {
                Mail::send('emails.task_started', [
                    'taskName' => 'Check in',
                    'startTime' => now()->format('Y-m-d H:i:s')
                ], function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Task "Check in" has started');
                });
            }

            // 3. Calculate time difference
            if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[0]) {
                $start_time = $workOrder->schedule_time;
            }
            if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[1]) {
                $start_time = $workOrder->schedule_time_between_1;
            }
            if ($workOrder->schedule_type == GlobalConstant::ORDER_SCHEDULE_TYPE[2]) {
                $start_time = $workOrder->between_time;
            }
            $scheduledStartTime = Carbon::parse($start_time);
            $checkInTime = Carbon::parse($request->check_in_time);
            $timeDifference = $checkInTime->diffInMinutes($scheduledStartTime, false);

            // 4. Determine Check-In Status
            if ($timeDifference < -30) {
                $status = 'Too Early';
                $timelinessImpact = -1; // Decrease Timeliness Rate
            } elseif ($timeDifference >= -30 && $timeDifference <= 15) {
                $status = 'On-Time';
                $timelinessImpact = 1; // Increase Timeliness Rate
            } else {
                $status = 'Late';
                $timelinessImpact = -1; // Decrease Timeliness Rate
            }

            // 5. Log the check-in
            $provider_checkout->update([
                'check_in_time' => $request->check_in_time,
                'is_check_in' => 'yes',
                'timeliness_rate' => $timelinessImpact,
                'status' => $status
            ]);

            $history = new HistoryLog();
            $history->provider_id = Auth::user()->id;
            $history->work_order_unique_id = $work_order_unique_id;
            $history->description = 'Check In';
            $history->type = 'provider';
            $history->date_time = now();
            $history->save();

            // 6. GPS validation (optional)
            if ($workOrder->location_id == "remote") {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Work Order location is remote.'
                ]);
            } else {
                $additional_locations = AdditionalLocation::find($workOrder->location_id);
                $distance = $this->calculateDistance($additional_locations->latitude, $additional_locations->longitude, $request->latitude, $request->longitude);
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'You are more than 1 mile away from the work order location.'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check In Failed: ' . $e->getMessage());

            return response()->json([
                'errors' => ApiResponseHelper::formatErrors(
                    ApiResponseHelper::SYSTEM_ERROR,
                    [config('app.debug') ? $e->getMessage() : 'Server Error']
                ),
                'payload' => null,
            ], 500);
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius in kilometers

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance; // Distance in kilometers
    }

    //update provider location

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'work_order_unique_id' => 'required|string',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $trackedAt = $request->timestamp ? new \DateTime($request->timestamp) : now();

        $location = LiveTracking::create([
            'work_order_unique_id' => $request->work_order_unique_id,
            'provider_id' => Auth::id(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed ?? 0,
            'heading' => $request->heading ?? 0,
            'accuracy' => $request->accuracy,
            'status' => 'on_my_way',
            'tracked_at' => $trackedAt,
        ]);

        // Broadcast the location update
        broadcast(new ProviderLocationUpdated($location))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'Location updated successfully',
            'location' => $location,
        ]);
    }

    public function getLatestLocation($work_order_unique_id)
    {
        $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$workOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        // Check authorization (client, provider, or admin)
        $user = Auth::user();
        $isAuthorized = false;

        if ($user->organization_role === 'Client' && $workOrder->user_id == $user->id) {
            $isAuthorized = true;
        }

        if ($user->organization_role === 'provider' && $workOrder->assigned_id == $user->id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authorized to access this work order',
            ], 403);
        }

        $location = LiveTracking::where('work_order_unique_id', $work_order_unique_id)
            ->orderBy('tracked_at', 'desc')
            ->first();

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'No location data available',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'location' => $location,
            'provider' => [
                'id' => $workOrder->provider_id,
                'name' => $workOrder->provider->name,
            ],
            'work_order' => [
                'id' => $workOrder->id,
                'unique_id' => $workOrder->work_order_unique_id,
                'title' => $workOrder->work_order_title,
            ],
        ]);
    }

    public function getLocationHistory(Request $request, $work_order_unique_id)
    {
        $workOrder = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$workOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        // Check authorization (similar to getLatestLocation)
        $user = Auth::user();
        $isAuthorized = false;

        if ($user->organization_role === 'Client' && $workOrder->user_id == $user->id) {
            $isAuthorized = true;
        }

        if ($user->organization_role === 'provider' && $workOrder->assigned_id == $user->id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authorized to access this work order',
            ], 403);
        }

        $limit = $request->input('limit', 100);
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');

        $query = LiveTracking::where('work_order_unique_id', $work_order_unique_id);

        if ($startTime) {
            $query->where('tracked_at', '>=', new \DateTime($startTime));
        }

        if ($endTime) {
            $query->where('tracked_at', '<=', new \DateTime($endTime));
        }

        $locations = $query->orderBy('tracked_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'locations' => $locations,
            'count' => $locations->count(),
        ]);
    }
}
