<?php

namespace App\Imports;

use App\Models\State;
use App\Models\Country;
use App\Models\ClientManager;
use App\Models\WorkOrderManage;
use App\Models\DefaultClientList;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DefaultClientImport implements ToModel, WithHeadingRow
{
    function __construct()
    {
        ini_set('memory_limit', '12900M');
        ini_set('max_execution_time', '999999');
    }

    public function model(array $row)
    {
        $defaultClient = (object) $row;

        return new DefaultClientList([
            'client_title' => $defaultClient->client_title ?? null,
            'client_manager_id' => $this->clientManager($defaultClient->client_manager)->id ?? null,
            'website' => $defaultClient->website ?? null,
            'notes' => $defaultClient->notes ?? null,
            'address_line_one' => $defaultClient->address_line_1 ?? null,
            'address_line_two' => $defaultClient->address_line_2 ?? null,
            'city' => $defaultClient->city ?? null,
            'state_id' => $this->getState($defaultClient->state, $defaultClient->country)->id ?? null,
            'zip_code' => $defaultClient->zip_code ?? null,
            'country_id' => $this->getCountry($defaultClient->country)->id ?? null,
            'location_type' => $defaultClient->location_type ?? null,
        ]);
    }

    public function getState($state, $country)
    {
        $countryInfo = $this->getCountry($country);

        $stateInfo = State::query()
                    ->where('country_id', $countryInfo->id)
                    ->where('name', $state)
                    ->first();

        if($stateInfo){
            $stateData = $stateInfo;
        }else {
            $stateData = new State();
            $stateData->country_id = $countryInfo->id;
            $stateData->name = $state;
            $stateData->save();
        }

        return $stateData;
    }

    public function getCountry($country)
    {
        $countryInfo = Country::query()
                    ->where('name', $country)
                    ->first();

        if($countryInfo){
            $countryData = $countryInfo;
        }else {
            $countryData = new Country();
            $countryData->name = $country;
            $countryData->save();
        }

        return $countryData;
    }

    public function clientManager($client_manager)
    {
        $countryInfo = ClientManager::query()
                    ->where('user_id', Auth::user()->id)
                    ->where('name', $client_manager)
                    ->first();

        return $countryInfo;
    }

}
