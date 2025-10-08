<?php

namespace App\Http\Controllers\Client\template;

use App\Models\Template;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\AdditionalContact;
use App\Models\QualificationType;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\QualificationSubCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Client\TemplateResource;

class TemplateController extends Controller
{
    public function index()
    {
        try {
            $template = Template::with('additional_contact')->where('uuid', Auth::user()->uuid)->get();

            // Use the resource to transform the collection
            return response()->json([
                'status' => 'success',
                'template' => TemplateResource::collection($template),
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
            'template_name' => 'required|string|max:255',
            'default_client_id' => 'nullable',
            'project_id' => 'nullable',
            'work_order_title' => 'nullable|string|max:255',
            'export_button' => 'nullable|boolean',
            'counter_offer' => 'nullable|boolean',
            'gps_on' => 'nullable|boolean',
            'public_description' => 'nullable',
            'private_description' => 'nullable',
            'work_category_id' => 'nullable',
            'additional_work_category_id' => 'nullable',
            'service_type_id' => 'nullable',
            'qualification_type' => 'nullable|array',
            'work_order_manager_id' => 'nullable',
            'additional_contact_info' => 'nullable|array',
            'tasks' => 'nullable|array',
            'buyer_custom_field' => 'nullable',
            'pay_type' => 'nullable|in:Hourly,Fixed,Per Device,Blended',
            'hourly_rate' => 'nullable|required_if:pay_type,Hourly',
            'max_hours' => 'nullable|required_if:pay_type,Hourly',
            'total_pay' => 'nullable|required_if:pay_type,Fixed',
            'per_device_rate' => 'nullable|required_if:pay_type,Per Device',
            'max_device' => 'nullable|required_if:pay_type,Per Device',
            'fixed_payment' => 'nullable|required_if:pay_type,Blended',
            'fixed_hours' => 'nullable|required_if:pay_type,Blended',
            'additional_hourly_rate' => 'nullable|required_if:pay_type,Blended',
            'max_additional_hour' => 'nullable|required_if:,Blended',
            'bank_account_id' => 'nullable',
            'approximate_hour_complete' => 'nullable',
            'rule_id' => 'nullable',
            'additional_contact_info.*.additional_contact_title' => 'nullable|string|max:255',
            'additional_contact_info.*.additional_contact_name' => 'nullable|string|max:255',
            'additional_contact_info.*.additional_contact_phone' => 'nullable|string|max:20',
            'additional_contact_info.*.additional_contact_ext' => 'nullable|string|max:10',
            'additional_contact_info.*.additional_contact_email' => 'nullable|email|max:255',
            'additional_contact_info.*.additional_contact_note' => 'nullable|string|max:500',

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
            $additionalContactIds = [];

            if ($request->has('additional_contact_info') && is_array($request->additional_contact_info)) {
                foreach ($request->additional_contact_info as $contactName) {
                    $contactName = (object) $contactName;
                    $additional_contact = new AdditionalContact();
                    $additional_contact->user_id = Auth::user()->id;
                    $additional_contact->name = $contactName->additional_contact_title ?? null;
                    $additional_contact->title = $contactName->additional_contact_name ?? null;
                    $additional_contact->phone = $contactName->additional_contact_phone ?? null;
                    $additional_contact->ext = $contactName->additional_contact_ext ?? null;
                    $additional_contact->email = $contactName->additional_contact_email ?? null;
                    $additional_contact->note = $contactName->additional_contact_note ?? null;
                    $additional_contact->save();

                    $additionalContactIds[] = $additional_contact->id;
                }
            }

            $tasks = $request->tasks;

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

            $template = new Template();
            $template->uuid = Auth::user()->uuid;
            $template->template_name = $request->template_name;
            $template->default_client_id = $request->default_client_id;
            $template->project_id = $request->project_id;
            $template->work_order_title = $request->work_order_title;
            $template->export_button = $request->export_button;
            $template->counter_offer = $request->counter_offer;
            $template->gps_on = $request->gps_on;
            $template->public_description = $request->public_description;
            $template->private_description = $request->private_description;
            $template->work_category_id = $request->work_category_id;
            $template->additional_work_category_id = $request->additional_work_category_id;
            $template->service_type_id = $request->service_type_id;
            $template->qualification_type = $request->has('qualification_type') ? json_encode($request->qualification_type) : null;
            $template->work_order_manager_id = $request->work_order_manager_id;
            $template->additional_contact_id = json_encode($additionalContactIds);
            $template->task = $tasksJson ?? null;
            $template->buyer_custom_field = $request->buyer_custom_field;
            $template->pay_type = $request->pay_type;
            $template->hourly_rate = $request->hourly_rate;
            $template->max_hours = $request->max_hours;
            $template->approximate_hour_complete = $request->approximate_hour_complete;
            $template->total_pay = $request->total_pay;
            $template->per_device_rate = $request->per_device_rate;
            $template->max_device = $request->max_device;
            $template->fixed_payment = $request->fixed_payment;
            $template->fixed_hours = $request->fixed_hours;
            $template->additional_hourly_rate = $request->additional_hourly_rate;
            $template->max_additional_hour = $request->max_additional_hour;
            $template->bank_account_id = $request->bank_account_id;
            $template->rule_id = $request->rule_id;
            $template->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'New template inserted',
                'template' => new TemplateResource($template),
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

    public function edit($id)
    {
        try {
            $template = Template::findOrFail($id); // Retrieve the template by ID
            return response()->json([
                'status' => 'success',
                'template' => new TemplateResource($template), // Use TemplateResource for formatting
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

    public function update(Request $request, $id)
    {
        $rules = [
            'template_name' => 'sometimes|required|string|max:255',
            'default_client_id' => 'nullable',
            'project_id' => 'nullable',
            'work_order_title' => 'nullable|string|max:255',
            'export_button' => 'nullable|boolean',
            'counter_offer' => 'nullable|boolean',
            'gps_on' => 'nullable|boolean',
            'public_description' => 'nullable',
            'private_description' => 'nullable',
            'work_category_id' => 'nullable',
            'additional_work_category_id' => 'nullable',
            'service_type_id' => 'nullable',
            'qualification_type' => 'nullable|array',
            'work_order_manager_id' => 'nullable',
            'additional_contact_info' => 'nullable|array',
            'tasks' => 'nullable|array',
            'buyer_custom_field' => 'nullable',
            'pay_type' => 'nullable|in:Hourly,Fixed,Per Device,Blended',
            'hourly_rate' => 'sometimes|required_if:pay_type,Hourly',
            'max_hours' => 'sometimes|required_if:pay_type,Hourly',
            'total_pay' => 'sometimes|required_if:pay_type,Fixed',
            'per_device_rate' => 'sometimes|required_if:pay_type,Per Device',
            'max_device' => 'sometimes|required_if:pay_type,Per Device',
            'fixed_payment' => 'sometimes|required_if:pay_type,Blended',
            'fixed_hours' => 'sometimes|required_if:pay_type,Blended',
            'additional_hourly_rate' => 'sometimes|required_if:pay_type,Blended',
            'max_additional_hour' => 'sometimes|required_if:pay_type,Blended',
            'bank_account_id' => 'nullable',
            'approximate_hour_complete' => 'nullable',
            'rule_id' => 'nullable',
            'additional_contact_info.*.additional_contact_title' => 'nullable|string|max:255',
            'additional_contact_info.*.additional_contact_name' => 'nullable|string|max:255',
            'additional_contact_info.*.additional_contact_phone' => 'nullable|string|max:20',
            'additional_contact_info.*.additional_contact_ext' => 'nullable|string|max:10',
            'additional_contact_info.*.additional_contact_email' => 'nullable|email|max:255',
            'additional_contact_info.*.additional_contact_note' => 'nullable|string|max:500',
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
            DB::beginTransaction();

            $template = Template::findOrFail($id); // Find the template by ID

            // Update fields if provided in the request
            $template->template_name = $request->template_name ?? $template->template_name;
            $template->default_client_id = $request->default_client_id ?? $template->default_client_id;
            $template->project_id = $request->project_id ?? $template->project_id;
            $template->work_order_title = $request->work_order_title ?? $template->work_order_title;
            $template->export_button = $request->export_button ?? $template->export_button;
            $template->counter_offer = $request->counter_offer ?? $template->counter_offer;
            $template->gps_on = $request->gps_on ?? $template->gps_on;
            $template->public_description = $request->public_description ?? $template->public_description;
            $template->private_description = $request->private_description ?? $template->private_description;
            $template->work_category_id = $request->work_category_id ?? $template->work_category_id;
            $template->additional_work_category_id = $request->additional_work_category_id ?? $template->additional_work_category_id;
            $template->service_type_id = $request->service_type_id ?? $template->service_type_id;
            $template->qualification_type = $request->has('qualification_type') ? json_encode($request->qualification_type) : $template->qualification_type;
            $template->work_order_manager_id = $request->work_order_manager_id ?? $template->work_order_manager_id;

            if ($request->has('tasks')) {
                $tasksJson = json_encode($request->tasks);
                $template->task = $tasksJson;
            }

            $template->buyer_custom_field = $request->buyer_custom_field ?? $template->buyer_custom_field;
            $template->pay_type = $request->pay_type ?? $template->pay_type;
            $template->hourly_rate = $request->hourly_rate ?? $template->hourly_rate;
            $template->max_hours = $request->max_hours ?? $template->max_hours;
            $template->approximate_hour_complete = $request->approximate_hour_complete ?? $template->approximate_hour_complete;
            $template->total_pay = $request->total_pay ?? $template->total_pay;
            $template->per_device_rate = $request->per_device_rate ?? $template->per_device_rate;
            $template->max_device = $request->max_device ?? $template->max_device;
            $template->fixed_payment = $request->fixed_payment ?? $template->fixed_payment;
            $template->fixed_hours = $request->fixed_hours ?? $template->fixed_hours;
            $template->additional_hourly_rate = $request->additional_hourly_rate ?? $template->additional_hourly_rate;
            $template->max_additional_hour = $request->max_additional_hour ?? $template->max_additional_hour;
            $template->bank_account_id = $request->bank_account_id ?? $template->bank_account_id;
            $template->rule_id = $request->rule_id ?? $template->rule_id;

            $template->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Template updated successfully',
                'template' => new TemplateResource($template),
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


    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Find the template
            $template = Template::findOrFail($id);

            // Optional: Check if this template is used by any WorkOrder
            $workOrderExists = WorkOrder::where('template_id', $id)->exists();
            if ($workOrderExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete template, it is associated with existing work orders.',
                ], 400);
            }

            // Optional: Delete linked AdditionalContacts if stored as array of IDs
            if (!empty($template->additional_contact_id)) {
                $contactIds = json_decode($template->additional_contact_id, true);
                if (is_array($contactIds)) {
                    AdditionalContact::whereIn('id', $contactIds)->delete();
                }
            }

            // Delete the template
            $template->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Template successfully deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Template deletion failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                [ServerErrorMask::UNKNOWN_ERROR]
            );

            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    public function FindTemplate(Request $request)
    {

        try {

            $template = Template::where('id', $request->template_id)->first();

            if ($template) {
                // Decode the JSON string into an array
                $qualificationType = json_decode($template->qualification_type, true);

                // Create a new array to hold the transformed data
                $transformedQualificationType = [];

                // Check if qualificationType is an array and iterate over it
                if (is_array($qualificationType)) {
                    foreach ($qualificationType as $type) {
                        // Fetch the QualificationType name
                        $qualificationTypeName = QualificationType::where('id', $type['id'])->value('name');

                        // Fetch the subcategories (licenses) based on the sub_category ids
                        $licenses = QualificationSubCategory::whereIn('id', $type['sub_categories'])->pluck('name');

                        // Structure the data in the required format
                        $transformedQualificationType[] = [
                            'qualification_type_name' => $qualificationTypeName,
                            $qualificationTypeName => $licenses->toArray()  // Convert the collection to an array
                        ];
                    }
                }

                // Replace the original qualification_type with the transformed data
                $template->qualification_type = $transformedQualificationType;

                // Decode the task JSON string into an array
                $tasks = json_decode($template->task, true);

                // Ensure the tasks array exists
                if (isset($tasks['tasks'])) {
                    // Replace the original task field with the array of tasks
                    $template->task = $tasks['tasks'];
                } else {
                    $template->task = [];
                }
            }

            return response()->json([
                'status' => 'success',
                'template' => $template,
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
}
