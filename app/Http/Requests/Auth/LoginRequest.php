<?php

namespace App\Http\Requests\Auth;

use App\Helpers\ApiResponseHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {


        
        return [
            "email" => [
                "required",
                "email",
                "max:30",
                Rule::exists('users', 'email'),
            ],
            "password" => [
                "required",
                "string",
                "min:8",
                "max:20",
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
        return response()->json([
            'errors' => $formattedErrors,
            'payload' => null,
        ], 422);
    }
}
