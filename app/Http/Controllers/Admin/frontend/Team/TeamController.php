<?php

namespace App\Http\Controllers\Admin\frontend\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\TeamResource;
use App\Models\Team;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $teams = Team::query()->get();

            return response()->json([
                'status' => 'success',
                'teams' => TeamResource::collection($teams),
            ]);
        } catch (\Exception $e) {
            Log::error('Team query not found' . $e->getMessage());
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
            'name' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:5120',
            'designation' => 'required',
            'portfolio_url' => 'required',
            'linkedin_url' => 'required',
            'gender' => 'required|in:Male,Female,Other',
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
            $teams = new Team();

            $teams->name = $request->name;

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($teams->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'team');
                $teams->image = $image_url;
            }
            $teams->designation = $request->designation;
            $teams->gender = $request->gender;
            $teams->portfolio_url = $request->portfolio_url;
            $teams->linkedin_url = $request->linkedin_url;
            $teams->facebook_url = $request->facebook_url;
            $teams->x_url = $request->x_url;
            $teams->status = $request->status;

            $teams->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Team Successfully Save',
                'team' => new TeamResource($teams),
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
            $teams = Team::query()
                ->findOrFail($id);


            return response()->json([
                'status' => 'success',
                'teams' => new TeamResource($teams),
            ]);
        } catch (\Exception $e) {
            Log::error('Team query not found' . $e->getMessage());
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
            'name' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'designation' => 'required',
            'portfolio_url' => 'required',
            'linkedin_url' => 'required',
            'gender' => 'required|in:Male,Female,Other',
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
            $teams = Team::query()
                ->findOrFail($id);
            $teams->name = $request->name;

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($teams->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'team');
                $teams->image = $image_url;
            }
            $teams->designation = $request->designation;
            $teams->gender = $request->gender;
            $teams->portfolio_url = $request->portfolio_url;
            $teams->linkedin_url = $request->linkedin_url;
            $teams->facebook_url = $request->facebook_url;
            $teams->x_url = $request->x_url;
            $teams->status = $request->status;

            $teams->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Team Successfully Update',
                'team' => new TeamResource($teams->refresh()),
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

            $teams = Team::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($teams->image);

            $teams->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Team Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Team query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

}
