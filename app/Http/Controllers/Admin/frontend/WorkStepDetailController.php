<?php

namespace App\Http\Controllers\Admin\frontend;

use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PartnerContactResource;
use App\Http\Resources\Admin\ContactUsResource;
use App\Http\Resources\Admin\WorkStepDetailsResource;
use App\Models\PartnerContact;
use App\Models\ContactUs;
use App\Models\WorkStepDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Utils\ServerErrorMask;

class WorkStepDetailController extends Controller
{
    //
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    public function workStepList()
    {

        try {

            $workStepDetail = WorkStepDetail::query()
                    ->get();

            return response()->json([
                'status' => 'success',
                'work_step' =>  WorkStepDetailsResource::collection($workStepDetail),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Work step update fail' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function workStepStore(Request $request)
    {
        $rules = [
            'title' => 'required',
            'step_image' => 'nullable',
            'step_list' => 'nullable|array',
            'status' => 'nullable|in:Active,Inactive',
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

            $workStepDetail = new WorkStepDetail();

            $workStepDetail->title = $request->title;
            $workStepDetail->step_list = $request->step_list ? json_encode($request->step_list) : null;

            if ($request->hasFile("step_image")) {

                $this->fileUpload->fileUnlink($workStepDetail->step_image);

                $image_url_one = $this->fileUpload->imageUploader($request->file('step_image'), 'work_step_image', 640, 640);
                $workStepDetail->step_image = $image_url_one;
            }

            $workStepDetail->status = $request->status;

            $workStepDetail->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Step Successfully Saved',
                'work_step' => new WorkStepDetailsResource($workStepDetail),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Work step Store fail' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function workStepEdit($id)
    {
        try {

            $workStepDetail = WorkStepDetail::query()
                    ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'work_step' => new WorkStepDetailsResource($workStepDetail),
            ]);

        } catch (\Exception $e) {
            Log::error('Work step not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function workStepUpdate(Request $request, $id)
    {
        $rules = [
            'title' => 'required',
            'step_image' => 'nullable',
            'step_list' => 'nullable|array',
            'status' => 'nullable|in:Active,Inactive',
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

            $workStepDetail = WorkStepDetail::query()
                        ->findOrFail($id);

            $workStepDetail->title = $request->title;
            $workStepDetail->step_list = $request->step_list ? json_encode($request->step_list) : null;

            if ($request->hasFile("step_image")) {

                $this->fileUpload->fileUnlink($workStepDetail->step_image);

                $image_url_one = $this->fileUpload->imageUploader($request->file('step_image'), 'work_step_image', 640, 640);
                $workStepDetail->step_image = $image_url_one;
            }

            $workStepDetail->status = $request->status;
            $workStepDetail->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Step Successfully Updated',
                'work_step' => new WorkStepDetailsResource($workStepDetail->refresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Work step Updated fail' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function workStepDelete($id)
    {
        try {

            DB::beginTransaction();
            $workStepDetail = WorkStepDetail::query()
                    ->findOrFail($id);

            $this->fileUpload->fileUnlink($workStepDetail->step_image);

            $workStepDetail->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Work Step Successfully Deleted',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Work step Store fail' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function partnerContactList()
    {

        try {

            $partnerContact = PartnerContact::query()
                    ->get();

            return response()->json([
                'status' => 'success',
                'partner_contact' => PartnerContactResource::collection($partnerContact),
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function partnerContactView($id)
    {

        try {

            $partnerContact = PartnerContact::query()
                    ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'partner_contact' => new PartnerContactResource($partnerContact),
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function partnerContactDelete($id)
    {

        try {

            $partnerContact = PartnerContact::query()
                    ->findOrFail($id);

            $partnerContact->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Partner Contact Successfully Delete',
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function contactUsList()
    {

        try {

            $contactUs = ContactUs::query()
                    ->get();

            return response()->json([
                'status' => 'success',
                'contact_us' => ContactUsResource::collection($contactUs),
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function contactUsView($id)
    {

        try {

            $contactUs = ContactUs::query()
                    ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'contact_us' => new ContactUsResource($contactUs),
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function contactUsDelete($id)
    {

        try {

            $contactUs = ContactUs::query()
                    ->findOrFail($id);

            $contactUs->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Partner Contact Successfully Delete',
            ]);

        } catch (\Exception $e) {
            Log::error('Partner contact not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
