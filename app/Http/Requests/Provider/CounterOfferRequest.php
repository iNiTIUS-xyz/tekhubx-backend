<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CounterOfferRequest extends FormRequest
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
        return [];

        // return [
        //     'counter_offers' => 'required|array',
        //     'counter_offers.*.work_order_unique_id' => 'nullable|integer',
        //     'counter_offers.*.provider_id' => 'nullable|integer',
        //     'counter_offers.*.employed_providers' => 'nullable|integer',
        //     'counter_offers.*.reason' => 'required|integer',
        //     'counter_offers.*.withdraw' => 'nullable|integer',
        //     'counter_offers.*.counter_offer_pays' => 'nullable|array',
        //     'counter_offers.*.counter_offer_pays.type' => 'nullable|string|in:Hourly,Fixed,Per Device,Blended',
        //     'counter_offers.*.counter_offer_pays.amount_per_hour' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.max_hour' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.fixed_amount' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.amount_per_device' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.max_device' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.fixed_amount_max_hours' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.hourly_amount_after' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_pays.hourly_amount_max_hours' => 'nullable|numeric',
        //     'counter_offers.*.counter_offer_expenses' => 'nullable|array',
        //     'counter_offers.*.counter_offer_expenses.*.counter_offer_id' => 'required|integer',
        //     'counter_offers.*.counter_offer_expenses.*.category' => 'required|string',
        //     'counter_offers.*.counter_offer_expenses.*.description' => 'required|string',
        //     'counter_offers.*.counter_offer_expenses.*.total_amount' => 'required|numeric',
        //     'counter_offers.*.counter_offer_schedule' => 'nullable|array',
        //     'counter_offers.*.counter_offer_schedule.type' => 'required|string',
        //     'counter_offers.*.counter_offer_schedule.arrive_on' => 'nullable',
        //     'counter_offers.*.counter_offer_schedule.start_at' => 'nullable',
        //     'counter_offers.*.counter_offer_schedule.start_date' => 'nullable',
        //     'counter_offers.*.counter_offer_schedule.start_time' => 'nullable',
        //     'counter_offers.*.counter_offer_schedule.end_date' => 'nullable',
        //     'counter_offers.*.counter_offer_schedule.end_time' => 'nullable',
        // ];
        //
        // return [
        //     'counter_offers' => ['required', 'array'],
        //     'counter_offers.*.work_order_unique_id' => ['nullable', 'integer'],
        //     'counter_offers.*.provider_id' => ['nullable', 'integer'],
        //     'counter_offers.*.employed_providers' => ['nullable', 'integer'],
        //     'counter_offers.*.reason' => ['required', 'integer'],
        //     'counter_offers.*.withdraw' => ['nullable', 'integer'],
        //     'counter_offers.*.counter_offer_pays' => ['nullable', 'array'],
        //     'counter_offers.*.counter_offer_pays.counter_offer_id' => ['required_with:counter_offers.*.counter_offer_pays', 'integer'],
        //     'counter_offers.*.counter_offer_pays.type' => ['required_with:counter_offers.*.counter_offer_pays', 'in:Hourly,Fixed,Per Device,Blended'],
        //     'counter_offers.*.counter_offer_pays.amount_per_hour' => [
        //         'nullable',
        //         'numeric',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         ($counterOfferPays['type'] == 'Hourly' || $counterOfferPays['type'] == 'Per Device');
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.max_hour' => [
        //         'nullable',
        //         'integer',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         $counterOfferPays['type'] == 'Hourly';
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.fixed_amount' => [
        //         'nullable',
        //         'numeric',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         ($counterOfferPays['type'] == 'Fixed' || $counterOfferPays['type'] == 'Blended');
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.amount_per_device' => ['nullable', 'numeric'],
        //     'counter_offers.*.counter_offer_pays.max_device' => [
        //         'nullable',
        //         'integer',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         $counterOfferPays['type'] == 'Per Device';
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.fixed_amount_max_hours' => [
        //         'nullable',
        //         'numeric',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         $counterOfferPays['type'] == 'Blended';
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.hourly_amount_after' => [
        //         'nullable',
        //         'numeric',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         $counterOfferPays['type'] == 'Blended';
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_pays.hourly_amount_max_hours' => [
        //         'nullable',
        //         'numeric',
        //         // Rule::requiredIf(function () {
        //         //     $counterOfferPays = $this->input('counter_offers.*.counter_offer_pays');
        //         //     return isset($counterOfferPays['type']) &&
        //         //         $counterOfferPays['type'] == 'Blended';
        //         // })
        //     ],
        //     'counter_offers.*.counter_offer_expenses' => ['nullable', 'array'],
        //     'counter_offers.*.counter_offer_expenses.*.counter_offer_id' => ['required_with:counter_offers.*.counter_offer_expenses', 'integer'],
        //     'counter_offers.*.counter_offer_expenses.*.category' => ['required_with:counter_offers.*.counter_offer_expenses', 'in:Freight,Personal material costs,Real material costs,Scope of work changes,Taxes,Travel,Expense'],
        //     'counter_offers.*.counter_offer_expenses.*.description' => ['required_with:counter_offers.*.counter_offer_expenses', 'string'],
        //     'counter_offers.*.counter_offer_expenses.*.total_amount' => ['required_with:counter_offers.*.counter_offer_expenses', 'numeric'],
        //     'counter_offers.*.counter_offer_schedule' => ['nullable', 'array'],
        //     'counter_offers.*.counter_offer_schedule.counter_offer_id' => ['required_with:counter_offers.*.counter_offer_schedule', 'integer'],
        //     'counter_offers.*.counter_offer_schedule.type' => ['required_with:counter_offers.*.counter_offer_schedule', 'in:Range,Arrive at a specific date and time'],
        //     'counter_offers.*.counter_offer_schedule.arrive_on' => ['required_with:counter_offers.*.counter_offer_schedule', 'date'],
        //     'counter_offers.*.counter_offer_schedule.start_at' => ['required_with:counter_offers.*.counter_offer_schedule', 'date_format:Y-m-d\TH:i:s\Z'],
        //     'counter_offers.*.counter_offer_schedule.start_date' => ['required_with:counter_offers.*.counter_offer_schedule', 'date'],
        //     'counter_offers.*.counter_offer_schedule.start_time' => ['required_with:counter_offers.*.counter_offer_schedule', 'date_format:H:i'],
        //     'counter_offers.*.counter_offer_schedule.end_date' => ['required_with:counter_offers.*.counter_offer_schedule', 'date'],
        //     'counter_offers.*.counter_offer_schedule.end_time' => ['required_with:counter_offers.*.counter_offer_schedule', 'date_format:H:i'],
        // ];
    }



    // public function rules()
    // {
    //     return [
    //         'counter_offers' => 'required|array',
    //         'counter_offers.*.work_order_unique_id' => 'required|integer',
    //         'counter_offers.*.provider_id' => 'required|integer',
    //         'counter_offers.*.employed_providers' => 'required|integer',
    //         'counter_offers.*.reason' => 'required|integer',
    //         'counter_offers.*.withdraw' => 'required|integer',

    //         'counter_offers.*.counter_offer_pays' => 'nullable|array',
    //         'counter_offers.*.counter_offer_pays.counter_offer_id' => 'required_with:counter_offers.*.counter_offer_pays|integer',
    //         'counter_offers.*.counter_offer_pays.type' => 'required_with:counter_offers.*.counter_offer_pays|in:Hourly,Fixed,Per Device,Blended',

    //         'counter_offers.*.counter_offer_pays.amount_per_hour' => 'nullable|numeric',
    //         'counter_offers.*.counter_offer_pays.max_hour' => 'nullable|integer',
    //         'counter_offers.*.counter_offer_pays.fixed_amount' => 'nullable|numeric',
    //         'counter_offers.*.counter_offer_pays.amount_per_device' => 'nullable|numeric',
    //         'counter_offers.*.counter_offer_pays.max_device' => 'nullable|integer',
    //         'counter_offers.*.counter_offer_pays.fixed_amount_max_hours' => 'nullable|numeric',
    //         'counter_offers.*.counter_offer_pays.hourly_amount_after' => 'nullable|numeric',
    //         'counter_offers.*.counter_offer_pays.hourly_amount_max_hours' => 'nullable|numeric',

    //         'counter_offers.*.counter_offer_expenses' => 'nullable|array',
    //         'counter_offers.*.counter_offer_expenses.*.counter_offer_id' => 'required_with:counter_offers.*.counter_offer_expenses|integer',
    //         'counter_offers.*.counter_offer_expenses.*.category' => 'required_with:counter_offers.*.counter_offer_expenses|string',
    //         'counter_offers.*.counter_offer_expenses.*.description' => 'required_with:counter_offers.*.counter_offer_expenses|string',
    //         'counter_offers.*.counter_offer_expenses.*.total_amount' => 'required_with:counter_offers.*.counter_offer_expenses|numeric',

    //         'counter_offers.*.counter_offer_schedule' => 'nullable|array',
    //         'counter_offers.*.counter_offer_schedule.counter_offer_id' => 'required_with:counter_offers.*.counter_offer_schedule|integer',
    //         'counter_offers.*.counter_offer_schedule.type' => 'required_with:counter_offers.*.counter_offer_schedule|string',
    //         'counter_offers.*.counter_offer_schedule.arrive_on' => 'required_with:counter_offers.*.counter_offer_schedule|date',
    //         'counter_offers.*.counter_offer_schedule.start_at' => 'required_with:counter_offers.*.counter_offer_schedule|date_format:Y-m-d\TH:i:s\Z',
    //         'counter_offers.*.counter_offer_schedule.start_date' => 'required_with:counter_offers.*.counter_offer_schedule|date',
    //         'counter_offers.*.counter_offer_schedule.start_time' => 'required_with:counter_offers.*.counter_offer_schedule|date_format:H:i',
    //         'counter_offers.*.counter_offer_schedule.end_date' => 'required_with:counter_offers.*.counter_offer_schedule|date',
    //         'counter_offers.*.counter_offer_schedule.end_time' => 'required_with:counter_offers.*.counter_offer_schedule|date_format:H:i',
    //     ];
    // }

    // protected function prepareForValidation()
    // {
    //     $counterOffers = $this->get('counter_offers', []);

    //     foreach ($counterOffers as $index => $offer) {
    //         if (isset($offer['counter_offer_pays']['type'])) {
    //             switch ($offer['counter_offer_pays']['type']) {
    //                 case 'Hourly':
    //                     $this->merge([
    //                         "counter_offers.$index.counter_offer_pays.amount_per_hour" => $offer['counter_offer_pays']['amount_per_hour'] ?? null,
    //                         "counter_offers.$index.counter_offer_pays.max_hour" => $offer['counter_offer_pays']['max_hour'] ?? null,
    //                     ]);
    //                     break;

    //                 case 'Fixed':
    //                     $this->merge([
    //                         "counter_offers.$index.counter_offer_pays.fixed_amount" => $offer['counter_offer_pays']['fixed_amount'] ?? null,
    //                     ]);
    //                     break;

    //                 case 'Per Device':
    //                     $this->merge([
    //                         "counter_offers.$index.counter_offer_pays.amount_per_hour" => $offer['counter_offer_pays']['amount_per_hour'] ?? null,
    //                         "counter_offers.$index.counter_offer_pays.max_device" => $offer['counter_offer_pays']['max_device'] ?? null,
    //                     ]);
    //                     break;

    //                 case 'Blended':
    //                     $this->merge([
    //                         "counter_offers.$index.counter_offer_pays.fixed_amount" => $offer['counter_offer_pays']['fixed_amount'] ?? null,
    //                         "counter_offers.$index.counter_offer_pays.fixed_amount_max_hours" => $offer['counter_offer_pays']['fixed_amount_max_hours'] ?? null,
    //                         "counter_offers.$index.counter_offer_pays.hourly_amount_after" => $offer['counter_offer_pays']['hourly_amount_after'] ?? null,
    //                         "counter_offers.$index.counter_offer_pays.hourly_amount_max_hours" => $offer['counter_offer_pays']['hourly_amount_max_hours'] ?? null,
    //                     ]);
    //                     break;
    //             }
    //         }
    //     }
    // }

    // public function messages()
    // {
    //     return [
    //         'counter_offers.required' => 'The counter offers field is required.',
    //         'counter_offers.array' => 'The counter offers field must be an array.',
    //         'counter_offers.*.work_order_unique_id.required' => 'The work order unique ID is required for each counter offer.',
    //         'counter_offers.*.provider_id.required' => 'The provider ID is required for each counter offer.',
    //         'counter_offers.*.employed_providers.required' => 'The number of employed providers is required for each counter offer.',
    //         'counter_offers.*.reason.required' => 'The reason field is required for each counter offer.',
    //         'counter_offers.*.withdraw.required' => 'The withdraw field is required for each counter offer.',
    //         'counter_offers.*.counter_offer_pays.type.in' => 'The type must be one of the following: Hourly, Fixed, Per Device, Blended.',
    //         // Add more custom messages as needed
    //     ];
    // }
}
