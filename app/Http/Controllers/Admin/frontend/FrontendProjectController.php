<?php

namespace App\Http\Controllers\Admin\frontend;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\FrontendProject;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OurProjectResource;

class FrontendProjectController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $projects = FrontendProject::query()->get();

            return response()->json([
                'status' => 'success',
                'projects' => OurProjectResource::collection($projects),
            ]);
        } catch (\Exception $e) {
            Log::error('projects query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:10240',
            'description' => 'required',
            'tags' => 'required|array|min:1',
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
            $projects = new FrontendProject();
            $projects->title = $request->title;
            $projects->slug = strtolower(str_replace(' ', '-', $request->title));
            $projects->description = $request->description;
            $projects->admin_id = Auth::user()->id;
            $projects->tags = json_encode($request->tags);

            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'projects');
                $projects->image = $image_url;
            }

            $projects->status = $request->status;
            $projects->total_view = 0;

            $projects->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project created successfully',
                'projects' => $projects,
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
            $projects = FrontendProject::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'projects' => new OurProjectResource($projects),
            ]);
        } catch (\Exception $e) {
            Log::error('projects query not found' . $e->getMessage());
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
            'title' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'description' => 'required',
            'tags' => 'required|array|min:1',
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
            $projects = FrontendProject::query()
                ->findOrFail($id);
            $projects->title = $request->title;
            $projects->slug = strtolower(str_replace(' ', '-', $request->title));
            $projects->description = $request->description;
            $projects->admin_id = Auth::user()->id;
            $projects->tags = json_encode($request->tags);

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($projects->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'projects');
                $projects->image = $image_url;
            }

            $projects->status = $request->status;
            $projects->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'projects' => $projects,
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

            $projects = FrontendProject::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($projects->image);

            $projects->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('projects query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
