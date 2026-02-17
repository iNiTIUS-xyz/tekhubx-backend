<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\State;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use App\Models\ServiceFees;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use App\Models\DocumentLibrary;
use App\Services\CommonService;
use App\Models\AdditionalContact;
use App\Models\AdditionalLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Classes\NotificationSentClass;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\UniqueIdentifierService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateWorkOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requestData;
    protected $user;

    public function __construct(array $requestData, User $user)
    {
        $this->requestData = $requestData;
        $this->user = $user;
    }

    public function handle(NotificationSentClass $notificationService, CommonService $commonService)
    {
        // Re-attach user to auth for this job context (important in queue)
        Auth::setUser($this->user);

        $request = new Request($this->requestData); // Simulate request for helper methods

        try {
            DB::beginTransaction();

            $uniqueMd5 = $this->generateUniqueWorkOrderId();

            $location_id = null;
            if ($request->filled('state_id')) {
                $location_id = $this->getLocationId($request, $commonService);
            }

            $additionalContactIds = $this->storeAdditionalContacts($request, $uniqueMd5);
            $shipment_arr = $this->storeShipments($request, $uniqueMd5);
            $allFilePaths = $this->storeDocuments($request);

            // Handle tasks file upload (if any)
            $tasks = $request->tasks;
            $tasksJson = json_encode($tasks);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                if ($file->isValid()) {
                    $filePath = $file->store('tasks/files', 'public');
                    $tasks = json_decode($tasksJson, true);
                    foreach ($tasks as &$task) {
                        if (isset($task['file_name']) && $task['file_name'] === 'abc.pdf') {
                            $task['file_name'] = $filePath;
                        }
                    }
                    $tasksJson = json_encode($tasks);
                }
            }

            // Calculate total labor
            $total = $this->calculateTotalLabor($request);

            // Tax calculation (uncomment when service is ready)
            // $taxValue = app('taxService')->calculateTax(...);
            $taxValue = 0.0;

            if ($request->remote) {
                $taxValue = 0.0;
            } elseif ($request->save_location_id) {
                $additionalLocation = AdditionalLocation::where('id', $request->save_location_id)->first();
                $country = Country::where('id', $additionalLocation->country_id)->first();
                $state = State::where('id', $additionalLocation->state_id)->first();
            } else {

                $country = Country::where('id', $request->country_id)->first();
                $state = State::where('id', $request->state_id)->first();
            }

            // $taxValue = app('taxService')->calculateTax($state, $country, $request, $total);


            // Create WorkOrder
            $workOrder = new WorkOrder();
            $workOrder->uuid = $this->user->uuid;
            $workOrder->user_id = $this->user->id;
            $workOrder->work_order_unique_id = $uniqueMd5;

            $workOrder->template_id = $request->template_id;
            $workOrder->work_order_title = $request->work_order_title;
            $workOrder->default_client_id = $request->default_client_id;
            $workOrder->project_id = $request->project_id;
            $workOrder->export_bool = $request->export_bool;
            $workOrder->counter_offer_bool = $request->counter_offer_bool;
            $workOrder->gps_bool = $request->gps_bool;
            $workOrder->service_description_public = $request->service_description_public;
            $workOrder->service_description_note_private = $request->service_description_note_private;
            $workOrder->work_category_id = $request->work_category_id;
            $workOrder->additional_work_category_id = $request->additional_work_category_id;
            $workOrder->service_type_id = $request->service_type_id;
            $workOrder->qualification_type = $request->has('qualification_type') ? json_encode($request->qualification_type) : null;

            $workOrder->schedule_type = $request->schedule_type;
            $workOrder->schedule_date = $request->schedule_date;
            $workOrder->schedule_time = $request->schedule_time;
            $workOrder->time_zone = $request->time_zone;
            $workOrder->schedule_date_between_1 = $request->schedule_date_between_1;
            $workOrder->schedule_date_between_2 = $request->schedule_date_between_2;
            $workOrder->schedule_time_between_1 = $request->schedule_time_between_1;
            $workOrder->schedule_time_between_2 = $request->schedule_time_between_2;
            $workOrder->between_date = $request->between_date;
            $workOrder->between_time = $request->between_time;
            $workOrder->through_date = $request->through_date;
            $workOrder->through_time = $request->through_time;
            $workOrder->work_order_manager_id = $request->work_order_manager_id;

            $workOrder->buyer_custom_field = $request->buyer_custom_field;
            $workOrder->pay_type = $request->pay_type;
            $workOrder->hourly_rate = ((float) $request->hourly_rate) ?? 0.0;
            $workOrder->max_hours = $request->max_hours;
            $workOrder->approximate_hour_complete = $request->approximate_hour_complete;
            $workOrder->total_pay = ((float) $request->total_pay) ?? 0.0;
            $workOrder->per_device_rate = ((float) $request->per_device_rate) ?? 0.0;
            $workOrder->max_device = $request->max_device;
            $workOrder->fixed_payment = $request->fixed_payment;
            $workOrder->fixed_hours = ((float) $request->fixed_hours) ?? 0.0;
            $workOrder->additional_hourly_rate = $request->additional_hourly_rate;
            $workOrder->max_additional_hour = $request->max_additional_hour;

            $workOrder->rule_id = $request->rule_id;

            $workOrder->location_id = $location_id ?? $request->save_location_id ?? $request->remote;
            $workOrder->additional_contact_id = json_encode($additionalContactIds);
            $workOrder->documents_file = json_encode($allFilePaths);
            $workOrder->tasks = $tasksJson;
            $workOrder->labor = $total;
            $workOrder->state_tax = $taxValue;
            $workOrder->bank_account_id = $this->user->stripe_account_id;
            $workOrder->shipment_id = json_encode($shipment_arr);
            $workOrder->status = $request->status ?? 'Published';
            $workOrder->save();

            // Create Payment record
            $subscription_check = Subscription::where('uuid', $this->user->uuid)->first();
            $service_fees = ServiceFees::where('plan_id', $subscription_check->plan_id)
                ->where('status', 'Active')
                ->get();

            $service_fees_array = $service_fees->map(fn($fee) => [
                'name' => $fee->name,
                'percentage' => $fee->percentage,
            ])->toArray();

            Payment::create([
                'client_id' => $this->user->uuid,
                'account_id' => $this->user->stripe_account_id,
                'payment_unique_id' => UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid'),
                'work_order_unique_id' => $uniqueMd5,
                'services_fee' => json_encode($service_fees_array),
                'total_labor' => $total,
                'tax' => $taxValue,
                'status' => 'Pending',
                'transaction_type' => 'Payment',
                'description' => 'Payment Create For Work Order',
            ]);

            // History log
            HistoryLog::create([
                'client_id' => $this->user->id,
                'work_order_unique_id' => $uniqueMd5,
                'description' => 'Work Order Create.',
                'type' => 'client',
                'date_time' => now(),
            ]);

            // Send notification (make sure this is queueable or fire-and-forget)
            $notificationService->providerNotifyWorkOrderCreate($workOrder);

            DB::commit();

            // Optional: Send success email/notification to user
            // Mail::to($this->user->email)->send(new WorkOrderCreated($workOrder));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreateWorkOrderJob failed: ' . $e->getMessage(), [
                'user_id' => $this->user->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Optional: Notify user of failure
            // Mail::to($this->user->email)->send(new WorkOrderCreationFailed($requestData));
        }
    }

    // Move your private methods here (or better: extract to a service class)
    private function generateUniqueWorkOrderId() {
          do {
            $uniqueMd5 = Auth::user()->id . date('ymds');
        } while (WorkOrder::where('work_order_unique_id', $uniqueMd5)->exists());

        return $uniqueMd5;
    }
    private function getLocationId($request, $commonService) {
         $country_name = Country::where('id', $request->country_id)->first();
        $state_name = State::where('id', $request->state_id)->first();

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
        } else {
            $full_address = "{$request->address_line_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";
            $location = $commonService->geocodeAddressOSM($full_address);

            if ($location) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
            } else {
                $latitude = null;
                $longitude = null;
            }
        }

        $existsingLocation = AdditionalLocation::where('uuid', Auth::user()->uuid)
            ->where('user_id', Auth::user()->id)
            ->where('address_line_1', $request->address_line_1)
            ->where('city', $request->city)
            ->where('state_id', $request->state_id)
            ->where('zip_code', $request->zip_code)
            ->first();

        if ($existsingLocation) {
            return $existsingLocation->id;
        }
        if ($request->has('display_name')) {
            $additional_location = new AdditionalLocation();
            $additional_location->uuid = Auth::user()->uuid;
            $additional_location->user_id = Auth::user()->id;
            $additional_location->display_name = $request->display_name;
            $additional_location->location_type = $request->location_type;
            $additional_location->country_id = $request->country_id;
            $additional_location->country_name = $country_name->name;
            $additional_location->address_line_1 = $request->address_line_1;
            $additional_location->address_line_2 = $request->address_line_2;
            $additional_location->city = $request->city;
            $additional_location->state_id = $request->state_id;
            $additional_location->state_name = $state_name->name;
            $additional_location->zip_code = $request->zip_code;
            $additional_location->latitude = $latitude;
            $additional_location->longitude = $longitude;
            $additional_location->save_name = $request->save_name;
            $additional_location->save();
            return $additional_location->id;
        } elseif ($request->has('save_location_id')) {
            return $request->save_location_id;
        } elseif ($request->has('remote') && $request->remote) {
            return 'remote';
        }
        return null;
    }
    private function storeAdditionalContacts($request, $uniqueMd5) {
        $additionalContactIds = [];

        // Check if 'additional_contact_info' is present and is an array or object
        if (is_array($request->additional_contact_info) || is_object($request->additional_contact_info)) {
            foreach ($request->additional_contact_info as $key => $contactName) {
                $contactName = (object) $contactName;
                $additional_contact = new AdditionalContact();
                $additional_contact->user_id = Auth::user()->id;
                $additional_contact->work_order_unique_id = $uniqueMd5;
                $additional_contact->name = isset($contactName->additional_contact_name) ? $contactName->additional_contact_name : null;
                $additional_contact->title = isset($contactName->additional_contact_title) ? $contactName->additional_contact_title : null;
                $additional_contact->phone = isset($contactName->additional_contact_phone) ? $contactName->additional_contact_phone : null;
                $additional_contact->ext = isset($contactName->additional_contact_ext) ? $contactName->additional_contact_ext : null;
                $additional_contact->email = isset($contactName->additional_contact_email) ? $contactName->additional_contact_email : null;
                $additional_contact->note = isset($contactName->additional_contact_note) ? $contactName->additional_contact_note : null;
                $additional_contact->save();

                $additionalContactIds[] = $additional_contact->id;
            }
        }
        return $additionalContactIds;
    }
    private function storeShipments($request, $uniqueMd5) {
         $shipment_arr = [];
        $shipmentInfos = $request->shipment_info ?? []; // Default to an empty array

        if (!is_array($shipmentInfos)) {
            return $shipment_arr; // Return empty if it's not an array
        }

        foreach ($shipmentInfos as $key => $shipmentInfo) {
            $shipmentInfo = (object) $shipmentInfo;

            // Validate required fields to avoid runtime errors
            if (empty($shipmentInfo->tracking_number) || empty($shipmentInfo->shipment_description)) {
                continue; // Skip invalid entries
            }

            $shipment = new Shipment();
            $shipment->user_id = Auth::user()->id;
            $shipment->work_order_unique_id = $uniqueMd5;
            $shipment->tracking_number = $shipmentInfo->tracking_number;
            $shipment->shipment_description = $shipmentInfo->shipment_description;
            $shipment->shipment_carrier = $shipmentInfo->shipment_carrier ?? null;
            $shipment->shipment_carrier_name = $shipmentInfo->shipment_carrier_name ?? null;
            $shipment->shipment_direction = $shipmentInfo->shipment_direction ?? null;
            $shipment->save();

            $shipment_arr[] = $shipment->id;
        }

        return $shipment_arr;
    }
    private function storeDocuments($request) { $allFilePaths       = [];
        $uploadedFilePaths  = [];
        $selectedFilePaths  = [];

        try {
            Log::info('=== DOCUMENT PROCESSING START ===');
            Log::info('Request input (non-file):', $request->except(['new_documents_file']));
            Log::info('All files received:', $request->file());

            // =================================================================
            // 1. HANDLE NEW FILE UPLOADS: new_documents_file[0][documents_file]
            // =================================================================
            $newFileGroups = $request->file('new_documents_file');

            if (is_array($newFileGroups) && !empty($newFileGroups)) {
                foreach ($newFileGroups as $index => $fileGroup) {
                    $file = null;

                    // Support both formats:
                    //   - new_documents_file[0][documents_file]
                    //   - (future) new_documents_file[] = UploadedFile
                    if (is_array($fileGroup) && isset($fileGroup['documents_file'])) {
                        $file = $fileGroup['documents_file'];
                    } elseif ($fileGroup instanceof \Illuminate\Http\UploadedFile) {
                        $file = $fileGroup;
                    }

                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $path = $file->store('work/documents', 'public');

                        // Save to DocumentLibrary
                        $doc = DocumentLibrary::create([
                            'uuid'      => Auth::user()->uuid,
                            'name'      => $originalName,
                            'file_path' => $path,
                        ]);

                        $uploadedFilePaths[] = $path;
                        Log::info("Uploaded new file [{$index}]: {$originalName} → {$path} (DB ID: {$doc->id})");
                    } else {
                        Log::warning("Invalid file at index {$index}");
                    }
                }
            } else {
                Log::info('No new files uploaded (new_documents_file is empty or missing)');
            }

            // =================================================================
            // 2. HANDLE REUSED OLD DOCUMENTS: old_documents_id[]
            // =================================================================
            if ($request->has('old_documents_id')) {
                $oldIds = array_filter((array) $request->input('old_documents_id')); // Remove empty

                if (!empty($oldIds)) {
                    $oldDocs = DocumentLibrary::whereIn('id', $oldIds)
                        ->select('id', 'name', 'file_path')
                        ->get();

                    foreach ($oldDocs as $doc) {
                        $selectedFilePaths[] = $doc->file_path;
                        Log::info("Reusing old file: {$doc->name} (ID: {$doc->id}) → {$doc->file_path}");
                    }
                } else {
                    Log::info('old_documents_id is present but empty');
                }
            } else {
                Log::info('No old_documents_id provided');
            }

            // =================================================================
            // 3. MERGE BOTH LISTS
            // =================================================================
            $allFilePaths = array_merge($uploadedFilePaths, $selectedFilePaths);

            Log::info('Final merged document paths:', $allFilePaths);
            Log::info('=== DOCUMENT PROCESSING END ===');
        } catch (\Throwable $e) {
            Log::error('Document processing failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw — just return what we have
        }

        return $allFilePaths;}
    private function calculateTotalLabor($request)
    {
        return match ($request->pay_type) {
            'Hourly' => $request->hourly_rate * $request->max_hours,
            'Fixed' => $request->total_pay ?? 0.0,
            'Per Device' => $request->per_device_rate * $request->max_device,
            'Blended' => ($request->fixed_payment ?? 0) + (($request->additional_hourly_rate ?? 0) * ($request->max_additional_hour ?? 0)),
            default => 0.0,
        };
    }
}
