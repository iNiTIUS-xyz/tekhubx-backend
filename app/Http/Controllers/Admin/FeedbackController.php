<?php

namespace App\Http\Controllers\Admin;

use App\Models\Feedback;
use Illuminate\Http\Request;
use App\Models\FeedbackReason;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\FeedbackResource;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function index()
    {
        try {

            $feedback = Feedback::all()->map(function ($item) {
                $name = null;
                $email = null;

                if ($item->client_id) {
                    $name = optional($item->client->profile)->first_name . ' ' . optional($item->client->profile)->last_name;
                    $email = optional($item->client)->email;
                } elseif ($item->provider_id) {
                    $name = optional($item->provider->profile)->first_name . ' ' . optional($item->provider->profile)->last_name;
                    $email = optional($item->provider)->email;
                }

                return [
                    'id' => $item->id,
                    'name' => $name,
                    'email' => $email,
                    'reason' => $item->reason,
                    'comments' => $item->comments,
                    'type' => $item->client_id ? 'client' : 'provider',
                ];
            });
            return response()->json([
                'status' => 'success',
                'feedback' => $feedback,
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
        $rules = [

            'reason' => 'required|string',
            'comments' => 'required|string|min:20',
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
            if (Auth::user()->organization_role == 'Client') {
                $user_id = Auth::user()->id;
                $feedback = new Feedback();
                $feedback->client_id = $user_id ?? null;
                $feedback->provider_id = null;
                $feedback->reason = $request->reason;
                $feedback->comments = $request->comments;
                $feedback->save();
            }
            if (Auth::user()->organization_role == 'Provider' || Auth::user()->organization_role == 'Provider Company') {
                $user_id = Auth::user()->id;
                $feedback = new Feedback();
                $feedback->client_id = null;
                $feedback->provider_id = $user_id ?? null;
                $feedback->reason = $request->reason;
                $feedback->comments = $request->comments;
                $feedback->save();
            }


            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'feedback' => $feedback,
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

    public function deleteFeedback($id)
    {
        try {
            DB::beginTransaction();

            $feedback = Feedback::findOrFail($id);
            $feedback->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting feedback: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reason_index()
    {
        $reasons = FeedbackReason::all();
        return response()->json([
            'status' => 'success',
            'reasons' => $reasons,
        ]);
    }

    public function reason_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:feedback_reasons,title',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reason = FeedbackReason::create([
                'title' => $request->title,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reason added successfully.',
                'data' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while adding reason.',
            ], 500);
        }
    }

    // Edit (fetch one)
    public function reason_edit($id)
    {
        $reason = FeedbackReason::find($id);
        if (!$reason) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reason not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $reason,
        ]);
    }

    // Update
    public function reason_update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:feedback_reasons,title,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reason = FeedbackReason::findOrFail($id);
            $reason->title = $request->title;
            $reason->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Reason updated successfully.',
                'data' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Server error while updating reason.',
            ], 500);
        }
    }

    // Delete
    public function reason_destroy($id)
    {
        try {
            $reason = FeedbackReason::findOrFail($id);
            $reason->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Reason deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting reason.',
            ], 500);
        }
    }
}
