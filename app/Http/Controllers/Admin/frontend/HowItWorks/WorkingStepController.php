<?php

namespace App\Http\Controllers\Admin\frontend\HowItWorks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Models\HowItWorksStep;
use App\Utils\ServerErrorMask;
use App\Models\FAQ;
use Illuminate\Support\Facades\DB;

class WorkingStepController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    public function index()
    {
        try {
            $steps = HowItWorksStep::query()->get();

            $faqs = FAQ::query()
                ->whereIn('category', ['Provider', 'Client'])
                ->get();

            return response()->json([
                'status' => 'success',
                'steps' => $steps,
                'faqs' => $faqs,
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
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
            'type' => 'required|in:client,provider',
            'step_count' => 'required|integer|min:1',
            'step_title' => 'required|string|max:255',
            'step_description' => 'nullable|string',
            'step_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
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

            // Check if a step with the same count already exists for the type
            $stepExists = HowItWorksStep::query()
                ->where('type', $request->type)
                ->where('step_count', $request->step_count)
                ->exists();

            if ($stepExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A step with this count already exists for the given type.',
                ], 422);
            }

            $step = new HowItWorksStep();

            $step->type = $request->type;
            $step->step_count = $request->step_count;
            $step->step_title = $request->step_title;
            $step->step_description = $request->step_description;

            if ($request->hasFile('step_image')) {
                $imageUrl = $this->fileUpload->imageUploader($request->file('step_image'), 'how_it_works_steps');
                $step->step_image = $imageUrl;
            }

            $step->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Step successfully created.',
                'data' => $step,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store failed: ' . $e->getMessage());

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
            $step = HowItWorksStep::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'step' => $step,
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
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
            'type' => 'required|in:client,provider',
            'step_count' => 'required|integer|min:1',
            'step_title' => 'required|string|max:255',
            'step_description' => 'nullable|string',
            'step_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
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

            // Fetch the step
            $step = HowItWorksStep::query()->findOrFail($id);

            // Check for duplicate step count within the same type
            $duplicateStep = HowItWorksStep::query()
                ->where('type', $request->type)
                ->where('step_count', $request->step_count)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicateStep) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A step with this count already exists for the given type.',
                ], 422);
            }

            // Update the step
            $step->type = $request->type;
            $step->step_count = $request->step_count;
            $step->step_title = $request->step_title;
            $step->step_description = $request->step_description;

            // Handle image upload
            if ($request->hasFile('step_image')) {
                // Delete old image if exists
                if ($step->step_image) {
                    $this->fileUpload->fileUnlink($step->step_image);
                }

                // Upload new image
                $imageUrl = $this->fileUpload->imageUploader($request->file('step_image'), 'how_it_works_steps');
                $step->step_image = $imageUrl;
            }

            $step->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Step successfully updated.',
                'data' => $step->refresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update failed: ' . $e->getMessage());

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

            // Find the step by ID
            $step = HowItWorksStep::query()->findOrFail($id);

            // Delete associated image if exists
            if ($step->step_image) {
                $this->fileUpload->fileUnlink($step->step_image);
            }

            // Delete the step
            $step->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Step successfully deleted.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
