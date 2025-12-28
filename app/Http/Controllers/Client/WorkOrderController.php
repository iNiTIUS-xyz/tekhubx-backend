<?php

namespace App\Http\Controllers\Client;

use App\Models\User;
use App\Models\State;
use App\Models\Review;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Interest;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\PayChange;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use App\Models\ServiceFees;
use App\Models\CounterOffer;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Utils\GlobalConstant;
use App\Models\ExpenseRequest;
use App\Utils\ServerErrorMask;
use App\Models\DocumentLibrary;
use App\Models\SendWorkRequest;
use App\Models\WorkOrderReport;
use App\Models\WorkSubCategory;
use App\Services\AvaTaxService;
use App\Services\CommonService;
use App\Models\ProviderCheckout;
use App\Models\AdditionalContact;
use App\Models\QualificationType;
use App\Helpers\ApiResponseHelper;
use App\Models\AdditionalLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\WorkOrderCompleteFile;
use App\Classes\NotificationSentClass;
use App\Http\Resources\ReviewResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProviderResource;
use App\Models\QualificationSubCategory;
use App\Http\Resources\DocumentsResource;
use App\Http\Resources\WorkOrderResource;
use App\Services\UniqueIdentifierService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ClientReviewResource;
use App\Http\Resources\ProviderReviewResource;
use App\Http\Resources\SingleWorkOrderResource;
use App\Http\Controllers\Common\StripeController;
use App\Http\Resources\Provider\PayChangeResource;
use App\Http\Resources\Provider\CounterOfferResource;
use App\Http\Resources\Provider\ExpenseRequestResource;
use App\Http\Resources\Provider\SendWorkRequestResource;

class WorkOrderController extends Controller
{
    protected $sentNotification;
    protected $taxService;
    protected $stripeService;
    protected $CommonService;
    public function __construct(NotificationSentClass $sentNotification, AvaTaxService $taxService, StripeController $stripeService, CommonService $CommonService)
    {
        $this->sentNotification = $sentNotification;
        $this->taxService = $taxService;
        $this->stripeService = $stripeService;
        $this->CommonService = $CommonService;
    }

    public function index()
    {
        try {

            $work_orders = WorkOrder::where('uuid', Auth::user()->uuid)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'work_order' => WorkOrderResource::collection($work_orders),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('Store Work Order Request: ' . json_encode($request->all()));
        $rules = [
            'template_id' => 'nullable|integer',
            'work_order_title' => 'required|string|max:255',
            'default_client_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'export_bool' => 'nullable',
            'counter_offer_bool' => 'nullable',
            'gps_bool' => 'nullable',
            'service_description_public' => 'nullable|string',
            'service_description_note_private' => 'nullable|string',
            'work_category_id' => 'required|integer|exists:work_categories,id',
            'additional_work_category_id' => 'nullable|integer',
            'service_type_id' => 'required|integer|exists:services,id',
            'qualification_type' => 'required|array',
            'display_name' => 'nullable',
            'location_type' => 'nullable|in:' . implode(',', GlobalConstant::LOCATION_TYPE),
            'country_id' => 'nullable|integer|exists:countries,id',
            'address_line_1' => 'nullable',
            'address_line_2' => 'nullable',
            'city' => 'nullable',
            'state_id' => 'nullable|integer|exists:states,id',
            'zip_code' => 'nullable',
            'save_name' => 'nullable',
            'save_location_id' => 'nullable|integer|exists:additional_locations,id',
            'remote' => 'nullable',
            'schedule_type' => 'required|in:' . implode(',', GlobalConstant::ORDER_SCHEDULE_TYPE),
            'schedule_date' => 'nullable|required_if:schedule_type, Arrive at a specific date and time - (Hard Start)',
            'schedule_time' => 'nullable|required_if:schedule_type, Arrive at a specific date and time - (Hard Start)',
            'time_zone' => 'nullable',
            'schedule_date_between_1' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_date_between_2' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_time_between_1' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_time_between_2' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'between_date' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'between_time' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'through_date' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'through_time' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'work_order_manager_id' => 'nullable|integer',
            'additional_contact_info' => 'nullable|array',
            'new_documents_file' => 'nullable|array',
            'new_documents_file.*.documents_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            'old_documents_id' => 'nullable',
            'tasks' => 'required|array',
            'buyer_custom_field' => 'nullable|string',
            'pay_type' => 'required|in:' . implode(',', GlobalConstant::PAY_TYPE),
            'hourly_rate' => 'sometimes|required_if:pay_type,Hourly',
            'max_hours' => 'sometimes|required_if:pay_type,Hourly',
            'total_pay' => 'sometimes|required_if:pay_type,Fixed',
            'per_device_rate' => 'sometimes|required_if:pay_type,Per Device',
            'max_device' => 'sometimes|required_if:pay_type,Per Device',
            'fixed_payment' => 'sometimes|required_if:pay_type,Blended',
            'fixed_hours' => 'sometimes|required_if:pay_type,Blended',
            'additional_hourly_rate' => 'sometimes|required_if:pay_type,Blended',
            'max_additional_hour' => 'sometimes|required_if:,Blended',
            'bank_account_id' => 'nullable',
            'approximate_hour_complete' => 'nullable',
            'rule_id' => 'nullable',
            'shipment_info' => 'nullable|array',
            'status' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }


        $user = User::where('uuid', Auth::user()->uuid)->first();
        if ($user->stripe_account_id == null) {

            return response()->json([
                'status' => 'error',
                'message' => 'Work order cannot be submitted because the Stripe account is not connected yet.',
            ], 500);
        }
        if ($user->stripe_payment_method_id == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order cannot be submitted because the Stripe account is not connected yet.',
            ], 500);
        }
        $subscription_check = Subscription::where('uuid', Auth::user()->uuid)->first();

        if (!$subscription_check) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please upgrade your subscription.',
            ], 500);
        }
        if ($subscription_check->status !== "Active") {
            return response()->json([
                'status' => 'error',
                'message' => 'You subscription status is ' . $subscription_check->status,
            ], 500);
        }

        try {

            DB::beginTransaction();

            $uniqueMd5 = $this->generateUniqueWorkOrderId();

            if ($request->state_id) {

                $location_id = $this->getLocationId($request);
            }
            $additionalContactIds = $this->storeAdditionalContacts($request, $uniqueMd5);
            $shipment_arr = $this->storeShipments($request, $uniqueMd5);
            $allFilePaths = $this->storeDocuments($request);

            $tasks = $request->tasks;

            if ($request->hasFile('file')) {

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    if ($file->isValid()) {
                        $filePath = $file->store('tasks/files', 'public');
                        foreach ($tasks as $task) {
                            $task = (object) $task;
                            if (isset($task->file_name) && $task->file_name === 'abc.pdf') { // Match the specific file_name if needed
                                $task->file_name = $filePath;
                            }
                        }
                        unset($task);
                    } else {
                        throw new \Exception('File is not valid');
                    }
                }

                $tasksJson = json_encode($tasks);
            } else {
                $tasksJson = json_encode($request->tasks);
            }


            if ($request->pay_type == 'Hourly') {
                $total = $request->hourly_rate * $request->max_hours;
            }
            if ($request->pay_type == 'Blended') {
                $additional = $request->additional_hourly_rate * $request->max_additional_hour;
                $fixed = $request->fixed_payment;
                $total = $fixed + $additional ?? 0;
            }

            if ($request->pay_type == 'Fixed') {
                $total = $request->total_pay ?? 0.0;
            }
            if ($request->pay_type == 'Per Device') {
                $total = $request->per_device_rate * $request->max_device;
            }

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

            // $taxValue = $this->taxService->calculateTax($state, $country, $request, $total);

            $workOrder = new WorkOrder();
            $workOrder->uuid = Auth::user()->uuid;
            $workOrder->user_id = Auth::user()->id;
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
            $workOrder->location_id = $location_id ?? $request->save_location_id ?? $request->remote;
            $workOrder->additional_contact_id = json_encode($additionalContactIds);
            $workOrder->documents_file = json_encode($allFilePaths);
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
            $workOrder->tasks = $tasksJson ?? null;
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
            $workOrder->labor = $total;
            $workOrder->state_tax = $taxValue ?? 0.0;
            $workOrder->bank_account_id = Auth::user()->stripe_account_id;
            $workOrder->rule_id = $request->rule_id;
            $workOrder->shipment_id = json_encode($shipment_arr);
            $workOrder->status = $request->status ?? 'Published';

            if ($workOrder->save()) {
                $totalFees = 0;
                $service_fees = ServiceFees::where('plan_id', $subscription_check->plan_id)->where('status', 'Active')->get();

                $service_fees_array = $service_fees->map(function ($serviceFee) {
                    return [
                        'name' => $serviceFee->name,
                        'percentage' => $serviceFee->percentage,
                    ];
                })->toArray();

                $payment = new Payment();
                $payment->client_id = Auth::user()->uuid;
                $payment->account_id = Auth::user()->stripe_account_id;
                $payment->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
                $payment->work_order_unique_id = $uniqueMd5;
                $payment->services_fee = json_encode($service_fees_array);
                $payment->total_labor = $total;
                $payment->tax = $taxValue ?? 0.0;
                $payment->status = 'Pending';
                $payment->transaction_type = 'Payment';
                $payment->description = 'Payment Create For Work Order';
                $payment->save();
            }
            $history = new HistoryLog();
            $history->client_id = Auth::user()->id;
            $history->work_order_unique_id = $uniqueMd5;
            $history->description = 'Work Order Create.';
            $history->type = 'client';
            $history->date_time = now();
            $history->save();

            $this->sentNotification->providerNotifyWorkOrderCreate($workOrder);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'work_order' => new WorkOrderResource($workOrder),
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    private function getLocationId($request)
    {
        $country_name = Country::where('id', $request->country_id)->first();
        $state_name = State::where('id', $request->state_id)->first();

        // Construct the full address
        $full_address = "{$request->address_line_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";

        // Get latitude and longitude using the geocoding function
        $location = $this->CommonService->geocodeAddressOSM($full_address);

        if ($location) {
            $latitude = $location['latitude'];
            $longitude = $location['longitude'];
        } else {
            $latitude = null;
            $longitude = null;
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

    public function getTimezoneByCoordinates(Request $request)
    {
        $timezone = DB::table('timezones')->get();

        return response()->json([
            'status' => 'success',
            'timezones' => $timezone
        ]);

        $apiKey = env('GOOGLE_MAPS_API_KEY');

        $timestamp = time(); // Current timestamp
        $client = new \GuzzleHttp\Client();

        $country_name = Country::where('id', $request->country_id)->first();
        $state_name = State::where('id', $request->state_id)->first();

        // Get timezone based on latitude and longitude
        if (!empty($request->save_location_id)) {

            $present = AdditionalLocation::find($request->save_location_id);
            $url = "https://maps.googleapis.com/maps/api/timezone/json?location={$present->latitude},{$present->longitude}&timestamp={$timestamp}&key={$apiKey}";

            try {
                $response = $client->get($url);
                $data = json_decode($response->getBody(), true);

                if (!empty($data) && isset($data['timeZoneId'])) {

                    return response()->json([
                        'status' => 'success',
                        'timezones' => $data['timeZoneId']
                    ]);
                }

                // Fallback in case of API issues
                return 'Timezone not available';
            } catch (\Exception $error) {
                Log::error($error);
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 500);
            }
        } elseif (!empty($request->remote)) {
            $timezone = DB::table('timezones')->get();

            return response()->json([
                'status' => 'success',
                'timezones' => $timezone
            ]);
        } else {
            // Construct the full address
            $full_address = "{$request->address_line_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";

            // Get latitude and longitude using the geocoding function
            $location = $this->CommonService->geocodeAddressOSM($full_address);


            if ($location) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
            } else {
                $latitude = null;
                $longitude = null;
            }

            $url = "https://maps.googleapis.com/maps/api/timezone/json?location={$latitude},{$longitude}&timestamp={$timestamp}&key={$apiKey}";
            try {
                $response = $client->get($url);
                $data = json_decode($response->getBody(), true);

                if (!empty($data) && isset($data['timeZoneId'])) {
                    return response()->json([
                        'status' => 'success',
                        'timezones' => $data['timeZoneId']
                    ]);
                }

                // Fallback in case of API issues
                return 'Timezone not available';
            } catch (\Exception $error) {
                Log::error($error);
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 500);
            }
        }
    }

    public function subCategoryWiseService($id)
    {
        // Find the sub-category by ID
        $subCategory = WorkSubCategory::find($id);

        // Check if the sub-category exists
        if (!$subCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sub-category not found.',
            ], 404);
        }

        // Retrieve services associated with the sub-category
        $services = $subCategory->services;

        // Return the services as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $services,
        ]);
    }
    private function generateUniqueWorkOrderId()
    {
        do {
            $uniqueMd5 = Auth::user()->id . date('ymds');
        } while (WorkOrder::where('work_order_unique_id', $uniqueMd5)->exists());

        return $uniqueMd5;
    }

    private function storeAdditionalContacts($request, $uniqueMd5)
    {
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

    private function storeShipments($request, $uniqueMd5)
    {
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
    private function storeDocuments($request)
    {
        $allFilePaths       = [];
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

        return $allFilePaths;
    }

    public function getDocuments()
    {
        $document_libraries = DocumentLibrary::where('uuid', Auth::user()->uuid)->get();

        return response()->json([
            'status' => 'success',
            'document_libraries' => DocumentsResource::collection($document_libraries)
        ]);
    }

    public function getDocumentsTwo()
    {
        $uuid = Auth::user()->uuid;

        // 1. WorkOrders with client-provided files
        $workOrders = WorkOrder::select('work_order_unique_id', 'work_order_title', 'documents_file')
            ->where('uuid', $uuid)
            ->whereNotNull('documents_file')
            ->get()
            ->filter(fn($workOrder) => !empty($workOrder->documents_file))
            ->keyBy('work_order_unique_id');

        // 2. Provider-provided files
        $completeFiles = WorkOrderCompleteFile::with(['profile', 'workOrder'])
            ->where('client_uuid', $uuid)
            ->get()
            ->groupBy('work_order_unique_id');

        // Merge keys from both sources
        $allKeys = $workOrders->keys()->merge($completeFiles->keys())->unique();

        $documentLibraries = $allKeys->map(function ($workOrderId) use ($workOrders, $completeFiles) {
            $workOrder = $workOrders->get($workOrderId);

            // If not found in client side, try from provider's relation
            if (!$workOrder && $completeFiles->has($workOrderId)) {
                $workOrder = $completeFiles[$workOrderId]->first()->workOrder;
            }

            // Client files
            $clientFiles = collect();
            if ($workOrder && $workOrder->documents_file) {
                $clientFiles = is_array($workOrder->documents_file)
                    ? collect($workOrder->documents_file)
                    : collect(json_decode($workOrder->documents_file, true));

                $clientFiles = $clientFiles->map(fn($file) => asset('storage/' . ltrim($file, '/')));
            }

            // Provider files
            $providerFiles = $completeFiles->get($workOrderId, collect())->map(function ($file) {
                return [
                    'file'        => asset('storage/' . ltrim($file->file, '/')),
                    'description' => $file->description,
                    'provider'    => optional($file->profile)->name,
                ];
            });

            return [
                'work_order_unique_id'   => $workOrderId,
                'work_order_title'       => $workOrder->work_order_title, // ✅ always available
                'client_provided_file'   => $clientFiles->values(),
                'provider_provided_file' => $providerFiles->values(),
            ];
        })->values();


        return response()->json([
            'status' => 'success',
            'document_libraries' => $documentLibraries,
        ]);
    }

    public function edit($id)
    {
        try {

            $work_order = WorkOrder::query()
                ->findOrFail($id);
            if (!$work_order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found.',
                    'payload' => null,
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'work_order' => new WorkOrderResource($work_order),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $rules = [
            'work_order_title' => 'required|string|max:255',
            'default_client_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'export_bool' => 'nullable',
            'counter_offer_bool' => 'nullable',
            'gps_bool' => 'nullable',
            'service_description_public' => 'nullable|string',
            'service_description_note_private' => 'nullable|string',
            'work_category_id' => 'nullable|integer',
            'additional_work_category_id' => 'nullable|integer',
            'service_type_id' => 'nullable|integer',
            'qualification_type' => 'nullable|array',
            'display_name' => 'nullable',
            'location_type' => 'nullable|in:' . implode(',', GlobalConstant::LOCATION_TYPE),
            'country_id' => 'nullable',
            'address_line_1' => 'nullable',
            'address_line_2' => 'nullable',
            'city' => 'nullable',
            'state_id' => 'nullable',
            'zip_code' => 'nullable',
            'save_name' => 'nullable',
            'save_location_id' => 'nullable|integer',
            'remote' => 'nullable',
            'schedule_type' => 'nullable|in:' . implode(',', GlobalConstant::ORDER_SCHEDULE_TYPE),
            'schedule_date' => 'nullable|required_if:schedule_type, Arrive at a specific date and time - (Hard Start)',
            'schedule_time' => 'nullable|required_if:schedule_type, Arrive at a specific date and time - (Hard Start)',
            'time_zone' => 'nullable',
            'schedule_date_between_1' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_date_between_2' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_time_between_1' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'schedule_time_between_2' => 'nullable|required_if:schedule_type, Complete work between specific hours',
            'between_date' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'between_time' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'through_date' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'through_time' => 'nullable|required_if:schedule_type, Complete work anytime over a date range',
            'work_order_manager_id' => 'nullable|integer',
            'additional_contact_info' => 'nullable|array',
            'new_documents_file' => 'nullable',
            'old_documents_id' => 'nullable',
            'tasks' => 'nullable|array',
            'buyer_custom_field' => 'nullable|string',
            'pay_type' => 'required|in:' . implode(',', GlobalConstant::PAY_TYPE),
            'hourly_rate' => 'sometimes|required_if:pay_type,Hourly',
            'max_hours' => 'sometimes|required_if:pay_type,Hourly',
            'total_pay' => 'sometimes|required_if:pay_type,Fixed',
            'per_device_rate' => 'sometimes|required_if:pay_type,Per Device',
            'max_device' => 'sometimes|required_if:pay_type,Per Device',
            'fixed_payment' => 'sometimes|required_if:pay_type,Blended',
            'fixed_hours' => 'sometimes|required_if:pay_type,Blended',
            'additional_hourly_rate' => 'sometimes|required_if:pay_type,Blended',
            'max_additional_hour' => 'sometimes|required_if:,Blended',
            'bank_account_id' => 'nullable',
            'approximate_hour_complete' => 'nullable',
            'rule_id' => 'nullable',
            'shipment_info' => 'nullable|array',
            'status' => 'nullable',
        ];

        if (!$request->save_location_id && !$request->remote) {
            $rules = array_merge($rules, [
                'display_name' => 'required|string|max:255',
                'location_type' => 'required|in:' . implode(',', GlobalConstant::LOCATION_TYPE),
                'country_id' => 'required|integer',
                'address_line_1' => 'required|string',
                'address_line_2' => 'nullable|string',
                'city' => 'required|string',
                'state_id' => 'required|integer',
                'zip_code' => 'required|string',
                'save_name' => 'required|string',
            ]);
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $subscription_check = Subscription::where('uuid', Auth::user()->uuid)->first();

        if (!$subscription_check) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please upgrade your subscription',
            ], 500);
        }
        if ($subscription_check->status !== "Active") {
            return response()->json([
                'status' => 'error',
                'message' => 'You subscription status is ' . $subscription_check->status,
            ], 500);
        }

        try {

            $workOrder = WorkOrder::query()
                ->where('uuid', Auth::user()->uuid)
                ->where('user_id', Auth::user()->id)
                ->findOrFail($id);

            if ($workOrder->status !== 'Published' && $workOrder->status !== 'Draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work Order already ' . $workOrder->status . '. You cannot update this work order',
                ], 500);
            }


            if ($request->state_id) {

                $location_id = $this->getLocationId($request);
            }

            $additionalContactIds = [];
            if (!$request->save_location_id && !$request->remote) {
                $additionalContactIds = $this->updateAdditionalContacts($request, $workOrder->work_order_unique_id);
            }
            $shipment_arr = $this->updateShipments($request, $workOrder->work_order_unique_id);

            $allFilePaths = $this->storeDocuments($request);

            $tasks = $request->tasks;

            if ($request->hasFile('file')) {

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    if ($file->isValid()) {
                        $filePath = $file->store('tasks/files', 'public');
                        foreach ($tasks as $task) {
                            $task = (object) $task;
                            if (isset($task->file_name) && $task->file_name === 'abc.pdf') { // Match the specific file_name if needed
                                $task->file_name = $filePath;
                            }
                        }
                        unset($task);
                    } else {
                        throw new \Exception('File is not valid');
                    }
                }

                $tasksJson = json_encode($tasks);
            } else {
                $tasksJson = json_encode($request->tasks);
            }


            if ($request->pay_type == 'Hourly') {
                $total = $request->hourly_rate * $request->max_hours;
            }
            if ($request->pay_type == 'Blended') {
                $additional = $request->additional_hourly_rate * $request->max_additional_hour;
                $fixed = $request->fixed_payment;
                $total = $fixed + $additional ?? 0;
            }

            if ($request->pay_type == 'Fixed') {
                $total = $request->total_pay;
            }

            if ($request->pay_type == 'Per Device') {
                $total = $request->per_device_rate * $request->max_device;
            }

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

            // $taxValue = $this->taxService->calculateTax($state, $country, $request, $total);

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
            $workOrder->location_id = $location_id ?? $request->save_location_id ?? $request->remote;
            $workOrder->additional_contact_id = json_encode($additionalContactIds) ?? null;
            $workOrder->documents_file = json_encode($allFilePaths) ?? null;
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
            $workOrder->tasks = $tasksJson ?? null;
            $workOrder->buyer_custom_field = $request->buyer_custom_field;
            $workOrder->pay_type = $request->pay_type;
            $workOrder->hourly_rate = $request->hourly_rate;
            $workOrder->max_hours = $request->max_hours;
            $workOrder->approximate_hour_complete = $request->approximate_hour_complete;
            $workOrder->total_pay = $request->total_pay;
            $workOrder->per_device_rate = $request->per_device_rate;
            $workOrder->max_device = $request->max_device;
            $workOrder->fixed_payment = $request->fixed_payment;
            $workOrder->fixed_hours = $request->fixed_hours;
            $workOrder->additional_hourly_rate = $request->additional_hourly_rate;
            $workOrder->max_additional_hour = $request->max_additional_hour;
            $workOrder->labor = $total;
            $workOrder->state_tax = $taxValue ?? 0.0;
            $workOrder->bank_account_id = $request->bank_account_id;
            $workOrder->rule_id = $request->rule_id;
            $workOrder->shipment_id = json_encode($shipment_arr);
            $workOrder->status = $request->status ?? 'Published';

            if ($workOrder->save()) {

                $service_fees = ServiceFees::where('plan_id', $subscription_check->plan_id)->where('status', 'Active')->get();

                $service_fees_array = $service_fees->map(function ($serviceFee) {
                    return [
                        'name' => $serviceFee->name,
                        'percentage' => $serviceFee->percentage,
                    ];
                })->toArray();

                $payment = Payment::query()
                    ->where('client_id', Auth::user()->uuid)
                    ->where('work_order_unique_id', $workOrder->work_order_unique_id)
                    ->first();

                $payment->account_id = $request->bank_account_id;
                $payment->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
                $payment->services_fee = json_encode($service_fees_array);
                $payment->total_labor = $total;
                $payment->tax = $taxValue ?? 0.0;
                $payment->save();
            }
            $history = HistoryLog::query()
                ->where('client_id', Auth::user()->id)
                ->where('work_order_unique_id', $workOrder->work_order_unique_id)
                ->first();

            $history->description = 'Work Order updated.';
            $history->type = 'client';
            $history->date_time = now();
            $history->save();

            $this->sentNotification->providerNotifyWorkOrderCreate($workOrder);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Work Order Successfully Updated',
                'work_order' => new WorkOrderResource($workOrder),
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    private function updateAdditionalContacts($request, $workOrderUniqueId)
    {
        $additionalContactIds = [];

        if (isset($request->additional_contact_info) && (is_array($request->additional_contact_info) || is_object($request->additional_contact_info))) {
            foreach ($request->additional_contact_info as $contactName) {
                $contactName = (object) $contactName;


                $additionalContactConditions = [
                    'user_id' => Auth::user()->id,
                    'work_order_unique_id' => $workOrderUniqueId,
                    'id' => $contactName->additional_contact_id ?? null
                ];

                $additional_contact = AdditionalContact::firstOrCreate($additionalContactConditions);

                $additional_contact->name = $contactName->additional_contact_name ?? null;
                $additional_contact->title = $contactName->additional_contact_title ?? null;
                $additional_contact->phone = $contactName->additional_contact_phone ?? null;
                $additional_contact->ext = $contactName->additional_contact_ext ?? null;
                $additional_contact->email = $contactName->additional_contact_email ?? null;
                $additional_contact->note = $contactName->additional_contact_note ?? null;
                $additional_contact->save();

                $additionalContactIds[] = $additional_contact->id;
            }
        }

        return $additionalContactIds;
    }

    private function updateShipments($request, $workOrderUniqueId)
    {
        $shipment_arr = [];
        $shipmentInfos = $request->shipment_info ?? [];

        if (!is_array($shipmentInfos)) {
            return $shipment_arr;
        }

        foreach ($shipmentInfos as $key => $shipmentInfo) {

            $shipmentInfo = (object) $shipmentInfo;

            if (empty($shipmentInfo->tracking_number) || empty($shipmentInfo->shipment_description)) {
                continue;
            }

            $shipmentConditions = [
                'user_id' => Auth::user()->id,
                'work_order_unique_id' => $workOrderUniqueId,
                'id' => $shipmentInfo->shipment_id ?? null
            ];

            $shipment = Shipment::firstOrCreate($shipmentConditions);

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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Fetch work order
            $workOrder = WorkOrder::findOrFail($id);

            if ($workOrder->status !== 'Draft' || $workOrder->status !== 'Published') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work Order already ' . $workOrder->status . '. You cannot delete this work order',
                ], 500);
            }
            // Delete related AdditionalContacts (if applicable)
            if (!empty($workOrder->additional_contact_id)) {
                $contactIds = json_decode($workOrder->additional_contact_id, true);
                if (is_array($contactIds)) {
                    AdditionalContact::whereIn('id', $contactIds)->delete();
                }
            }

            // Delete related Shipments
            if (!empty($workOrder->shipment_id)) {
                $shipmentIds = json_decode($workOrder->shipment_id, true);
                if (is_array($shipmentIds)) {
                    Shipment::whereIn('id', $shipmentIds)->delete();
                }
            }

            // Delete related Documents (if stored as paths, remove from storage too)
            if (!empty($workOrder->documents_file)) {
                $documents = json_decode($workOrder->documents_file, true);
                foreach ($documents as $filePath) {
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }

            // Delete related Payment
            Payment::where('work_order_unique_id', $workOrder->work_order_unique_id)->delete();

            // Delete related HistoryLog
            HistoryLog::where('work_order_unique_id', $workOrder->work_order_unique_id)->delete();

            // Delete the WorkOrder
            $workOrder->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work order deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Work order deletion failed: " . $e->getMessage());

            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                [ServerErrorMask::SERVER_ERROR]
            );

            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function work_order_status_update($id)
    {
        try {
            $work_order = WorkOrder::where('work_order_unique_id', $id)->first();

            // Toggle logic
            if ($work_order->status === 'Draft') {
                $work_order->status = 'Published';
            } elseif ($work_order->status === 'Published') {
                $work_order->status = 'Draft';
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid status for toggle. Only Draft and Published are allowed.',
                ], 400);
            }

            $work_order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Order status successfully toggled to ' . $work_order->status,
                'new_status' => $work_order->status,
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }


    public function all_work_order(Request $request)
    {
        try {
            $profile = Profile::where('user_id', Auth::user()->id)->first();
            $userLat = $profile->latitude;
            $userLong = $profile->longitude;

            $areaPerKm = $request->area_per_km ?? null;

            $interestWorkOrderIds = Interest::where('provider_id', Auth::user()->id)
                ->pluck('work_order_unique_id')
                ->toArray();

            $workOrderReport = WorkOrderReport::where('provider_id', Auth::user()->id)
                ->pluck('work_order_unique_id')
                ->toArray();

            $work_orders = WorkOrder::query()
                ->selectRaw("work_orders.*,
                (
                    CASE
                        WHEN additional_location.latitude IS NOT NULL AND additional_location.longitude IS NOT NULL THEN
                            6371 * acos(
                                cos(radians(?)) *
                                cos(radians(additional_location.latitude)) *
                                cos(radians(additional_location.longitude) - radians(?)) +
                                sin(radians(?)) *
                                sin(radians(additional_location.latitude))
                            )
                        ELSE NULL
                    END
                ) AS distance", [$userLat, $userLong, $userLat])
                ->leftJoin('additional_locations as additional_location', 'work_orders.location_id', '=', 'additional_location.id')
                ->with([
                    'template',
                    'default_client',
                    'project',
                    'work_category',
                    'additional_work_category',
                    'service_type',
                    'additional_location',
                    'manager',
                    'additional_contact',
                    'shipment',
                    'bank_account'
                ])
                ->when($request->keyword, fn($q) => $q->where('work_order_title', 'LIKE', '%' . $request->keyword . '%'))
                ->when($request->category_id, fn($q) => $q->whereIn('work_category_id', $request->category_id))
                ->when($areaPerKm, function ($q) use ($areaPerKm) {
                    $q->havingRaw('(distance <= ? OR distance IS NULL)', [$areaPerKm]);
                })
                ->where(function ($query) use ($interestWorkOrderIds) {
                    $query->where(function ($q) use ($interestWorkOrderIds) {
                        $q->whereNotIn('work_order_unique_id', $interestWorkOrderIds)
                            ->whereNull('assigned_id');
                    })
                        ->orWhere('assigned_id', Auth::user()->id);
                })
                ->where(function ($query) {
                    $query->where('status', 'Published')
                        ->orWhere(function ($query) {
                            $query->whereIn('status', ['Assigned', 'Complete'])
                                ->where('assigned_id', Auth::user()->id)
                                ->whereNotNull('assigned_id');
                        });
                })
                ->orderByRaw('distance IS NULL, distance ASC') // nulls last
                ->get();

            $work_orders = $work_orders->map(function ($work_order) use ($workOrderReport) {
                $work_order->has_report = in_array($work_order->work_order_unique_id, $workOrderReport);
                $work_order->distance = $work_order->distance ?? 'N/A';
                return $work_order;
            });

            return response()->json([
                'status' => 'success',
                'work_order' => $work_orders,
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function calculateDistance($lat1, $long1, $lat2, $long2)
    {
        $earthRadius = 6371; // Radius of the earth in kilometers

        $latDistance = deg2rad($lat2 - $lat1);
        $longDistance = deg2rad($long2 - $long1);

        $a = sin($latDistance / 2) * sin($latDistance / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($longDistance / 2) * sin($longDistance / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c; // Distance in kilometers
    }

    public function single_work_order(Request $request, $id)
    {
        try {

            $work_order = WorkOrder::where('work_order_unique_id', $id)->first();

            if (!$work_order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'work_order' => new SingleWorkOrderResource($work_order),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
    public function workRequestList($work_unique_id)
    {
        try {
            $workOrder = WorkOrder::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->first();
            if (!$workOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found',
                ], 404);
            }

            $workRequests = SendWorkRequest::with('user')
                ->where('work_order_unique_id', $workOrder->work_order_unique_id)
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'work_request' => SendWorkRequestResource::collection($workRequests)
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
    public function viewWorkRequest($work_unique_id, $id)
    {
        try {

            $workRequests = SendWorkRequest::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'work_request' => new SendWorkRequestResource($workRequests),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function assignedWorkOrder(Request $request, $work_unique_id, $id)
    {
        $rules = [
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $user = User::where('uuid', Auth::user()->uuid)->first();
        if (is_null($user->stripe_customer_id) && is_null($user->stripe_payment_method_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account not found',
            ]);
        }

        DB::beginTransaction();

        try {
            $workRequest = SendWorkRequest::query()
                ->select(['id', 'work_order_unique_id', 'uuid', 'user_id', 'status'])
                ->where('status', 'Active')
                ->with([
                    'users' => fn($q) => $q->select(['id', 'uuid', 'username', 'email'])
                ])
                ->where('work_order_unique_id', $work_unique_id)
                ->findOrFail($id);

            $workOrder = WorkOrder::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->whereNull('assigned_id')
                ->first();
            if ($request->status == 0) {
                $workRequest->update(['status' => 'Inactive']);
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Send work request is now inactive',
                ]);
            }

            if (is_null($workOrder)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found or already assigned',
                ], 404);
            }

            if ($request->status == 1) {
                $workOrder->assigned_status = "Assigned";
                $workOrder->assigned_id = $workRequest->user_id;
                $workOrder->assigned_uuid = $workRequest->users->uuid;
                $workOrder->status = "Assigned";
                $workOrder->provider_status = "Assigned";
                try {
                    $payIntent = $this->stripeService->workOrderAssigne($user, $work_unique_id, $workRequest->user_id);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Payment Intent creation failed: ' . $e->getMessage());
                    $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
                    return response()->json([
                        'status' => 'error',
                        'message' => $systemError,
                    ], 500);
                }
                if ($workOrder->save()) {
                    $payment = Payment::where('work_order_unique_id', $work_unique_id)->first();
                    $payment->update(['provider_id' => $workRequest->user_id]);
                }

                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $workRequest->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->description = 'Work Order Assigned.';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();

                // $payIntent = $this->stripeService->workOrderAssigne($user, $work_unique_id, $workRequest->user_id);

                $this->sentNotification->workOrderRequestAssignSent($workOrder->refresh(), $workRequest);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User successfully assigned',
                'work_order' => $workOrder,
                'paymentIntent' => $payIntent ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
    public function counterOfferList($work_unique_id)
    {
        try {
            $workOrder = WorkOrder::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->first();
            if (!$workOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found',
                ], 404);
            }

            $counterOffer = CounterOffer::query()
                ->where('work_order_unique_id', $workOrder->work_order_unique_id)
                ->where('status', 'Active')
                ->with([
                    'counterOfferPay',
                    'counterOfferSchedule',
                    'uuidUser',
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'counter_offer' => CounterOfferResource::collection($counterOffer),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
    public function viewCounterOffer($work_unique_id, $id)
    {
        try {

            $counterOffer = CounterOffer::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->with([
                    'counterOfferPay',
                    'counterOfferSchedule',
                    'uuidUser',
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'counter_offer' => new CounterOfferResource($counterOffer),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function assignedCounterOfferWorkOrder(Request $request, $work_unique_id, $id)
    {
        try {
            $user = User::where('uuid', Auth::user()->uuid)->first();
            if ($user->stripe_customer_id == null && $user->stripe_payment_method_id == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account not found',
                ]);
            }
            $counterOffer = CounterOffer::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->with(['user'])
                ->findOrFail($id);

            $workOrder = WorkOrder::query()
                ->select(['id', 'work_order_unique_id', 'assigned_id', 'status', 'assigned_status'])
                ->where('work_order_unique_id', $work_unique_id)
                ->where('assigned_id', null)
                ->first();

            if ($request->status === 0) {

                $counterOffer->update(['status' => 'Inactive']);
                $this->sentNotification->counterOfferAssignSent($workOrder->refresh(), $counterOffer, 'no');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Counter Offer Successfully Inactive',
                ]);
            }

            if (!$workOrder) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found or allready work assigned',
                ], 404);
            }

            if ($request->status == 1) {

                $workOrder->assigned_status = "Assigned";
                $workOrder->assigned_id = $counterOffer->user_id;
                $workOrder->assigned_uuid = $counterOffer->user?->uuid;
                $workOrder->status = $request->status == 1 ? "Assigned" : $workOrder->status; // Only update if status is 1
                $workOrder->provider_status = "Assigned";
                $workOrder->save();

                $payment = Payment::where('work_order_unique_id', $work_unique_id)->first();
                $payment->update(['provider_id' => $counterOffer->user_id]);

                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $counterOffer->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->description = 'Work Order Assigned With Counter Offer';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();
                $payIntent = $this->stripeService->workOrderAssigne($request, $work_unique_id, $counterOffer->user_id);
                $this->sentNotification->counterOfferAssignSent($workOrder->refresh(), $counterOffer, 'yes');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Counter offer Successfully Assigned',
                'work_order' => $workOrder,
                'paymentIntent' => $payIntent
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function getAdditionalLocation()
    {
        try {

            $additional_locations = AdditionalLocation::where('uuid', Auth::user()->uuid)->get();
            // $location = Location::where('uuid', Auth::user()->uuid)->get();

            return response()->json([
                'status' => 'success',
                'additional_locations' => $additional_locations,
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    // Expense Request & Pay Change Request
    public function expenseRequestList($work_unique_id)
    {
        try {
            $expenseRequests = ExpenseRequest::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'expenseCategory' => fn($q) => $q->select(['id', 'name']),
                    'workOrder',
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'expense_request' => ExpenseRequestResource::collection($expenseRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Expense request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function viewExpenseRequest($work_unique_id, $id)
    {
        try {
            $expenseRequests = ExpenseRequest::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'expenseCategory' => fn($q) => $q->select(['id', 'name']),
                    'workOrder',
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'expense_request' => new ExpenseRequestResource($expenseRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Expense request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function expenseRequestApprove(Request $request, $work_unique_id, $id)
    {
        $rules = [
            'status' => 'required|in:Accept,Declined',
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

            $workOrder = WorkOrder::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->first();
            $expenseRequests = ExpenseRequest::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->findOrFail($id);

            $payment = Payment::query()
                ->where("work_order_unique_id", $work_unique_id)->where('client_id', $workOrder->uuid)
                ->first();

            if (in_array($expenseRequests->status, ['Accept', 'Declined'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This expense request has already been ' . $expenseRequests->status . '.',
                ], 400);
            }

            if ($request->status === 'Declined') {

                $expenseRequests->update(['status' => $request->status]);

                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $expenseRequests->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->expense_request_id = $expenseRequests->id;
                $history->description = 'Expense Request Declined';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Expense Request Successfully Declined',
                ]);
            }
            if ($request->status === 'Accept') {
                $payment->expense_fee += $expenseRequests->amount;
                $payment->save();

                $expenseRequests->update(['status' => $request->status]);
                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $expenseRequests->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->expense_request_id = $expenseRequests->id;
                $history->description = 'Expense Request Accepted';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();
            }
            $this->sentNotification->expenseRequestApproveSent($expenseRequests, $request->status);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense Request Successfully Accept',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function payChangeList($work_unique_id)
    {
        try {
            $payChanges = PayChange::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'workOrder',
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'pay_change' => PayChangeResource::collection($payChanges),
            ]);
        } catch (\Exception $e) {
            Log::error('Pay change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function viewPayChange($work_unique_id, $id)
    {
        try {
            $payChanges = PayChange::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'workOrder',
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'pay_change' => new PayChangeResource($payChanges),
            ]);
        } catch (\Exception $e) {
            Log::error('Pay change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function payChangeWorkOrder(Request $request, $work_unique_id, $id)
    {
        $rules = [
            'status' => 'required|in:Accept,Declined',
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

            $workOrder = WorkOrder::query()
                ->where("work_order_unique_id", $work_unique_id)
                ->first();

            $payChanges = PayChange::query()
                ->with(['workOrder'])
                ->findOrFail($id);

            $payment = Payment::query()
                ->where("work_order_unique_id", $work_unique_id)->where('client_id', $workOrder->uuid)
                ->first();

            if (in_array($payChanges->status, ['Accept', 'Declined'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This pay change has already been ' . $payChanges->status . '.',
                ], 400);
            }

            if ($request->status === 'Declined') {

                $payChanges->update(['status' => $request->status]);

                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $payChanges->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->paychange_id = $payChanges->id;
                $history->description = 'Payment Change Declined';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pay Change Successfully Declined',
                ]);
            }
            if ($request->status === 'Accept') {
                $amount = $payChanges?->workOrder?->hourly_rate * $payChanges->extra_hour;

                $payment->pay_change_fee += $amount;
                $payment->save();

                $payChanges->update(['status' => $request->status]);

                $history = new HistoryLog();
                $history->client_id = Auth::user()->id;
                $history->provider_id = $payChanges->user_id;
                $history->work_order_unique_id = $work_unique_id;
                $history->paychange_id = $payChanges->id;
                $history->description = 'Payment Change Accepted';
                $history->type = 'client';
                $history->date_time = now();
                $history->save();
            }
            $this->sentNotification->payChangeApproveSent($payChanges, $request->status);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pay Change Successfully Accept',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pay Change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    //*****************Work Order review part****************************

    public function reviewByProvider(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'rating' => 'required|in:1,2,3,4,5',
            'review_text' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $work_order = WorkOrder::where('work_order_unique_id', $request->work_order_unique_id)->first();

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        $work_order->update([
            // 'status' => 'Approved',
            'provider_status' => 'Completed',
        ]);

        $review = Review::create([
            'uuid' => $work_order->uuid,
            'provider_id' => Auth::user()->id,
            'client_id' => $work_order->user_id,
            'work_order_unique_id' => $request->work_order_unique_id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'tag' => 'provider',
            'created_at' => now(),
        ]);

        $history = new HistoryLog();
        $history->provider_id = Auth::user()->id;
        $history->work_order_unique_id = $request->work_order_unique_id;
        $history->description = 'Work Order Reviewed';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        return response()->json([
            'status' => 'success',
            'work_order' => new SingleWorkOrderResource($work_order),
            'reviews' => new ReviewResource($review),
        ]);
    }
    public function getReviewByProvider(Request $request)
    {
        $request->validate([
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
        ]);

        $review = Review::where('work_order_unique_id', $request->work_order_unique_id)
            ->where('provider_id', Auth::id())
            ->where('tag', 'provider')
            ->first();

        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'review' => new ReviewResource($review),
        ]);
    }
    public function updateReviewByProvider(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'rating' => 'required|in:1,2,3,4,5',
            'review_text' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $review = Review::where('work_order_unique_id', $request->work_order_unique_id)
            ->where('provider_id', Auth::id())
            ->where('tag', 'provider')
            ->first();

        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Review not found for update.',
            ], 404);
        }

        $review->update([
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'updated_at' => now(),
        ]);

        $history = new HistoryLog();
        $history->provider_id = Auth::id();
        $history->work_order_unique_id = $request->work_order_unique_id;
        $history->description = 'Work Order Review Updated';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully.',
            'review' => new ReviewResource($review),
        ]);
    }

    public function reviewByClient(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'rating' => 'required|in:1,2,3,4,5',
            'review_text' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $work_order = WorkOrder::where('work_order_unique_id', $request->work_order_unique_id)->first();

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        if ($work_order->assigned_uuid == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not assigned to any provider',
            ], 404);
        }

        if ($work_order->status === 'Complete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order already completed',
            ], 400);
        }

        $work_order->update([
            'status' => $work_order->status == 'Done' ? 'Done' : 'Complete',
            'assigned_status' => 'Complete',
        ]);

        $review = Review::create([
            'uuid' => $work_order->assigned_uuid,
            'provider_id' => $work_order->assigned_id,
            'client_id' => Auth::user()->id,
            'work_order_unique_id' => $request->work_order_unique_id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'tag' => 'client',
            'created_at' => now(),
        ]);


        return response()->json([
            'status' => 'success',
            'work_order' => new SingleWorkOrderResource($work_order),
            'reviews' => new ReviewResource($review),
        ]);
    }

    public function getReview(Request $request, $work_order_unique_id)
    {
        $client_review = Review::where('work_order_unique_id', $work_order_unique_id)->where('tag', 'provider')->first();

        $provider_review = Review::where('work_order_unique_id', $work_order_unique_id)->where('tag', 'client')->first();

        return response()->json([
            'status' => 'success',
            'client_review' => $client_review ? new ProviderReviewResource($client_review) : null,
            'provider_review' => $provider_review ? new ClientReviewResource($provider_review) : null,
        ]);
    }

    //work order mark complete part
    public function providerMarkComplete(Request $request, $work_order_unique_id)
    {
        $rules = [
            'files' => 'required|array|min:1', // Validate an array of files
            'files.*' => 'file|mimes:jpg,jpeg,png,heic,pdf,doc,docx|max:5120', // 5MB max size
            'description' => 'required|string|min:10',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $work_order = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();
        $provider_checkout = ProviderCheckout::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$provider_checkout) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not started yet.',
            ], 404);
        }

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        if ($work_order->provider_status === 'Complete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order already completed',
            ], 400);
        }

        $work_order->update([
            'provider_status' => 'Completed',
        ]);
        $provider_checkout->update(['is_check_out' => 'yes']);

        $tasks = json_decode($work_order->tasks, true);
        $notificationEmails = collect($tasks)
            ->firstWhere('name', 'Check out')['notification_email'] ?? [];

        foreach ($notificationEmails as $email) {
            Mail::send('emails.task_started', [
                'taskName' => 'Check out',
                'startTime' => now()->format('Y-m-d H:i:s')
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Task "Check out" has started');
            });
        }
        $filePaths = [];
        foreach ($request->file('files') as $file) {
            $filePath = $file->store('work_order_files', 'public'); // Store file in "storage/app/public/work_order_files"
            $filePaths[] = $filePath;

            // Save each file with its details
            WorkOrderCompleteFile::create([
                'client_uuid' => $work_order->client_uuid,
                'client_id' => $work_order->user_id,
                'provider_uuid' => Auth::user()->uuid,
                'provider_id' => Auth::user()->id,
                'work_order_unique_id' => $work_order_unique_id,
                'file' => $filePath,
                'description' => $request->description,
            ]);
        }

        $history = new HistoryLog();
        $history->provider_id = Auth::user()->id;
        $history->work_order_unique_id = $work_order_unique_id;
        $history->description = 'Work Order Mark Completed';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        $this->sentNotification->markCompletedByProvider($work_order_unique_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Work order completed successfully',
            'work_order' => new SingleWorkOrderResource($work_order),
        ]);
    }

    public function getWorkOrderCompleteFile(Request $request, $work_order_unique_id)
    {
        $work_order = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        $work_order_complete_files = WorkOrderCompleteFile::where('work_order_unique_id', $work_order_unique_id)->get();

        // return response()->json([
        //     'status' => 'success',
        //     'work_order_complete_files' => WorkOrderCompleteFileResource::collection($work_order_complete_files),
        // ]);
        $provider = $work_order_complete_files->first()->profile ?? null; // Assuming all files share the same provider
        $description = $work_order_complete_files->first()->description ?? null; // Get the description from the first file

        return response()->json([
            'status' => 'success',
            'provider' => new ProviderResource($provider),
            'description' => $description,
            'files' => $work_order_complete_files->map(fn($file) => Storage::url($file->file)),
        ]);
    }

    //report problem
    public function providerSendReportProblem(Request $request, $work_order_unique_id)
    {
        $rules = [
            'type' => 'required|string',
            'description' => 'required|string|min:10',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $report = new WorkOrderReport();
        $report->provider_id = Auth::user()->id;
        $report->work_order_unique_id = $work_order_unique_id;
        $report->type = $request->type;
        $report->description = $request->description;
        $report->save();

        $history = new HistoryLog();
        $history->provider_id = Auth::user()->id;
        $history->work_order_unique_id = $work_order_unique_id;
        $history->description = 'Work Order Reported By Provider';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Report sent successfully',
            'report' => $report,
        ]);
    }

    public function workOrderReportProblemShow($work_order_unique_id)
    {
        $report = WorkOrderReport::with('provider.profile')->where('work_order_unique_id', $work_order_unique_id)->get();
        return response()->json([
            'status' => 'success',
            'report' => $report,
        ]);
    }

    public function notInterest($work_order_unique_id)
    {
        $work_order = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();

        if (!$work_order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order not found',
            ], 404);
        }

        $interest = new Interest();
        $interest->work_order_unique_id = $work_order_unique_id;
        $interest->provider_id = Auth::user()->id;
        $interest->save();

        $history = new HistoryLog();
        $history->provider_id = Auth::user()->id;
        $history->work_order_unique_id = $work_order_unique_id;
        $history->description = 'Work Order Not Interested By Provider';
        $history->type = 'provider';
        $history->date_time = now();
        $history->save();

        return response()->json([
            'status' => 'success',
            'interest' => $interest,
        ]);
    }

    //provider export work order
    public function exportWorkOrder(Request $request)
    {
        Log::info('Export Work Order Request: ' . json_encode($request->all()));

        // Validation rules for the request
        $rules = [
            'work_order_unique_id' => 'required|string|exists:work_orders,work_order_unique_id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        try {
            // Fetch the work order with related data
            $workOrder = WorkOrder::with([
                'user' => function ($query) {
                    $query->select('id', 'uuid', 'username', 'email', 'organization_role', 'stripe_account_id');
                },
                'default_client' => function ($query) {
                    $query->select('id', 'client_title', 'company_name_logo', 'website', 'address_line_one', 'address_line_two', 'city', 'state_id', 'zip_code', 'country_id');
                },
                'project' => function ($query) {
                    $query->select('id', 'title', 'default_client_id', 'project_manager_id');
                },
                'work_category' => function ($query) {
                    $query->select('id', 'name');
                },
                'additional_work_category' => function ($query) {
                    $query->select('id', 'name');
                },
                'service_type' => function ($query) {
                    $query->select('id', 'name', 'work_sub_category_id');
                },
                'additional_location' => function ($query) {
                    $query->select('id', 'display_name', 'location_type', 'address_line_1', 'address_line_2', 'city', 'state_id', 'state_name', 'country_id', 'country_name', 'zip_code', 'latitude', 'longitude', 'phone', 'email', 'note');
                },
                'manager' => function ($query) {
                    $query->select('id', 'name', 'email', 'address_one', 'address_two', 'country_id', 'state', 'zip_code');
                },
                'additionalContacts' => function ($query) {
                    $query->select('id', 'work_order_unique_id', 'name', 'title', 'phone', 'ext', 'email', 'note');
                },
                'shipments' => function ($query) {
                    $query->select('id', 'work_order_unique_id', 'tracking_number', 'shipment_description', 'shipment_carrier', 'shipment_carrier_name', 'shipment_direction');
                },
                'bank_account' => function ($query) {
                    $query->select('id', 'account_holder_name', 'country', 'currency', 'status');
                },
                'payment' => function ($query) {
                    $query->select('id', 'work_order_unique_id', 'client_id', 'provider_id', 'total_labor', 'tax', 'services_fee', 'extra_fee', 'extra_fee_note', 'payment_date_time', 'gateway', 'status', 'transaction_type', 'description');
                },
                'template' => function ($query) {
                    $query->select('id', 'template_name', 'work_order_title', 'public_description', 'private_description');
                }
            ])
                ->where('work_order_unique_id', $request->work_order_unique_id)
                ->first();

            if (!$workOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order not found.',
                    'payload' => null,
                ], 404);
            }

            // Transform the data for the response
            $workOrderData = [
                'work_order_unique_id' => $workOrder->work_order_unique_id,
                'title' => $workOrder->work_order_title,
                'template' => $workOrder->template ? [
                    'id' => $workOrder->template->id,
                    'template_name' => $workOrder->template->template_name,
                    'work_order_title' => $workOrder->template->work_order_title,
                    'public_description' => $workOrder->template->public_description,
                    'private_description' => $workOrder->template->private_description,
                ] : null,
                'default_client' => $workOrder->default_client ? [
                    'id' => $workOrder->default_client->id,
                    'client_title' => $workOrder->default_client->client_title,
                    'company_name_logo' => $workOrder->default_client->company_name_logo,
                    'website' => $workOrder->default_client->website,
                    'address_line_one' => $workOrder->default_client->address_line_one,
                    'address_line_two' => $workOrder->default_client->address_line_two,
                    'city' => $workOrder->default_client->city,
                    'state' => $workOrder->default_client->state ? $workOrder->default_client->state->name : null,
                    'zip_code' => $workOrder->default_client->zip_code,
                    'country' => $workOrder->default_client->country ? $workOrder->default_client->country->name : null,
                ] : null,
                'project' => $workOrder->project ? [
                    'id' => $workOrder->project->id,
                    'title' => $workOrder->project->title,
                    'project_manager' => $workOrder->project->project_manager ? [
                        'id' => $workOrder->project->project_manager->id,
                        'name' => $workOrder->project->project_manager->name,
                    ] : null,
                ] : null,
                'service_description_public' => $workOrder->service_description_public,
                'service_description_note_private' => $workOrder->service_description_note_private,
                'work_category' => $workOrder->work_category ? [
                    'id' => $workOrder->work_category->id,
                    'name' => $workOrder->work_category->name,
                ] : null,
                'additional_work_category' => $workOrder->additional_work_category ? [
                    'id' => $workOrder->additional_work_category->id,
                    'name' => $workOrder->additional_work_category->name,
                ] : null,
                'service_type' => $workOrder->service_type ? [
                    'id' => $workOrder->service_type->id,
                    'name' => $workOrder->service_type->name,
                    'work_sub_category' => $workOrder->service_type->workSubCategory ? $workOrder->service_type->workSubCategory->name : null,
                ] : null,
                'qualification_type' => $this->transformQualificationType($workOrder->qualification_type),
                'location' => $workOrder->additional_location ? [
                    'id' => $workOrder->additional_location->id,
                    'display_name' => $workOrder->additional_location->display_name,
                    'location_type' => $workOrder->additional_location->location_type,
                    'address_line_1' => $workOrder->additional_location->address_line_1,
                    'address_line_2' => $workOrder->additional_location->address_line_2,
                    'city' => $workOrder->additional_location->city,
                    'state_name' => $workOrder->additional_location->state_name,
                    'country_name' => $workOrder->additional_location->country_name,
                    'zip_code' => $workOrder->additional_location->zip_code,
                    'latitude' => $workOrder->additional_location->latitude,
                    'longitude' => $workOrder->additional_location->longitude,
                    'phone' => $workOrder->additional_location->phone,
                    'email' => $workOrder->additional_location->email,
                    'note' => $workOrder->additional_location->note,
                ] : ($workOrder->location_id === 'remote' ? 'remote' : null),
                'schedule_type' => $workOrder->schedule_type,
                'schedule_date' => $workOrder->schedule_date,
                'schedule_time' => $workOrder->schedule_time,
                'time_zone' => $workOrder->time_zone,
                'schedule_date_between_1' => $workOrder->schedule_date_between_1,
                'schedule_date_between_2' => $workOrder->schedule_date_between_2,
                'schedule_time_between_1' => $workOrder->schedule_time_between_1,
                'schedule_time_between_2' => $workOrder->schedule_time_between_2,
                'between_date' => $workOrder->between_date,
                'between_time' => $workOrder->between_time,
                'through_date' => $workOrder->through_date,
                'through_time' => $workOrder->through_time,
                'work_order_manager' => $workOrder->manager ? [
                    'id' => $workOrder->manager->id,
                    'name' => $workOrder->manager->name,
                    'email' => $workOrder->manager->email,
                    'address' => $workOrder->manager->address_one ?? null . '-' . $workOrder->manager->address_two ?? null,
                    'state' => $workOrder->manager->state_name ? $workOrder->manager->state_name->name : null,
                    'country' => $workOrder->manager->country ? $workOrder->manager->country->name : null,
                    'zip_code' => $workOrder->manager->zip_code,
                ] : null,
                'additional_contacts' => $workOrder->additionalContacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'title' => $contact->title,
                        'phone' => $contact->phone,
                        'ext' => $contact->ext,
                        'email' => $contact->email,
                        'note' => $contact->note,
                    ];
                })->toArray(),
                'shipments' => $workOrder->shipments->map(function ($shipment) {
                    return [
                        'id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'description' => $shipment->shipment_description,
                        'carrier' => $shipment->shipment_carrier,
                        'carrier_name' => $shipment->shipment_carrier_name,
                        'direction' => $shipment->shipment_direction,
                    ];
                })->toArray(),
                'documents' => $workOrder->documents_file ? json_decode($workOrder->documents_file, true) : [],
                'tasks' => $workOrder->tasks ? json_decode($workOrder->tasks, true) : [],
                'buyer_custom_field' => $workOrder->buyer_custom_field,
                'pay_type' => $workOrder->pay_type,
                'hourly_rate' => $workOrder->hourly_rate,
                'max_hours' => $workOrder->max_hours,
                'approximate_hour_complete' => $workOrder->approximate_hour_complete,
                'total_pay' => $workOrder->total_pay,
                'per_device_rate' => $workOrder->per_device_rate,
                'max_device' => $workOrder->max_device,
                'fixed_payment' => $workOrder->fixed_payment,
                'fixed_hours' => $workOrder->fixed_hours,
                'additional_hourly_rate' => $workOrder->additional_hourly_rate,
                'max_additional_hour' => $workOrder->max_additional_hour,
                'labor' => $workOrder->labor,
                'state_tax' => $workOrder->state_tax,
                'payment' => $workOrder->payment ? [
                    'id' => $workOrder->payment->id,
                    'total_labor' => $workOrder->payment->total_labor,
                    'tax' => $workOrder->payment->tax,
                    'services_fee' => $workOrder->payment->services_fee ? json_decode($workOrder->payment->services_fee, true) : [],
                ] : null,

            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Work order data retrieved for export.',
                'payload' => $workOrderData,
            ], 200);
        } catch (\Exception $error) {
            Log::error("Error exporting work order: " . $error->getMessage());
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function transformQualificationType($qualification_type)
    {
        // Decode the outer JSON string
        $outerDecoded = json_decode($qualification_type, true);

        // Ensure $outerDecoded is an array and contains items
        if (is_array($outerDecoded)) {
            // Process each type object in the array
            return array_map(function ($type) {
                // Ensure $type is an array and contains 'id' and 'sub_categories'
                if (!is_array($type) || !isset($type['id']) || !isset($type['sub_categories'])) {
                    return [
                        'id' => null,
                        'name' => null,
                        'sub_categories' => [],
                    ];
                }

                $typeId = $type['id'];
                $subCategories = $type['sub_categories'];

                // Fetch the QualificationType and its subcategories
                $qualificationType = QualificationType::find($typeId);

                return [
                    'id' => $typeId,
                    'name' => $qualificationType ? $qualificationType->name : null,
                    'sub_categories' => QualificationSubCategory::whereIn('id', $subCategories)->get()->map(function ($subCategory) {
                        return [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                        ];
                    }),
                ];
            }, $outerDecoded);
        }

        // Handle case where outer result is not as expected
        return [];
    }
}
