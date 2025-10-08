<?php

namespace App\Http\Controllers\Provider\EmployeeProvider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Client\LicenseAndCertificateResource;
use App\Models\LicenseAndCertificate;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class LicenseAndCertificateController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        $this->middleware('permission:employee_provider_license_certificates,employee_provider_license_certificates.list')->only(['index']);
        $this->middleware('permission:employee_provider_license_certificates.create_store')->only(['store']);
        $this->middleware('permission:employee_provider_license_certificates.edit')->only(['edit']);
        $this->middleware('permission:employee_provider_license_certificates.update')->only(['update']);
        $this->middleware('permission:employee_provider_license_certificates.delete')->only(['destroy']);

    }
    public function index(Request $request)
    {
        try {
            if (!$request->provider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Provider Id Must be Required',
                ]);
            }

            $licenseCertificate = LicenseAndCertificate::query()
                ->when($request->provider, fn($q) => $q->where('provider_id', $request->provider))
                ->with([
                    'provider',
                    'employeeProvider'
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'license_certificate' => LicenseAndCertificateResource::collection($licenseCertificate),
            ]);
        } catch (\Exception $e) {
            Log::error('License and certificate query not found' . $e->getMessage());
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
            'provider_id' => 'required|exists:users,id',
            'employee_provider_id' => 'required|exists:employee_providers,id',
            'license_id' => 'required',
            'certificate_id' => 'required',
            'state_name' => 'required',
            'license_number' => 'required',
            'applicable_work_category_id' => 'required',
            'certificate_number' => 'required',
            'issue_date' => 'required',
            'expired_date' => 'required',
            'file' => 'required|mimes:png,jpg,jpeg,pdf|max:10240',
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
            $licenseCertificate = new LicenseAndCertificate();
            $licenseCertificate->provider_id = $request->provider_id;
            $licenseCertificate->employee_provider_id = $request->employee_provider_id;
            $licenseCertificate->license_id = $request->license_id;
            $licenseCertificate->certificate_id = $request->certificate_id;
            $licenseCertificate->state_name = $request->state_name;
            $licenseCertificate->license_number = $request->license_number;
            $licenseCertificate->applicable_work_category_id = $request->applicable_work_category_id;
            $licenseCertificate->certificate_number = $request->certificate_number;
            $licenseCertificate->issue_date = date('Y-m-d', strtotime($request->issue_date));
            $licenseCertificate->expired_date = date('Y-m-d', strtotime($request->expired_date));

            if ($request->hasFile("file")) {
                $image_url = $this->fileUpload->pdfUploader($request->file('file'), 'license_and_certificate');
                $licenseCertificate->file = $image_url;
            }

            $licenseCertificate->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'License and certificate Successfully Save',
                'license_certificate' => new LicenseAndCertificateResource($licenseCertificate),
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
            $licenseCertificate = LicenseAndCertificate::query()
                ->findOrFail($id);
            return new LicenseAndCertificateResource($licenseCertificate);
        } catch (\Exception $e) {
            Log::error('License and certificate query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'provider_id' => 'required|exists:users,id',
            'employee_provider_id' => 'required|exists:employee_providers,id',
            'license_id' => 'required',
            'certificate_id' => 'required',
            'state_name' => 'required',
            'license_number' => 'required',
            'applicable_work_category_id' => 'required',
            'certificate_number' => 'required',
            'issue_date' => 'required',
            'expired_date' => 'required',
            'file' => 'required|mimes:png,jpg,jpeg,pdf|max:10240',
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
            $licenseCertificate = LicenseAndCertificate::query()
                ->findOrFail($id);
            $licenseCertificate = new LicenseAndCertificate();
            $licenseCertificate->provider_id = $request->provider_id;
            $licenseCertificate->employee_provider_id = $request->employee_provider_id;
            $licenseCertificate->license_id = $request->license_id;
            $licenseCertificate->certificate_id = $request->certificate_id;
            $licenseCertificate->state_name = $request->state_name;
            $licenseCertificate->license_number = $request->license_number;
            $licenseCertificate->applicable_work_category_id = $request->applicable_work_category_id;
            $licenseCertificate->certificate_number = $request->certificate_number;
            $licenseCertificate->issue_date = date('Y-m-d', strtotime($request->issue_date));
            $licenseCertificate->expired_date = date('Y-m-d', strtotime($request->expired_date));

            if ($request->hasFile("file")) {
                $this->fileUpload->fileUnlink($licenseCertificate->file);
                $image_url = $this->fileUpload->pdfUploader($request->file('file'), 'license_and_certificate');
                $licenseCertificate->file = $image_url;
            }

            $licenseCertificate->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'License and certificate Successfully Update',
                'license_certificate' => new LicenseAndCertificateResource($licenseCertificate->refresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update filed' . $e->getMessage());
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

            $licenseCertificate = LicenseAndCertificate::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($licenseCertificate->file);

            $licenseCertificate->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'License and certificate Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('License and certificate query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
