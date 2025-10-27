<?php

namespace App\Http\Controllers\Client\defaultClient;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Classes\FileUploadClass;
use App\Models\DefaultClientList;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Imports\DefaultClientImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Client\DefaultClientListResource;

class DefaultClientListController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    public function index()
    {
        try {

            $defaultClients = DefaultClientList::where('uuid', Auth::user()->uuid)->get();

            return response()->json([
                'status' => 'success',
                'defaultClient' => DefaultClientListResource::collection($defaultClients),
            ]);
        } catch (\Exception $e) {
            Log::error('Default Client query not found' . $e->getMessage());
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
            'client_title' => 'required|max:30',
            'client_manager_id' => 'required',
            'website' => 'nullable',
            'notes' => 'nullable',
            'policies' => 'nullable',
            'instructions' => 'nullable',
            'address_line_one' => 'nullable',
            'address_line_two' => 'nullable',
            'city' => 'nullable',
            'zip_code' => 'required|integer',
            'state_id' => 'required|exists:states,id',
            'country_id' => 'required|exists:countries,id',
            'location_type' => 'nullable|in:Commercial,Government,Residential,Educational,Other',
            'logo' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'company_name_logo' => 'nullable',
            'client_name_logo' => 'nullable',
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
            $defaultClients = new DefaultClientList();
            $defaultClients->uuid = Auth::user()->uuid;
            $defaultClients->client_title = $request->client_title;
            $defaultClients->client_manager_id = $request->client_manager_id;
            $defaultClients->website = $request->website;
            $defaultClients->notes = $request->notes;
            $defaultClients->policies = $request->policies;
            $defaultClients->instructions = $request->instructions;
            $defaultClients->address_line_one = $request->address_line_one;
            $defaultClients->address_line_two = $request->address_line_two;
            $defaultClients->city = $request->city;
            $defaultClients->state_id = $request->state_id;
            $defaultClients->zip_code = $request->zip_code;
            $defaultClients->country_id = $request->country_id;
            $defaultClients->location_type = $request->location_type;
            $defaultClients->company_name_logo = $request->company_name_logo ? true : false;
            $defaultClients->client_name_logo = $request->client_name_logo ? true : false;

            if ($request->hasFile("logo")) {
                $logo_url = $this->fileUpload->imageUploader($request->file('logo'), 'defaultClient', 200, 200);
                $defaultClients->logo = $logo_url;
            }
            $defaultClients->status = "Active";

            $defaultClients->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Default Client Successfully Save',
                'defaultClient' => new DefaultClientListResource($defaultClients),
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
            $defaultClients = DefaultClientList::query()
                ->findOrFail($id);

            if (!$defaultClients) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Default Client not found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'defaultClient' => new DefaultClientListResource($defaultClients),
            ]);
        } catch (\Exception $e) {
            Log::error('Default Client query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info($request->all());
        $rules = [
            'client_title' => 'nullable|max:30',
            'client_manager_id' => 'nullable|exists:users,id',
            'website' => 'nullable',
            'notes' => 'nullable',
            'policies' => 'nullable',
            'instructions' => 'nullable',
            'address_line_one' => 'nullable',
            'address_line_two' => 'nullable',
            'city' => 'nullable',
            'zip_code' => 'nullable|integer',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'nullable|exists:countries,id',
            'location_type' => 'nullable|in:Commercial,Government,Residential,Educational,Other',
            'logo' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'company_name_logo' => 'nullable',
            'client_name_logo' => 'nullable',
            'status' => 'nullable|in:Active,Hidden',
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
            $defaultClients = DefaultClientList::find($id);

            if (!$defaultClients) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Default Client not found',
                ], 404);
            }

            $defaultClients->client_title = $request->client_title ?? $defaultClients->client_title;
            $defaultClients->client_manager_id = $request->client_manager_id ?? $defaultClients->client_manager_id;
            $defaultClients->website = $request->website ?? $defaultClients->website;
            $defaultClients->notes = $request->notes ?? $defaultClients->notes;
            $defaultClients->policies = $request->policies ?? $defaultClients->policies;
            $defaultClients->instructions = $request->instructions ?? $defaultClients->instructions;
            $defaultClients->address_line_one = $request->address_line_one ?? $defaultClients->address_line_one;
            $defaultClients->address_line_two = $request->address_line_two ?? $defaultClients->address_line_two;
            $defaultClients->city = $request->city ?? $defaultClients->city;
            $defaultClients->state_id = $request->state_id ?? $defaultClients->state_id;
            $defaultClients->zip_code = $request->zip_code ?? $defaultClients->zip_code;
            $defaultClients->country_id = $request->country_id ?? $defaultClients->country_id;
            $defaultClients->location_type = $request->location_type ?? $defaultClients->location_type;
            $defaultClients->company_name_logo = $request->company_name_logo ?? $defaultClients->company_name_logo;
            $defaultClients->client_name_logo = $request->client_name_logo ?? $defaultClients->client_name_logo;

            if ($request->hasFile("logo")) {
                $this->fileUpload->fileUnlink($defaultClients->logo);
                $logo_url = $this->fileUpload->imageUploader($request->file('logo'), 'defaultClient', 200, 200);
                $defaultClients->logo = $logo_url;
            }

            $defaultClients->status = $request->status;

            $defaultClients->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Default Client Successfully Update',
                'defaultClient' => new DefaultClientListResource($defaultClients->refresh()),
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

            $defaultClient = DefaultClientList::query()
                ->findOrFail($id);

            if (!$defaultClient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Default Client not found',
                ], 404);
            }

             $work_orderExists = WorkOrder::query()
                ->where('uuid', Auth::user()->uuid)
                ->where('default_client_id', $defaultClient->id)
                ->exists();
            if ($work_orderExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete location, it is associated with existing work orders.',
                ], 400);
            }
            $this->fileUpload->fileUnlink($defaultClient->logo);

            $defaultClient->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Default Client Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Default Client query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $rules = [
            'client_excel_file' => 'required|mimes:xlsx',
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
            Excel::import(new DefaultClientImport, $request->client_excel_file);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Default Client successfully imported',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Default client excel import has problem' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    // public function excelDownload()
    // {
    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();

    //     $columns = [
    //         'Client Title',
    //         'Client Manager',
    //         'Website',
    //         'Notes',
    //         'Address Line 1',
    //         'Address Line 2',
    //         'City',
    //         'State',
    //         'Zip Code',
    //         'Country',
    //         'Location Type',
    //     ];

    //     foreach ($columns as $key => $value) {
    //         $sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
    //     }

    //     $clientManagerNames = ClientManager::where('client_id', Auth::user()->id)->pluck('name')->toArray();
    //     $clientManagerNamesString = implode(',', $clientManagerNames);

    //     $locationType = GlobalConstant::LOCATION_TYPE;
    //     $locationTypeString = implode(',', $locationType);

    //     $countryNames = Country::pluck('name')->toArray();
    //     $countryNamesString = implode(',', $countryNames);

    //     $highestRow = 50;
    //     $dropdownColumnClientManager = 'B';
    //     $dropdownColumnLocationType = 'K';
    //     $dropdownColumnCountry = 'J';

    //     for ($row = 2; $row <= $highestRow; $row++) {
    //         // Client Manager dropdown
    //         $cellClientManager = $sheet->getCell($dropdownColumnClientManager . $row);
    //         $validationClientManager = $cellClientManager->getDataValidation();
    //         $validationClientManager->setType(DataValidation::TYPE_LIST);
    //         $validationClientManager->setErrorStyle(DataValidation::STYLE_INFORMATION);
    //         $validationClientManager->setAllowBlank(true);
    //         $validationClientManager->setShowInputMessage(true);
    //         $validationClientManager->setShowErrorMessage(true);
    //         $validationClientManager->setShowDropDown(true);
    //         $validationClientManager->setFormula1('"' . $clientManagerNamesString . '"');

    //         // Location Type dropdown
    //         $cellLocationType = $sheet->getCell($dropdownColumnLocationType . $row);
    //         $validationLocationType = $cellLocationType->getDataValidation();
    //         $validationLocationType->setType(DataValidation::TYPE_LIST);
    //         $validationLocationType->setErrorStyle(DataValidation::STYLE_INFORMATION);
    //         $validationLocationType->setAllowBlank(true);
    //         $validationLocationType->setShowInputMessage(true);
    //         $validationLocationType->setShowErrorMessage(true);
    //         $validationLocationType->setShowDropDown(true);
    //         $validationLocationType->setFormula1('"' . $locationTypeString . '"');

    //         // country Type dropdown
    //         $cellCountry = $sheet->getCell($dropdownColumnCountry . $row);
    //         $validationCountryType = $cellCountry->getDataValidation();
    //         $validationCountryType->setType(DataValidation::TYPE_LIST);
    //         $validationCountryType->setErrorStyle(DataValidation::STYLE_INFORMATION);
    //         $validationCountryType->setAllowBlank(true);
    //         $validationCountryType->setShowInputMessage(true);
    //         $validationCountryType->setShowErrorMessage(true);
    //         $validationCountryType->setShowDropDown(true);
    //         $validationCountryType->setFormula1('"' . $countryNamesString . '"');
    //     }

    //     $now = Carbon::now();
    //     $formatted = $now->format('Y_F_d_\a\t_g_i_s_A');
    //     $fileName = 'Client_Template_' . $formatted . '.xlsx';
    //     $filePath = 'template/' . Auth::user()->id . '/' . $fileName;

    //     if (!Storage::disk('public')->exists(dirname($filePath))) {
    //         Storage::disk('public')->makeDirectory(dirname($filePath), 0777, true, true);
    //     }

    //     $writer = new Xlsx($spreadsheet);
    //     $writer->save(storage_path('app/public/' . $filePath));

    //     $url = Storage::url($filePath);

    //     return response()->json([
    //         'status' => 'success',
    //         'download_url' => $url,
    //     ]);
    // }
}
