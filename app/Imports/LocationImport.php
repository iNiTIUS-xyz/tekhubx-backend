<?php

namespace App\Imports;

use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Str;
use App\Services\CommonService;
use App\Models\AdditionalLocation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationImport implements ToModel, WithHeadingRow
{
    protected $CommonService;
    public function __construct(CommonService $CommonService)
    {
        $this->CommonService = $CommonService;
    }

    public function model(array $row)
    {
        $location = (object) $row;

        $country_name = Country::where('id', $location->country_id)->first();
        $state_name = State::where('id', $location->state_id)->first();

        // Construct the full address
        $address_1 = $location->address_1;
        $city = $location->city;
        $zip_code = $location->zip_code;
        $full_address = "{$address_1}, {$city}, {$state_name->name}, {$zip_code}, {$country_name->name}";

        // Get latitude and longitude using the geocoding function
        $geoLocation = $this->CommonService->geocodeAddressOSM($full_address);

        if ($geoLocation) {
            $latitude = $geoLocation['latitude'];
            $longitude = $geoLocation['longitude'];
        } else {
            $latitude = null;
            $longitude = null;
        }

        return new AdditionalLocation([

            'uuid' => $this->generateShortUuid(),
            'name' => $location->name,
            'display_name' => $location->display_name,
            'default_client_id' => $location->default_client_id ?? null,
            'location_group_id' => $location->location_group_id ?? null,
            'country_id' => $location->country_id,
            'location_type' => $location->location_type,
            'address_line_1' => $location->address_1,
            'address_line_2' => $location->address_2 ?? null,
            'city' => $location->city,
            'state_id' => $location->state_id,
            'zip_code' => $location->zip_code,
            'name_description' => $location->name_description ?? null,
            'phone' => $location->phone ?? null,
            'phone_ext' => $location->phone_ext ?? null,
            'email' => $location->email ?? null,
            'note' => $location->note ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);
    }

    private function generateShortUuid()
    {
        return str_replace('-', '', Str::uuid()->toString()); // Remove hyphens to get 32 characters
    }
}
