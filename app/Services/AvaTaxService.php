<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\WorkCategory;
use Illuminate\Support\Carbon;
use App\Models\WorkSubCategory;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class AvaTaxService
{
    protected $client;
    protected $authHeader;
    protected $companyId;

    public function __construct()
    {
        $this->client = new Client();
        $this->companyId = 8443163;
        $this->authHeader = 'Basic ' . base64_encode(env('AVATAX_USERNAME') . ':' . env('AVATAX_PASSWORD'));
    }

    /**
     * Get Avalara items and match with work_category_id
     *
     * @param int $workCategoryId
     * @return array|null Avalara item or null if not found
     */
    public function getAvalaraItemByWorkCategoryId($workCategoryId)
    {
        try {
            $url = "https://sandbox-rest.avatax.com/api/v2/companies/{$this->companyId}/items";
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => $this->authHeader,
                    'X-Avalara-Client' => 'TekHubX; 1.0; Custom; 1.0',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $items = json_decode($response->getBody()->getContents(), true)['value'] ?? [];

            // Get the work category name from the database
            $workCategory = WorkSubCategory::find($workCategoryId);
            if (!$workCategory) {
                return null;
            }

            // Find matching Avalara item by description
            foreach ($items as $item) {
                if (strcasecmp($item['description'], $workCategory->name) === 0) {
                    return $item;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Avalara Item Fetching Failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calculate tax using Avalara API
     *
     * @param object $state State information (must have `short_name` property)
     * @param object $country Country information (must have `short_name` property)
     * @param array $request Request data containing `zip_code` and `work_category_id`
     * @param float $total The total amount for which tax needs to be calculated
     * @return float Tax value or 0.0 on failure
     */
    public function calculateTax($state, $country, $request, $total)
    {
        $taxValue = 0.0;

        // Get Avalara item for the given work_category_id
        $avalaraItem = $this->getAvalaraItemByWorkCategoryId($request['work_category_id']);
        // dd($avalaraItem);
        if (!$avalaraItem) {
            Log::error("No matching Avalara item found for work_category_id: " . $request['work_category_id']);
            return 0.0;
        }

        $url = 'https://sandbox-rest.avatax.com/api/v2/transactions/create'; // Use sandbox or live URL as needed

        $payload = [
            "companyCode" => env('AVATAX_COMPANY_CODE'), // Company Code
            "type" => "SalesInvoice",
            "date" => Carbon::now()->format('Y-m-d'),
            "customerCode" => env('AVATAX_ACCOUNT_ID'), // Avalara Customer Account ID
            "addresses" => [
                "SingleLocation" => [
                    "region" => $state->short_name,
                    "country" => $country->short_name,
                    "postalCode" => $request['zip_code'] ?? '',
                ]
            ],
            "lines" => [
                [
                    "number" => "1",
                    "quantity" => 1,
                    "amount" => $total,
                    "taxCode" => $avalaraItem['taxCode'] // Use matched tax code
                ]
            ]
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => $this->authHeader,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            // dd($responseBody); // Debugging line to see the full response

            // Extract tax value from the response
            if (isset($responseBody['lines']) && is_array($responseBody['lines'])) {
                foreach ($responseBody['lines'] as $line) {
                    if (isset($line['details']) && is_array($line['details'])) {
                        foreach ($line['details'] as $detail) {
                            if (isset($detail['rate'])) {
                                $lineAmount = $line['lineAmount'] ?? 0; // Use lineAmount instead of amount
                                $taxValue += $detail['rate'] * $lineAmount; // Calculate tax for each line
                            }
                        }
                    }
                }
            }

            return $taxValue;
        } catch (ClientException $e) {
            // Log detailed error response for debugging
            Log::error('Avalara API Request Failed', [
                'url' => $url,
                'payload' => $payload,
                'response' => $e->getResponse()->getBody()->getContents(),
            ]);

            return 0.0; // Return 0.0 on failure
        }
    }
}
