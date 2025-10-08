<?php

namespace App\Http\Controllers\Common;

use App\Models\User;
use App\Models\About;
use App\Models\State;
use GuzzleHttp\Client;
use App\Models\Company;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\ZipCode;
use App\Models\SkillSet;
use App\Models\Education;
use App\Models\Equipment;
use App\Models\WorkOrder;
use Avalara\AvaTaxClient;
use App\Models\HistoryLog;
use App\Models\Transaction;
use App\Models\WorkSummery;
use App\Models\CounterOffer;
use App\Models\WorkCategory;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Utils\ServerErrorMask;
use App\Models\SendWorkRequest;
use App\Models\WorkSubCategory;
use App\Classes\FileUploadClass;
use App\Models\EmploymentHistory;
use App\Models\QualificationType;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LicenseAndCertificate;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ProfileResource;
use App\Models\QualificationSubCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\HistoryLogResource;

class CommonController extends Controller
{
    protected $fileUpload;
    protected $userService;
    public function __construct(FileUploadClass $fileUpload, UserService $userService)
    {
        $this->fileUpload = $fileUpload;
        $this->userService = $userService;
    }

    public function type_wise_cat(Request $request)
    {
        $qualification_sub_cats = QualificationSubCategory::with('qualification')->where('qualification_type_id', $request->qualification_id)->get();
        // Group by qualification name
        $grouped = $qualification_sub_cats->groupBy(function ($item) {
            return $item->qualification->name;
        });

        // Transform the grouped data
        $result = $grouped->map(function ($group) {
            return [
                'id' => $group->first()->qualification->id,
                'name' => $group->first()->qualification->name,
                'qualification_type_id' => $group->first()->qualification_type_id,
                'qualification_sub_cats' => $group->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'status' => 'success',
            'qualifications' => $result,
        ]);
    }
    public function qualification_type()
    {
        try {

            $qualification_type = QualificationType::all();

            return response()->json([
                'status' => 'success',
                'qualification_type' => $qualification_type,
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

    public function country_data()
    {
        try {

            $country = Country::with('states')->get();

            return response()->json([
                'status' => 'success',
                'country' => $country,
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

    public function state_wise_zip_code(Request $request)
    {
        $rules = [
            'state_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        $zip_code = ZipCode::with('state')->where('state_id', $request->state_id)->get();

        return response()->json([
            'status' => 'success',
            'zip_code' => $zip_code,
        ]);
    }
    public function companyUpdate(Request $request)
    {
        $rules = [
            'company_name' => 'required',
            'company_bio' => 'nullable',
            'logo' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'about_us' => 'nullable',
            'types_of_work' => 'nullable',
            'skill_sets' => 'nullable',
            'equipments' => 'nullable',
            'licenses' => 'nullable',
            'certifications' => 'nullable',
            'employed_providers' => 'nullable',
            'address' => 'nullable',
            'company_website' => 'nullable',
            'annual_revenue' => 'nullable',
            'need_technicians' => 'nullable',
            'employee_counter' => 'nullable',
            'technicians_hire' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $company = Company::query()
                ->where('user_id', Auth::user()->id)
                ->first();

            $company->company_name = $request->company_name;
            $company->company_bio = $request->company_bio;
            $company->about_us = $request->about_us;
            $company->types_of_work = $request->types_of_work;
            $company->skill_sets = $request->skill_sets;
            $company->equipments = $request->equipments;
            $company->licenses = $request->licenses;
            $company->certifications = $request->certifications;
            $company->employed_providers = $request->employed_providers;
            $company->address = $request->address;
            $company->company_website = $request->company_website;
            $company->annual_revenue = $request->annual_revenue;
            $company->need_technicians = $request->need_technicians;
            $company->employee_counter = $request->employee_counter;
            $company->technicians_hire = $request->technicians_hire;

            if ($request->hasFile("logo")) {
                $image_url = $this->fileUpload->imageUploader($request->file('logo'), 'company', 800, 600);
                $company->logo = $image_url;
            }

            $company->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'company' => new CompanyResource($company->refresh()),
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

    public function qualification_list_of_license_and_certificate()
    {
        try {
            $qualifications = QualificationSubCategory::with('qualification')
                ->whereIn('qualification_type_id', [1, 2])
                ->get()
                ->groupBy(function ($item) {
                    return $item->qualification_type_id == 1 ? 'certifications' : 'licenses';
                });

            $formattedQualifications = $qualifications->map(function ($group) {
                return $group->map(function ($qualification) {
                    return [
                        'id' => $qualification->id,
                        'qualification_type_id' => $qualification->qualification_type_id,
                        'name' => $qualification->name,
                    ];
                });
            });

            return response()->json([
                'status' => 'success',
                'qualifications' => $formattedQualifications,
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

    public function updateUserProfile(Request $request)
    {

        Log::info('Update User Profile Request Data:', $request->all());
        $rules = $this->getValidationRules($request->type);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $userInfo = Auth::user();

            if ($request->type == 'basic') {


                $country_name = Country::where('id', $request->country_id)->first();
                $state_name = State::where('id', $request->state_id)->first();

                if (!empty($request->address_1) && !empty($request->city) && !empty($request->state_id) && !empty($request->zip_code) && !empty($request->country_id)) {
                    $full_address = "{$request->address_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";
                    $location = $this->userService->geocodeAddressOSM($full_address);
                    if ($location) {
                        $latitude = $location['latitude'];
                        $longitude = $location['longitude'];
                    } else {
                        $latitude = null;
                        $longitude = null;
                    }
                }
                // $full_address = "{$request->address_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";

                $user = User::query()
                    ->findOrFail($userInfo->id);
                $user->username = $request->username;
                $user->save();

                $profile = Profile::where('user_id', $userInfo->id)->first();
                $profile->first_name = $request->first_name ?? $profile->first_name;
                $profile->last_name = $request->last_name ?? $profile->last_name;
                $profile->phone = $request->phone ?? $profile->phone;
                $profile->country_id = $request->country_id ?? $profile->country_id;
                $profile->state_id = $request->state_id ?? $profile->state_id;
                $profile->city = $request->city ?? $profile->city;
                $profile->address_1 = $request->address_1 ?? $profile->address_1;
                $profile->address_2 = $request->address_2 ?? $profile->address_2;
                $profile->zip_code = $request->zip_code ?? $profile->zip_code;
                $profile->latitude = $latitude ?? null;
                $profile->longitude = $longitude ?? null;
                $profile->social_security_number = $request->social_security_number;
                $profile->why_chosen_us = $request->why_chosen_us;
                $profile->profile_status = 1;

                if ($request->hasFile("profile_image")) {
                    $this->fileUpload->fileUnlink($profile->profile_image);
                    $image_url = $this->fileUpload->imageUploader($request->file('profile_image'), 'profile', 200, 200);
                    $profile->profile_image = $image_url;
                }
                $profile->save();

                if ($user->organization_role == "Provider Company" || $user->organization_role == "Client") {
                    $company = Company::where('user_id', $userInfo->id)->first();
                    $company->company_name = $request->company_name;
                    $company->annual_revenue = $request->annual_revenue;
                    $company->need_technicians = $request->need_technicians;
                    $company->employee_counter = $request->employee_counter;
                    $company->technicians_hire = $request->technicians_hire;
                    $company->company_bio = $request->company_bio;
                    $company->about_us = $request->about_us;
                    $company->address = $request->company_address;
                    $company->company_website = $request->company_website;
                    $company->save();
                }
            }

            if ($request->type == 'about') {

                $this->about($request, $userInfo);
            }

            if ($request->type == 'work_summery') {

                $this->workSummery($request, $userInfo);
            }

            if ($request->type == 'skill_set') {
                $this->skillSet($request, $userInfo);
            }

            if ($request->type == 'equipments') {
                $this->equipments($request, $userInfo);
            }

            if ($request->type == 'employment_history') {
                $this->employmentHistory($request->input('employment_history'), $userInfo);
            }

            if ($request->type == 'education') {
                $this->education($request->input('education'), $userInfo);
            }

            // if ($request->type == 'license_certificate') {
            //     $this->licenseAndCertificate($request, $userInfo);
            // }

            DB::commit();


            $updateUser = User::query()
                ->with([
                    'profile',
                    'companies',
                    'about',
                    'workSummery',
                    'skillSet',
                    'equipment',
                    'employmentHistory',
                    'education',
                    'licenseAndCertificates',
                ])
                ->findOrFail($userInfo->id);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $updateUser->id,
                    'organization_role' => $updateUser->organization_role,
                    'username' => $updateUser->username,
                    'email' => $updateUser->email,
                    'role' => $updateUser->role,
                    'status' => $updateUser->status,
                    'profile' => $updateUser->profiles->isNotEmpty() ? new ProfileResource($updateUser->profiles->first()) : null,
                    'company' => $updateUser->companies->isNotEmpty() ? new CompanyResource($updateUser->companies->first()) : null,
                    'about' => $updateUser->about,
                    'workSummery' => $updateUser->workSummery,
                    'skillSet' => $updateUser->skillSet,
                    'equipment' => $updateUser->equipment,
                    'employmentHistory' => $updateUser->employmentHistory,
                    'education' => $updateUser->education,
                    'licenseAndCertificates' => $updateUser->licenseAndCertificates,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        }
    }

    private function getValidationRules($type)
    {
        switch ($type) {
            case 'about':
                return [
                    'tagline' => 'nullable|string|max:255',
                    'biography' => 'nullable|string',
                ];
            case 'basic':
                return [
                    'username' => 'nullable',
                    'first_name' => 'nullable',
                    'last_name' => 'nullable',
                    'phone' => 'nullable|string|max:15',
                    'why_chosen_us' => 'nullable|string',
                    'company_name' => 'nullable',
                    'annual_revenue' => 'nullable',
                    'need_technicians' => 'nullable',
                    'employee_counter' => 'nullable',
                    'technicians_hire' => 'nullable',
                    'company_bio' => 'nullable',
                    'about_us' => 'nullable',
                    // 'country_id' => 'required|exists:countries,id',
                    // 'state_id' => 'required|exists:states,id',
                    // 'city' => 'required',
                    // 'address_1' => 'required',
                    // 'address_2' => 'nullable',
                    // 'zip_code' => 'required',
                    'social_security_number' => 'nullable|required_if:country_id,1',
                    'company_address' => 'nullable',
                    'company_website' => 'nullable',
                    'profile_image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
                ];
            case 'work_summery':
                return [
                    'work_summery_category_ids' => 'required|array',
                    'work_summery_category_ids.*' => 'exists:work_sub_categories,id',
                ];

            case 'skill_set':
                return [
                    'skill_set_names' => 'required|array',
                ];
            case 'equipments':
                return [
                    'equipment_names' => 'required|array',
                ];
            case 'employment_history':
                return [
                    'employment_history' => 'required|array',
                    'employment_history.*.id' => 'nullable',
                    'employment_history.*.company_name' => 'nullable|string|max:255',
                    'employment_history.*.position' => 'nullable|string|max:255',
                    'employment_history.*.start_date' => 'nullable|date|before_or_equal:today',
                    'employment_history.*.end_date' => 'nullable|date|after_or_equal:employment_history.*.start_date',
                    'employment_history.*.location' => 'nullable|string|max:255',
                    'employment_history.*.description' => 'nullable|string|max:1500',
                ];
            case 'education':
                return [
                    'education' => 'required|array',
                    'education.*.id' => 'nullable',
                    'education.*.school_name' => 'nullable|string|max:255',
                    'education.*.degree' => 'nullable|string|max:255',
                    'education.*.field_of_study' => 'nullable|string|max:255',
                    'education.*.start_date' => 'nullable|date|before:education.*.end_date',
                    'education.*.end_date' => 'nullable|date|after:education.*.start_date',
                    'education.*.location' => 'nullable|string|max:255',
                    'education.*.activities' => 'nullable|string|max:500',
                ];
                // case 'license_certificate':
                //     return [
                //         'license_id' => 'nullable',
                //         'certificate_id' => 'nullable',
                //         'state_name' => 'nullable',
                //         'license_number' => 'nullable',
                //         'applicable_work_category_id' => 'nullable',
                //         'certificate_number' => 'nullable',
                //         'issue_date' => 'nullable',
                //         'expired_date' => 'nullable',
                //         'file' => 'nullable|mimes:png,jpg,jpeg,pdf|max:10240',
                //     ];
            default:
                return [];
        }
    }

    //new 6

    public function about($request, $userInfo)
    {
        About::where('employee_provider_id', null)
            ->where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->delete();

        About::create([
            'employee_provider_id' => null,
            'user_id' => $userInfo->id,
            'uuid' => $userInfo->uuid,
            'tagline' => $request->tagline ?? null,
            'biography' => $request->biography ?? null,
        ]);

        return true;
    }

    public function workSummery($request, $userInfo)
    {
        WorkSummery::where('employee_provider_id', null)
            ->where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->delete();

        foreach ($request->work_summery_category_ids as $value) {
            $workSubcategory = WorkSubCategory::find($value);
            $workCategory = WorkCategory::find($workSubcategory->cat_id);

            if ($workCategory) {
                WorkSummery::create([
                    'employee_provider_id' => null,
                    'user_id' => $userInfo->id,
                    'uuid' => $userInfo->uuid,
                    'work_category_id' => $value,
                    'work_category_name' => $workCategory->name,
                    'work_sub_category_id' => $workSubcategory->id,
                    'work_sub_category_name' => $workSubcategory->name,
                ]);
            }
        }

        return true;
    }

    public function skillSet($request, $userInfo)
    {
        SkillSet::where('employee_provider_id', null)
            ->where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->delete();

        foreach ($request->skill_set_names as $value) {
            SkillSet::create([
                'employee_provider_id' => null,
                'user_id' => $userInfo->id,
                'uuid' => $userInfo->uuid,
                'name' => trim($value),
            ]);
        }

        return true;
    }

    public function equipments($request, $userInfo)
    {
        Equipment::where('employee_provider_id', null)
            ->where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->delete();

        foreach ($request->equipment_names as $value) {
            Equipment::create([
                'employee_provider_id' => null,
                'user_id' => $userInfo->id,
                'uuid' => $userInfo->uuid,
                'name' => trim($value),
            ]);
        }

        return true;
    }

    public function employmentHistory($employmentHistoryArray, $userInfo)
    {
        // Keep track of IDs that are present in the request
        $existingIds = [];

        foreach ($employmentHistoryArray as $employment) {
            $record = EmploymentHistory::updateOrCreate(
                [
                    'id'   => $employment['id'] ?? null, // Use id if passed
                    'user_id' => $userInfo->id,
                    'uuid' => $userInfo->uuid,
                ],
                [
                    'employee_provider_id' => null,
                    'company_name' => $employment['company_name'],
                    'position' => $employment['position'],
                    'start_date' => $employment['start_date'],
                    'end_date' => $employment['end_date'],
                    'location' => $employment['location'],
                    'description' => $employment['description'],
                ]
            );

            $existingIds[] = $record->id;
        }

        // Delete records not in the new array
        EmploymentHistory::where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->whereNotIn('id', $existingIds)
            ->delete();

        return true;
    }
    public function education($educationArray, $userInfo)
    {
        $existingIds = [];

        foreach ($educationArray as $educationData) {
            if (isset($educationData['id'])) {
                // Update existing record
                $education = Education::where('id', $educationData['id'])
                    ->where('user_id', $userInfo->id)
                    ->where('uuid', $userInfo->uuid)
                    ->first();

                if ($education) {
                    $education->update([
                        'school_name'   => $educationData['school_name'],
                        'degree'        => $educationData['degree'],
                        'field_of_study' => $educationData['field_of_study'],
                        'start_date'    => $educationData['start_date'],
                        'end_date'      => $educationData['end_date'],
                        'location'      => $educationData['location'],
                        'activities'    => $educationData['activities'],
                    ]);

                    $existingIds[] = $education->id;
                }
            } else {
                // Create new record
                $newEducation = Education::create([
                    'employee_provider_id' => null,
                    'user_id'       => $userInfo->id,
                    'uuid'          => $userInfo->uuid,
                    'school_name'   => $educationData['school_name'],
                    'degree'        => $educationData['degree'],
                    'field_of_study' => $educationData['field_of_study'],
                    'start_date'    => $educationData['start_date'],
                    'end_date'      => $educationData['end_date'],
                    'location'      => $educationData['location'],
                    'activities'    => $educationData['activities'],
                ]);

                $existingIds[] = $newEducation->id;
            }
        }

        // Delete records that are not in the new array
        Education::where('user_id', $userInfo->id)
            ->where('uuid', $userInfo->uuid)
            ->whereNotIn('id', $existingIds)
            ->delete();

        return true;
    }

    // new 6 end

    // license & certificate
    public function licenseAndCertificateCommon(Request $request)
    {
        Log::info('Request Data:', $request->all());

        if (!isset($request->license_certificate) || !is_array($request->license_certificate)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data: license_certificate is missing or not an array',
            ], 400);
        }

        $rules = [
            'license_certificate.*.id' => 'nullable|exists:license_and_certificates,id',
            'license_certificate.*.license_id' => 'nullable',
            'license_certificate.*.certificate_id' => 'nullable',
            'license_certificate.*.state_name' => 'nullable',
            'license_certificate.*.license_number' => 'nullable',
            'license_certificate.*.applicable_work_category_id' => 'nullable',
            'license_certificate.*.certificate_number' => 'nullable',
            'license_certificate.*.issue_date' => 'nullable|date',
            'license_certificate.*.expired_date' => 'nullable|date',
            'license_certificate.*.file' => 'nullable', // file or string path or null
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $user = Auth::user();

            $conditions = $user->organization_role == 'Provider' || $user->organization_role == 'Provider Company'
                ? ['employee_provider_id' => null, 'provider_id' => $user->id]
                : ['employee_provider_id' => null, 'client_id' => $user->id];

            $existingIds = [];

            foreach ($request->license_certificate as $data) {
                if (isset($data['id'])) {
                    // Update existing
                    $licenseCertificate = LicenseAndCertificate::where('id', $data['id'])
                        ->where($conditions)
                        ->first();

                    if ($licenseCertificate) {
                        $licenseCertificate->fill([
                            'license_id' => $data['license_id'] ?? null,
                            'certificate_id' => $data['certificate_id'] ?? null,
                            'state_name' => $data['state_name'] ?? null,
                            'license_number' => $data['license_number'] ?? null,
                            'applicable_work_category_id' => $data['applicable_work_category_id'] ?? null,
                            'certificate_number' => $data['certificate_number'] ?? null,
                            'issue_date' => isset($data['issue_date']) ? date('Y-m-d', strtotime($data['issue_date'])) : null,
                            'expired_date' => isset($data['expired_date']) ? date('Y-m-d', strtotime($data['expired_date'])) : null,
                        ]);

                        // Handle file cases
                        if (array_key_exists('file', $data)) {
                            if ($data['file'] instanceof \Illuminate\Http\UploadedFile) {
                                // New file uploaded
                                $extension = strtolower($data['file']->getClientOriginalExtension());
                                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                                    $licenseCertificate->file = $this->fileUpload->imageUploader(
                                        $data['file'],
                                        'license_and_certificate',
                                        200,
                                        200
                                    );
                                } elseif ($extension === 'pdf') {
                                    $licenseCertificate->file = $this->fileUpload->pdfUploader(
                                        $data['file'],
                                        'license_and_certificate'
                                    );
                                }
                            } elseif (is_null($data['file'])) {
                                // User wants to remove file
                                $licenseCertificate->file = null;
                            }
                            // If it's a string (old path) → do nothing (keep as is)
                        }

                        $licenseCertificate->save();
                        $existingIds[] = $licenseCertificate->id;
                    }
                } else {
                    // Create new
                    $licenseCertificate = new LicenseAndCertificate();
                    $licenseCertificate->fill(array_merge($conditions, [
                        'license_id' => $data['license_id'] ?? null,
                        'certificate_id' => $data['certificate_id'] ?? null,
                        'state_name' => $data['state_name'] ?? null,
                        'license_number' => $data['license_number'] ?? null,
                        'applicable_work_category_id' => $data['applicable_work_category_id'] ?? null,
                        'certificate_number' => $data['certificate_number'] ?? null,
                        'issue_date' => isset($data['issue_date']) ? date('Y-m-d', strtotime($data['issue_date'])) : null,
                        'expired_date' => isset($data['expired_date']) ? date('Y-m-d', strtotime($data['expired_date'])) : null,
                    ]));

                    if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $extension = strtolower($data['file']->getClientOriginalExtension());
                        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                            $licenseCertificate->file = $this->fileUpload->imageUploader(
                                $data['file'],
                                'license_and_certificate',
                                200,
                                200
                            );
                        } elseif ($extension === 'pdf') {
                            $licenseCertificate->file = $this->fileUpload->pdfUploader(
                                $data['file'],
                                'license_and_certificate'
                            );
                        }
                    }

                    $licenseCertificate->save();
                    $existingIds[] = $licenseCertificate->id;
                }
            }

            // Delete missing ones
            LicenseAndCertificate::where($conditions)
                ->whereNotIn('id', $existingIds)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'License and certificate successfully saved',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An internal error occurred.',
            ], 500);
        }
    }


    public function checkUsername(Request $request)
    {
        $rules = [
            'username' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        try {
            $user = User::query()
                ->where('username', $request->username)
                ->where('id', '!=', Auth::user()->id)
                ->first();

            if (!$user) {
                return response()->json([
                    'is_exists' => false,
                    'message' => 'User dose not have with this username',
                ]);
            } else {
                return response()->json([
                    'is_exists' => true,
                    'message' => 'Another user has with this username',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        }
    }

    public function clientDashboard()
    {
        $payment = Payment::where('client_id', Auth::user()->uuid)->where('status', 'Completed')->orderBy('created_at', 'desc')->first();
        $assigned = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Assigned')->count();
        $published_work_order_count = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Published')->count();
        $draft_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Draft')->count();
        $approved_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Approved')->count();
        $completed_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Complete')->count();
        $done_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Done')->count();

        $published_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->where('status', 'Published')->get();

        $work_order_ids = $published_work_order->pluck('work_order_unique_id');

        $work_order_request = SendWorkRequest::whereIn('work_order_unique_id', $work_order_ids)
            ->where('status', 'Active')
            ->count();
        $counter_offer = CounterOffer::whereIn('work_order_unique_id', $work_order_ids)
            ->where('status', 'Active')
            ->count();
        $total_work_order = WorkOrder::where('uuid', Auth::user()->uuid)->count();
        return response()->json([
            'status' => 'success',
            'available_funds' => $payment->point_balance ?? 0,
            'total_work_order' => $total_work_order ?? 0,
            'assigned' => $assigned ?? 0,
            'published_work_order' => $published_work_order_count ?? 0,
            'draft_work_order' => $draft_work_order ?? 0,
            'approved_work_order' => $approved_work_order ?? 0,
            'completed_work_order' => $completed_work_order ?? 0,
            'work_order_request' => $work_order_request ?? 0,
            'counter_offer' => $counter_offer ?? 0,
            'done_work_order' => $done_work_order ?? 0
        ]);
    }

    public function clinetHistoryLog($work_order_unique_id)
    {
        $historyLog = HistoryLog::where('work_order_unique_id', $work_order_unique_id)->get();

        return response()->json(
            [
                'status' => 'success',
                'history_log' => HistoryLogResource::collection($historyLog)
            ]
        );
    }

    public function graphData(Request $request)
    {
        $clientId = Auth::user()->uuid;

        // Fetch subscription transactions
        $subscriptionData = Payment::where('client_id', $clientId)
            ->where('transaction_type', 'Subscription')
            ->where('status', 'Completed')
            ->get(['credit', 'created_at']);

        // Fetch payment transactions
        $paymentData = Payment::where('client_id', $clientId)
            ->where('transaction_type', 'Payment')
            ->where('status', 'Completed')
            ->get(['services_fee', 'debit', 'created_at']);

        // Combine and group transactions by month
        $combinedData = collect();

        // Add subscription data
        foreach ($subscriptionData as $transaction) {
            $combinedData->push([
                'type' => 'Subscription',
                'amount' => $transaction->credit,
                'date' => $transaction->created_at->format('Y-m-d'),
                'month' => $transaction->created_at->format('Y-m'),
            ]);
        }

        // Add payment data
        foreach ($paymentData as $transaction) {
            $combinedData->push([
                'type' => 'Payment',
                'amount' => $transaction->service_fee + $transaction->debit,
                'date' => $transaction->created_at->format('Y-m-d'),
                'month' => $transaction->created_at->format('Y-m'),
            ]);
        }

        // Group by month and calculate total spend
        $monthlySpend = $combinedData->groupBy('month')->map(function ($transactions) {
            return [
                'total' => $transactions->sum('amount'),
                'details' => $transactions->groupBy('type')->map->sum('amount'),
            ];
        });

        $totalProviders = WorkOrder::where('uuid', Auth::user()->uuid)
            ->whereNotNull('assigned_id')
            ->distinct('assigned_id')
            ->count('assigned_id');

        $totalCompletedWorkOrders = WorkOrder::where('uuid', Auth::user()->uuid)
            ->where('status', 'Done')
            ->count();
        // Response
        return response()->json([
            'success' => true,
            'monthly_spend' => $monthlySpend,
            'total_providers' => $totalProviders,
            'total_completed_work_orders' => $totalCompletedWorkOrders,
        ]);
    }

    //test avalara

    // $url = "https://sandbox-rest.avatax.com/api/v2/companies";
    // $response = $client->get($url, [
    //     'headers' => [
    //         'Authorization' => $authHeader,
    //         'X-Avalara-Client' => 'TekHubX; 1.0; Custom; 1.0',
    //         'Content-Type' => 'application/json',
    //     ],
    // ]);

    // dd(json_decode($response->getBody()->getContents(), true));
    // public function testAvalara(Request $request)
    // {
    //     $client = new Client();
    //     $companyId = 8443163;
    //     $authHeader = 'Basic ' . base64_encode('dev@techhubps.com' . ':' . 'abC@1235%');

    //     try {
    //         $url = "https://sandbox-rest.avatax.com/api/v2/companies/{$companyId}/items";
    //         $response = $client->get($url, [
    //             'headers' => [
    //                 'Authorization' => $authHeader,
    //                 'X-Avalara-Client' => 'TekHubX; 1.0; Custom; 1.0',
    //                 'Content-Type' => 'application/json',
    //             ],
    //         ]);

    //         dd(json_decode($response->getBody()->getContents(), true));
    //     } catch (\Exception $e) {
    //         dd('Error:', $e->getMessage());
    //     }
    // }
}
