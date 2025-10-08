<?php

namespace App\Http\Controllers\Provider\WorkRequest;

use DateTime;
use App\Models\User;
use App\Models\Profile;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\SendWorkRequest;
use App\Models\EmployeeProvider;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Classes\NotificationSentClass;
use App\Http\Resources\CheckingResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserLicenseResource;
use App\Http\Resources\Provider\SendWorkRequestResource;
use App\Http\Resources\EmpResourceForLicenceAndCertificate;

class SendWorkRequestsController extends Controller
{
    protected $sentNotification;

    public function __construct(NotificationSentClass $sentNotification)
    {
        $this->sentNotification = $sentNotification;

        // $this->middleware('permission:send_work_requests,send_work_requests.list')->only(['index']);
        $this->middleware('permission:send_work_requests.create_store')->only(['store']);
        $this->middleware('permission:send_work_requests.Edit')->only(['edit']);
        $this->middleware('permission:send_work_requests.update')->only(['update']);
        $this->middleware('permission:send_work_requests.delete')->only(['destroy']);
    }

    public function index()
    {
        try {
            $workRequests = SendWorkRequest::query()->get();

            return response()->json([
                'status' => 'success',
                'work_request' => SendWorkRequestResource::collection($workRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Work Request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'work_request' => 'required|array|min:1',
            'work_request.*.employed_provider_id' => 'nullable',
            'work_request.*.after_withdraw' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $profile_check = Profile::where('user_id', Auth::user()->id)->first();
        if ($profile_check->profile_status == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please update your profile first.',
            ], 422);
        }
        $workReqSends = collect($request->work_request)->map(function ($data) {
            return $data = (object) $data;
        });

        $checkOrderStatus = $this->validateWorkOrderStatus($request);

        if ($checkOrderStatus[0] == false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work order already ' . $checkOrderStatus[1]->status . '. You can not assign this work order',
            ], 409);
        }

        try {
            // DB::beginTransaction();

            foreach ($workReqSends as $work) {

                $afterWithdraw = $work->after_withdraw;
                $currentDateTime = new DateTime();

                if (strpos($afterWithdraw, 'Minute') !== false) {
                    $minutes = intval($afterWithdraw);
                    $currentDateTime->modify("+{$minutes} minutes");
                } elseif (strpos($afterWithdraw, 'Hour') !== false) {
                    $hours = intval($afterWithdraw);
                    $currentDateTime->modify("+{$hours} hours");
                } elseif (strpos($afterWithdraw, 'Day') !== false) {
                    $days = intval($afterWithdraw);
                    $currentDateTime->modify("+{$days} days");
                }

                $present_check = SendWorkRequest::where('work_order_unique_id', $request->work_order_unique_id)
                    ->where('uuid', Auth::user()->uuid)
                    ->where('status', 'Active');

                if ($present_check->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Work Request Already Exists',
                    ], 422);
                }

                $workRequests = new SendWorkRequest();
                $workRequests->work_order_unique_id = $request->work_order_unique_id;
                $workRequests->uuid = Auth::user()->uuid;
                $workRequests->user_id = Auth::user()->id;
                $workRequests->after_withdraw = $work->after_withdraw;
                $workRequests->request_date_time = date('Y-m-d H:i:s', strtotime(now()));
                $workRequests->status = 'Active';
                $workRequests->expired_request_time = $currentDateTime->format('Y-m-d H:i:s');
                $workRequests->save();

                $history = new HistoryLog();
                $history->provider_id = Auth::user()->id;
                $history->work_order_unique_id = $request->work_order_unique_id;
                $history->work_order_send_request_id = $workRequests->id;
                $history->description = 'Work Order Request';
                $history->type = 'provider';
                $history->date_time = now();
                $history->save();

                $this->sentNotification->workRequestSent($workRequests);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Request Successfully Save',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store filed' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function validateWorkOrderStatus($request)
    {
        $work = WorkOrder::query()
            ->where('work_order_unique_id', $request->work_order_unique_id)
            ->first();

        if ($work->status == 'Active' || $work->status == 'Published') {
            return [true, $work];
        }

        return [false, $work];
    }

    public function validateWorkOrderQualification($request)
    {
        $work = WorkOrder::query()
            ->select(['id', 'work_order_unique_id', 'qualification_type'])
            ->where('work_order_unique_id', $request->work_order_unique_id)
            ->first();

        $employedProviderIds = collect($request->work_request)
            ->pluck('employed_provider_id')
            ->filter()
            ->unique()
            ->toArray();

        $employeeProviders = EmployeeProvider::query()
            ->whereIn('id', $employedProviderIds)
            ->with([
                'licenseCertificate'
            ])
            ->get();

        $qualificationType = json_decode($work->qualification_type, true);

        $certificationTypeId = 1;
        $licenseTypeId = 2;
        $equipmentTypeId = 3;
        $insuranceTypeId = 4;

        // Extract required qualification categories
        $requiredCertificates = collect($qualificationType)->firstWhere('id', $certificationTypeId)['sub_categories'] ?? [];
        $requiredLicenses = collect($qualificationType)->firstWhere('id', $licenseTypeId)['sub_categories'] ?? [];
        // $requiredEquipment = collect($qualificationType)->firstWhere('id', $equipmentTypeId)['sub_categories'] ?? [];
        // $requiredInsurance = collect($qualificationType)->firstWhere('id', $insuranceTypeId)['sub_categories'] ?? [];

        $allQualified = true;
        $unqualifiedProviders = [];

        if (!$employeeProviders->count() > 0) {
            return [false, 'Something went wrong. Please try again'];
        }

        foreach ($employeeProviders as $provider) {

            $qualified = true;

            if (isset($provider->licenseCertificate)) {
                $providerCertificates = $provider->licenseCertificate->pluck('certificate_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                $providerLicenses = $provider->licenseCertificate->pluck('license_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                // $providerEquipment = $provider->licenseCertificate->pluck('equipment_id')
                //     ->filter()
                //     ->unique()
                //     ->toArray();

                // $providerInsurance = $provider->licenseCertificate->pluck('insurance_id')
                //     ->filter()
                //     ->unique()
                //     ->toArray();

                // Check for required certifications
                if (!empty($requiredCertificates) && array_diff($requiredCertificates, $providerCertificates)) {
                    $qualified = false;
                }

                // Check for required licenses
                if (!empty($requiredLicenses) && array_diff($requiredLicenses, $providerLicenses)) {
                    $qualified = false;
                }

                // Check for required equipment
                // if (!empty($requiredEquipment) && array_diff($requiredEquipment, $providerEquipment)) {
                //     $qualified = false;
                // }

                // Check for required insurance
                // if (!empty($requiredInsurance) && array_diff($requiredInsurance, $providerInsurance)) {
                //     $qualified = false;
                // }

                if (!$qualified) {
                    $allQualified = false;
                    $unqualifiedProviders[] = $provider->first_name;
                }
            }
        }

        if (!$allQualified) {
            return [false, $unqualifiedProviders];
        }

        return [true];
    }

    public function edit($id)
    {
        try {
            $workRequests = SendWorkRequest::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Work Request Successfully Save',
                'work_request' =>  new SendWorkRequestResource($workRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Work Request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $workRequests = SendWorkRequest::query()
                ->findOrFail($id);

            $workRequests->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Request Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Work Request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    // public function work_order_id_wise_employed_provider(Request $request)
    // {
    //     $rules = [
    //         'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 422);
    //     }

    //     try {
    //         if (Auth::user()->organization_role == 'Provider') {
    //             $employeeProviders = User::where('uuid', Auth::user()->uuid)
    //             ->orWhere('id', Auth::user()->id)
    //             ->with(['licenseAndCertificates' => function ($query) {
    //                 $query->where('status', 'Approved'); // Only fetch Approved licenses and certificates
    //             }])
    //             ->get();
    //         }else{

    //             $employeeProviders = EmployeeProvider::where('uuid', Auth::user()->uuid)
    //             ->orWhere('provider_id', Auth::user()->id)
    //             ->with(['licenseCertificate' => function ($query) {
    //                 $query->where('status', 'Approved'); // Only fetch Approved licenses and certificates
    //             }])
    //             ->get();
    //         }

    //         if ($employeeProviders->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'employee_providers' => 'Did not find any employee providers for this user.',
    //                 // 'work_order' => new CheckingResource($work_order),
    //             ]);
    //         }
    //         $work_order = WorkOrder::where('work_order_unique_id', $request->work_order_unique_id)->first();

    //         // Safely decode qualification_type with a fallback to an empty array
    //         $qualificationTypes = json_decode($work_order->qualification_type, true) ?? [];

    //         if (!is_array($qualificationTypes)) {
    //             throw new \Exception("Invalid qualification_type format for work order ID {$work_order->id}");
    //         }

    //         $workOrderLicenseSubCategoryIds = [];
    //         $workOrderCertificateSubCategoryIds = [];

    //         foreach ($qualificationTypes as $qualification) {
    //             $qualificationId = $qualification['id'] ?? null;
    //             $subCategories = $qualification['sub_categories'] ?? [];

    //             if (!is_array($subCategories)) {
    //                 throw new \Exception("Invalid sub_categories format for qualification ID {$qualificationId}");
    //             }

    //             // Assume that qualification ID '1' is for licenses and '2' is for certifications
    //             if ($qualificationId == 2) {
    //                 $workOrderLicenseSubCategoryIds = array_merge($workOrderLicenseSubCategoryIds, $subCategories);
    //             } elseif ($qualificationId == 1) {
    //                 $workOrderCertificateSubCategoryIds = array_merge($workOrderCertificateSubCategoryIds, $subCategories);
    //             }
    //         }

    //         $totalSubCategories = count($workOrderLicenseSubCategoryIds) + count($workOrderCertificateSubCategoryIds);

    //         $providerResults = [];

    //         foreach ($employeeProviders as $employeeProvider) {
    //             $matchCount = 0;
    //             $mismatchCount = 0;

    //             // Store the IDs of licenses and certificates held by the employee
    //             $employeeLicenseIds = $employeeProvider->licenseCertificate->pluck('license_id')->filter()->toArray();
    //             $employeeCertificateIds = $employeeProvider->licenseCertificate->pluck('certificate_id')->filter()->toArray();

    //             // Count matches for licenses
    //             foreach ($workOrderLicenseSubCategoryIds as $requiredLicenseId) {
    //                 if (in_array($requiredLicenseId, $employeeLicenseIds)) {
    //                     $matchCount++;
    //                 } else {
    //                     $mismatchCount++;
    //                 }
    //             }

    //             // Count matches for certificates
    //             foreach ($workOrderCertificateSubCategoryIds as $requiredCertificateId) {
    //                 if (in_array($requiredCertificateId, $employeeCertificateIds)) {
    //                     $matchCount++;
    //                 } else {
    //                     $mismatchCount++;
    //                 }
    //             }

    //             // Store the results for the current provider
    //             $providerResults[] = [
    //                 'employee_provider' => new EmpResourceForLicenceAndCertificate($employeeProvider),
    //                 'match_count' => $matchCount,
    //                 'mismatch_count' => $mismatchCount,
    //                 'total' => $totalSubCategories,
    //             ];
    //         }

    //         // Return the response
    //         return response()->json([
    //             'status' => 'success',
    //             'employee_providers' => $providerResults,
    //             'work_order' => new CheckingResource($work_order),
    //         ]);
    //     } catch (\Exception $e) {
    //         // Log the error and return a failure response
    //         Log::error("Error in work_order_id_wise_employed_provider: " . $e->getMessage(), [
    //             'userId' => Auth::id(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while processing the request.',
    //         ], 500);
    //     }
    // }

    public function work_order_id_wise_employed_provider(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            if (Auth::user()->organization_role == 'Provider') {
                $employeeProviders = User::where('uuid', Auth::user()->uuid)
                    ->orWhere('id', Auth::user()->id)
                    ->with(['licenseAndCertificates' => function ($query) {
                        $query->where('status', 'Approved');
                    }])
                    ->get();
            } else {
                $employeeProviders = EmployeeProvider::where('uuid', Auth::user()->uuid)
                    ->orWhere('provider_id', Auth::user()->id)
                    ->with(['licenseCertificate' => function ($query) {
                        $query->where('status', 'Approved');
                    }])
                    ->get();
            }

            if ($employeeProviders->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'employee_providers' => 'Did not find any employee providers for this user.',
                ]);
            }

            $work_order = WorkOrder::where('work_order_unique_id', $request->work_order_unique_id)->first();

            $qualificationTypes = json_decode($work_order->qualification_type, true) ?? [];

            if (!is_array($qualificationTypes)) {
                throw new \Exception("Invalid qualification_type format for work order ID {$work_order->id}");
            }

            $workOrderLicenseSubCategoryIds = [];
            $workOrderCertificateSubCategoryIds = [];

            foreach ($qualificationTypes as $qualification) {
                $qualificationId = $qualification['id'] ?? null;
                $subCategories = $qualification['sub_categories'] ?? [];

                if (!is_array($subCategories)) {
                    throw new \Exception("Invalid sub_categories format for qualification ID {$qualificationId}");
                }

                if ($qualificationId == 1) { // Licenses
                    $workOrderLicenseSubCategoryIds = array_merge($workOrderLicenseSubCategoryIds, $subCategories);
                } elseif ($qualificationId == 2) { // Certificates
                    $workOrderCertificateSubCategoryIds = array_merge($workOrderCertificateSubCategoryIds, $subCategories);
                }
            }

            $totalSubCategories = count($workOrderLicenseSubCategoryIds) + count($workOrderCertificateSubCategoryIds);

            $providerResults = [];

            foreach ($employeeProviders as $employeeProvider) {
                $matchCount = 0;
                $mismatchCount = 0;

                // Handle User vs EmployeeProvider differently
                if (Auth::user()->organization_role == 'Provider') {
                    $licensesAndCerts = $employeeProvider->licenseAndCertificates;
                    $employeeLicenseIds = $licensesAndCerts->pluck('license_id')->filter()->toArray();
                    $employeeCertificateIds = $licensesAndCerts->pluck('certificate_id')->filter()->toArray();
                } else {
                    $licensesAndCerts = $employeeProvider->licenseCertificate;
                    $employeeLicenseIds = $licensesAndCerts->pluck('license_id')->filter()->toArray();
                    $employeeCertificateIds = $licensesAndCerts->pluck('certificate_id')->filter()->toArray();
                }

                foreach ($workOrderLicenseSubCategoryIds as $requiredLicenseId) {
                    if (in_array($requiredLicenseId, $employeeLicenseIds)) {
                        $matchCount++;
                    } else {
                        $mismatchCount++;
                    }
                }

                foreach ($workOrderCertificateSubCategoryIds as $requiredCertificateId) {
                    if (in_array($requiredCertificateId, $employeeCertificateIds)) {
                        $matchCount++;
                    } else {
                        $mismatchCount++;
                    }
                }

                if(Auth::user()->organization_role == 'Provider'){
                    $providerResults[] = [
                        'employee_provider' => new UserLicenseResource($employeeProvider),
                        'match_count' => $matchCount,
                        'mismatch_count' => $mismatchCount,
                        'total' => $totalSubCategories,
                    ];
                }else{

                    $providerResults[] = [
                        'employee_provider' => new EmpResourceForLicenceAndCertificate($employeeProvider),
                        'match_count' => $matchCount,
                        'mismatch_count' => $mismatchCount,
                        'total' => $totalSubCategories,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'employee_providers' => $providerResults,
                'work_order' => new CheckingResource($work_order),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in work_order_id_wise_employed_provider: " . $e->getMessage(), [
                'userId' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the request.',
            ], 500);
        }
    }
}
