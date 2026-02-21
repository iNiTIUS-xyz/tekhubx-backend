<?php

namespace App\Http\Controllers\Provider\EmployeeProvider;

use App\Models\Role;
use App\Models\User;
use App\Models\About;
use App\Models\Profile;
use App\Models\SkillSet;
use App\Models\Education;
use App\Models\Equipment;
use App\Models\WorkSummery;
use Illuminate\Support\Str;
use App\Models\WorkCategory;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Utils\ServerErrorMask;
use App\Mail\PasswordSetupMail;
use App\Classes\FileUploadClass;
use App\Models\EmployeeProvider;
use App\Models\EmploymentHistory;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\LicenseAndCertificate;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PoolProfileResource;
use App\Http\Resources\UserCompleteDetailsResource;
use App\Http\Resources\Client\EmployeeProviderResource;
use App\Http\Resources\EmpResourceForLicenceAndCertificate;

class EmployeeProviderController extends Controller
{
    protected $fileUpload;
    protected $employeeProviderService;

    public function __construct(UserService $employeeProviderService, FileUploadClass $fileUpload)
    {
        $this->employeeProviderService = $employeeProviderService;
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $employeeProviders = EmployeeProvider::query()
                ->where('uuid', Auth::user()->uuid)
                ->orWhere('provider_id', Auth::user()->id)
                ->get();

            return response()->json([
                'status' => 'success',
                'employee_providers' => EmployeeProviderResource::collection($employeeProviders),
            ]);
        } catch (\Exception $e) {
            Log::error('Employee Provider query not found' . $e->getMessage());
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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:employee_providers',
            'phone' => 'required',
            'address_line_1' => 'required|string|min:3|max:255',
            'address_line_2' => 'nullable|string|min:3|max:255',
            'city' => 'required',
            'state_id' => 'required|exists:states,id',
            'zip_code' => 'required',
            'country_id' => 'required|exists:countries,id',
            'work_category_id' => 'required|exists:work_categories,id',
            'bio' => 'required|string|min:10|max:500',
            'status' => 'nullable|in:Active,Inactive',
            // 'role_id' => 'required|exists:roles,id',
        ];

        $messages = [
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'This email is already taken.',
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

            $token = Str::random(40);

            // $role = Role::find($request->role_id);
            $employeeProviders = new EmployeeProvider();

            $employeeProviders->user_id = Auth::user()->id;
            $employeeProviders->uuid = Auth::user()->uuid;
            $employeeProviders->first_name = $request->first_name;
            $employeeProviders->last_name = $request->last_name;
            $employeeProviders->email = $request->email;
            $employeeProviders->phone = $request->phone;
            $employeeProviders->address_line_1 = $request->address_line_1;
            $employeeProviders->address_line_2 = $request->address_line_2;
            $employeeProviders->city = $request->city;
            $employeeProviders->state_id = $request->state_id;
            $employeeProviders->zip_code = $request->zip_code;
            $employeeProviders->country_id = $request->country_id;
            $employeeProviders->work_category_id = $request->work_category_id;
            $employeeProviders->bio = $request->bio;
            $employeeProviders->status = $request->status;
            $employeeProviders->token = $token;
            // $employeeProviders->role = $role->name;
            $employeeProviders->role = 'Manager';

            $employeeProviders->save();

            $uuid = Auth::user()->uuid;
            // user provider create
            $user = $this->employeeProviderService->createProviderUser($request, $uuid);

            $this->employeeProviderService->createProviderUserProfile($request, $user);

            $employeeProviders->provider_id = $user->id;

            $employeeProviders->save();

            DB::commit();

            // password set for user mail
            Mail::to($user->email)->send(new PasswordSetupMail($employeeProviders, $token));

            return response()->json([
                'status' => 'success',
                'message' => 'Employee Provider Successfully Save',
                'employee_providers' => new EmployeeProviderResource($employeeProviders),
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

    public function edit($id)
    {
        try {
            $employeeProviders = EmployeeProvider::query()
                ->with([
                    'profile',
                    'providerUser',
                    'about',
                    'workSummery',
                    'skillSet',
                    'equipment',
                    'employmentHistory',
                    'education',
                    'licenseCertificate.certificate',
                    'licenseCertificate.license',
                ])
                ->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'employee_providers' => new EmployeeProviderResource($employeeProviders),
            ]);
        } catch (\Exception $e) {
            Log::error('Employee Provider query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
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

            $employee = EmployeeProvider::query()
                ->findOrFail($id);
            // if ($request->type == 'basic') {
            if ($request->role_id) {
                $role = Role::find($request->role_id);
            }
            $employee->first_name = $request->first_name ?? $employee->first_name;
            $employee->last_name = $request->last_name ?? $employee->last_name;
            $employee->email = $request->email ?? $employee->email;
            $employee->phone = $request->phone ?? $employee->phone;
            $employee->address_line_1 = $request->address_line_1 ?? $employee->address_line_1;
            $employee->address_line_2 = $request->address_line_2 ?? $employee->address_line_2;
            $employee->city = $request->city ?? $employee->city;
            $employee->state_id = $request->state_id ?? $employee->state_id;
            $employee->zip_code = $request->zip_code ?? $employee->zip_code;
            $employee->country_id = $request->country_id ?? $employee->country_id;
            $employee->work_category_id = $request->work_category_id ?? $employee->work_category_id;
            $employee->bio = $request->bio ?? $employee->bio;
            $employee->status = $request->status ?? $employee->status;
            $employee->role = $role->name ?? $employee->role;
            $employee->save();

            if (!empty($request->role_id)) {
                $user = User::find($employee->provider_id);
                $user->update([
                    'role_id' => $request->role_id,
                    'role' => $role->name
                ]);
            }
            // }
            // Handle the different request types
            if ($request->type == 'about') {

                $this->about($request, $employee);
            }

            if ($request->type == 'work_summery') {

                $this->workSummery($request, $employee);
            }

            if ($request->type == 'skill_set') {
                $this->skillSet($request, $employee);
            }

            if ($request->type == 'equipments') {
                $this->equipments($request, $employee);
            }

            if ($request->type == 'employment_history') {
                $this->employmentHistory($request->input('employment_history'), $employee);
            }

            if ($request->type == 'education') {
                $this->education($request->input('education'), $employee);
            }

            // if ($request->type == 'license_certificate') {
            //     $this->licenseAndCertificate($request, $employee);
            // }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee provider details successfully updated',
            ], 200);
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

    private function getValidationRules($type)
    {
        switch ($type) {
            case 'about':
                return [
                    'tagline' => 'required|string|max:255',
                    'biography' => 'nullable|string',
                ];
            case 'basic':
                return [
                    'first_name' => 'nullable',
                    'last_name' => 'nullable',
                    'email' => 'nullable|email|unique:employee_providers',
                    'phone' => 'nullable',
                    'address_line_1' => 'nullable',
                    'address_line_2' => 'nullable',
                    'city' => 'nullable',
                    'state_id' => 'nullable|exists:states,id',
                    'zip_code' => 'nullable',
                    'country_id' => 'nullable|exists:countries,id',
                    'work_category_id' => 'nullable|exists:work_categories,id',
                    'bio' => 'nullable',
                    'status' => 'nullable|in:Active,Inactive',
                    'role_id' => 'nullable|exists:roles,id',
                ];
            case 'work_summery':
                return [
                    'work_summery_category_ids' => 'required|array',
                    'work_summery_category_ids.*' => 'exists:work_categories,id',
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
                    'employment_history.*.company_name' => 'required|string|max:255',
                    'employment_history.*.position' => 'required|string|max:255',
                    'employment_history.*.start_date' => 'required|date|before_or_equal:today',
                    'employment_history.*.end_date' => 'nullable|date|after_or_equal:employment_history.*.start_date',
                    'employment_history.*.location' => 'required|string|max:255',
                    'employment_history.*.description' => 'nullable|string|max:1500',
                ];

            case 'education':
                return [
                    'education' => 'required|array',
                    'education.*.school_name' => 'required|string|max:255',
                    'education.*.degree' => 'required|string|max:255',
                    'education.*.field_of_study' => 'nullable|string|max:255',
                    'education.*.start_date' => 'required|date|before:education.*.end_date',
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

    // about function
    public function about($request, $employee)
    {
        $conditions = [
            'employee_provider_id' => $employee->id,
            'user_id' => $employee->provider_id,
            'uuid' => $employee->providerUser?->uuid,
        ];

        $about = About::firstOrCreate($conditions);

        $about->tagline = $request->tagline;
        $about->biography = $request->biography;
        $about->save();

        return true;
    }

    // workSummery function
    public function workSummery($request, $employee)
    {
        foreach ($request->work_summery_category_ids as $key => $value) {
            $workCategory = WorkCategory::find($value);

            $conditions = [
                'employee_provider_id' => $employee->id,
                'user_id' => $employee->provider_id,
                'uuid' => $employee->providerUser?->uuid,
                'work_category_id' => $value,
            ];

            $workSummery = WorkSummery::firstOrCreate($conditions);
            $workSummery->work_category_id = $value;
            $workSummery->work_category_name = $workCategory->name;
            $workSummery->save();
        }

        return true;
    }

    // skillSet function
    public function skillSet($request, $employee)
    {
        foreach ($request->skill_set_names as $key => $value) {

            $conditions = [
                'employee_provider_id' => $employee->id,
                'user_id' => $employee->provider_id,
                'uuid' => $employee->providerUser?->uuid,
                'name' => trim($value),
            ];

            $skillSet = SkillSet::firstOrCreate($conditions);
            $skillSet->name = trim($value);
            $skillSet->save();
        }

        return true;
    }

    // equipments function
    public function equipments($request, $employee)
    {
        foreach ($request->equipment_names as $key => $value) {
            $conditions = [
                'employee_provider_id' => $employee->id,
                'user_id' => $employee->provider_id,
                'uuid' => $employee->providerUser?->uuid,
                'name' => trim($value),
            ];

            $equipments =  Equipment::firstOrCreate($conditions);
            $equipments->name = trim($value);
            $equipments->save();
        }

        return true;
    }

    // employmentHistory function
    public function employmentHistory($employmentHistoryArray, $employee)
    {
        foreach ($employmentHistoryArray as $employment) {

            $conditions = [
                'employee_provider_id' => $employee->id,
                'user_id' => $employee->provider_id,
                'uuid' => $employee->providerUser?->uuid,
                'company_name' => $employment['company_name'],
                'position' => $employment['position'],
            ];

            $employmentHistory =  EmploymentHistory::firstOrCreate($conditions);
            $employmentHistory->start_date = $employment['start_date'];
            $employmentHistory->end_date = $employment['end_date'];
            $employmentHistory->location = $employment['location'];
            $employmentHistory->description = $employment['description'];
            $employmentHistory->save();
        }

        return true;
    }

    // education function
    public function education($educationArray, $employee)
    {
        foreach ($educationArray as $educationData) {

            $conditions = [
                'employee_provider_id' => $employee->id,
                'user_id' => $employee->provider_id,
                'uuid' => $employee->providerUser?->uuid,
                'school_name' => $educationData['school_name'],
                'degree' => $educationData['degree'],
                'field_of_study' => $educationData['field_of_study'],
            ];

            $education =  Education::firstOrCreate($conditions);

            $education->start_date = $educationData['start_date'];
            $education->end_date = $educationData['end_date'];
            $education->location = $educationData['location'];
            $education->activities = $educationData['activities'];
            $education->save();
        }

        return true;
    }

    public function licenseAndCertificate(Request $request, $id)
    {
        $rules = [
            'license_certificate' => 'required|array',
            'license_certificate.license_id' => 'nullable|integer',
            'license_certificate.certificate_id' => 'nullable|integer',
            'license_certificate.state_name' => 'nullable|string|max:255',
            'license_certificate.license_number' => 'nullable|string|max:255',
            'license_certificate.applicable_work_category_id' => 'nullable|integer',
            'license_certificate.certificate_number' => 'nullable|string|max:255',
            'license_certificate.issue_date' => 'nullable|date_format:Y-m-d',
            'license_certificate.expired_date' => 'nullable|date_format:Y-m-d',
            'license_certificate.file' => 'nullable|mimes:png,jpg,jpeg,pdf|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray()),
            ], 422);
        }

        try {
            $employee = EmployeeProvider::findOrFail($id);

            $conditions = [
                'employee_provider_id' => $employee->id,
                'provider_id' => $employee->provider_id,
            ];

            $licenseCertificate = LicenseAndCertificate::firstOrCreate($conditions);

            // Ensure `$request->license_certificate` is set before accessing
            $licenseData = $request->license_certificate ?? [];

            $licenseCertificate->license_id = optional($licenseData)['license_id'];
            $licenseCertificate->certificate_id = optional($licenseData)['certificate_id'];
            $licenseCertificate->state_name = optional($licenseData)['state_name'];
            $licenseCertificate->license_number = optional($licenseData)['license_number'];
            $licenseCertificate->applicable_work_category_id = optional($licenseData)['applicable_work_category_id'];
            $licenseCertificate->certificate_number = optional($licenseData)['certificate_number'];
            $licenseCertificate->issue_date = optional($licenseData)['issue_date'];
            $licenseCertificate->expired_date = optional($licenseData)['expired_date'];

            // Handle file upload if present
            if (isset($licenseData['file'])) {
                // $this->fileUpload->fileUnlink($licenseCertificate->file);
                // $image_url = $this->fileUpload->pdfUploader($licenseData['file'], 'license_and_certificate');
                // $licenseCertificate->file = $image_url;

                $extension = $licenseData['file']->getClientOriginalExtension();
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $licenseCertificate->file = $this->fileUpload->imageUploader($licenseData['file'], 'license_and_certificate');
                } elseif ($extension === 'pdf') {
                    $licenseCertificate->file = $this->fileUpload->pdfUploader($licenseData['file'], 'license_and_certificate');
                } else {
                    throw new \Exception('Unsupported file type uploaded.');
                }
            }

            $licenseCertificate->save();

            return response()->json([
                'status' => 'success',
                'message' => 'License and certificate successfully saved.',
                'data' => $licenseCertificate,
            ], 200);
        } catch (\Exception $e) {
            Log::error('License and Certificate Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while saving the license and certificate.',
            ], 500);
        }
    }


    public function licenseAndCertificateListDetails()
    {
        try {
            $employeeProviders = EmployeeProvider::where('uuid', Auth::user()->uuid)
                ->orWhere('provider_id', Auth::user()->id)
                ->with('licenseCertificate')
                ->get();

            return response()->json([
                'status' => 'success',
                'employee_providers' => EmpResourceForLicenceAndCertificate::collection($employeeProviders),
            ]);
        } catch (\Exception $e) {
            Log::error('Employee Provider query not found' . $e->getMessage());
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

            $employeeProviders = EmployeeProvider::query()
                ->findOrFail($id);

            // user delete
            // $user = User::query()
            //             ->where('id', $employeeProviders->provider_id)
            //             ->with('profiles')
            //             ->first();
            // user profile delete
            // $user->profiles?->delete();
            // user delete
            // $user->delete();
            // employee provider delete
            // $employeeProviders->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee Provider Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Providers query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function providersList()
    {
        try {
            $users = User::with('profiles', 'companies')->whereIn('organization_role', ['Provider', 'Provider Company'])->get();

            // $userDetails = $users->map(function ($user) {
            //     return [
            //         'id' => $user->id,
            //         'organization_role' => $user->organization_role,
            //         'username' => $user->username,
            //         'email' => $user->email,
            //         'role' => $user->role,
            //         'status' => $user->status,
            //         'profile' => $user->profiles->isNotEmpty() ? new ProfileResource($user->profiles->first()) : null,
            //         'company' => $user->companies->isNotEmpty() ? new CompanyResource($user->companies->first()) : null,
            //     ];
            // });

            return response()->json([
                'success' => true,
                'users' => PoolProfileResource::collection($users),
            ]);
        } catch (\Throwable $e) {
            Log::error('Counter offer query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function providersDetails($id)
    {
        try {
            $users = User::query()
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
                ->whereIn('organization_role', ['Provider', 'Provider Company'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'users' => new UserCompleteDetailsResource($users),
            ]);
        } catch (\Throwable $e) {
            Log::error('Counter offer query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function profileImage(Request $request)
    {
        $rules = [
            'profile_id' => 'required',
            'profile_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }
        try {
            $profile = Profile::find($request->profile_id);
            if ($request->hasFile("profile_image")) {
                $this->fileUpload->fileUnlink($profile->profile_image);
                $image_url = $this->fileUpload->imageUploader($request->file('profile_image'), 'profile');
                $profile->profile_image = $image_url;
            }
            $profile->save();

            return response()->json([
                'success' => true,
                'profile' => $profile,
            ]);
        } catch (\Throwable $e) {
            Log::error('Counter offer query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
