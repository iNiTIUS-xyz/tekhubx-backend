<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\Country;
use UnofficialException;
use App\Utils\GlobalConstant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StatesProvincesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assuming US has country_id = 1 and Canada has country_id = 2
        $usCountry = Country::where('short_name', 'US')->first();
        $caCountry = Country::where('short_name', 'CA')->first();

        $usStates = [
            ['name' => 'Alabama', 'short_name' => 'AL', 'tax' => 4.0],
            ['name' => 'Alaska', 'short_name' => 'AK', 'tax' => 0.0],
            ['name' => 'Arizona', 'short_name' => 'AZ', 'tax' => 5.6],
            ['name' => 'Arkansas', 'short_name' => 'AR', 'tax' => 6.5],
            ['name' => 'California', 'short_name' => 'CA', 'tax' => 7.25],
            ['name' => 'Colorado', 'short_name' => 'CO', 'tax' => 2.9],
            ['name' => 'Connecticut', 'short_name' => 'CT', 'tax' => 6.35],
            ['name' => 'Delaware', 'short_name' => 'DE', 'tax' => 0.0],
            ['name' => 'Florida', 'short_name' => 'FL', 'tax' => 6.0],
            ['name' => 'Georgia', 'short_name' => 'GA', 'tax' => 4.0],
            ['name' => 'Hawaii', 'short_name' => 'HI', 'tax' => 4.0],
            ['name' => 'Idaho', 'short_name' => 'ID', 'tax' => 6.0],
            ['name' => 'Illinois', 'short_name' => 'IL', 'tax' => 6.25],
            ['name' => 'Indiana', 'short_name' => 'IN', 'tax' => 7.0],
            ['name' => 'Iowa', 'short_name' => 'IA', 'tax' => 6.0],
            ['name' => 'Kansas', 'short_name' => 'KS', 'tax' => 6.5],
            ['name' => 'Kentucky', 'short_name' => 'KY', 'tax' => 6.0],
            ['name' => 'Louisiana', 'short_name' => 'LA', 'tax' => 4.45],
            ['name' => 'Maine', 'short_name' => 'ME', 'tax' => 5.5],
            ['name' => 'Maryland', 'short_name' => 'MD', 'tax' => 6.0],
            ['name' => 'Massachusetts', 'short_name' => 'MA', 'tax' => 6.25],
            ['name' => 'Michigan', 'short_name' => 'MI', 'tax' => 6.0],
            ['name' => 'Minnesota', 'short_name' => 'MN', 'tax' => 6.875],
            ['name' => 'Mississippi', 'short_name' => 'MS', 'tax' => 7.0],
            ['name' => 'Missouri', 'short_name' => 'MO', 'tax' => 4.225],
            ['name' => 'Montana', 'short_name' => 'MT', 'tax' => 0.0],
            ['name' => 'Nebraska', 'short_name' => 'NE', 'tax' => 5.5],
            ['name' => 'Nevada', 'short_name' => 'NV', 'tax' => 6.85],
            ['name' => 'New Hampshire', 'short_name' => 'NH', 'tax' => 0.0],
            ['name' => 'New Jersey', 'short_name' => 'NJ', 'tax' => 6.625],
            ['name' => 'New Mexico', 'short_name' => 'NM', 'tax' => 5.125],
            ['name' => 'New York', 'short_name' => 'NY', 'tax' => 4.0],
            ['name' => 'North Carolina', 'short_name' => 'NC', 'tax' => 4.75],
            ['name' => 'North Dakota', 'short_name' => 'ND', 'tax' => 5.0],
            ['name' => 'Ohio', 'short_name' => 'OH', 'tax' => 5.75],
            ['name' => 'Oklahoma', 'short_name' => 'OK', 'tax' => 4.5],
            ['name' => 'Oregon', 'short_name' => 'OR', 'tax' => 0.0],
            ['name' => 'Pennsylvania', 'short_name' => 'PA', 'tax' => 6.0],
            ['name' => 'Rhode Island', 'short_name' => 'RI', 'tax' => 7.0],
            ['name' => 'South Carolina', 'short_name' => 'SC', 'tax' => 6.0],
            ['name' => 'South Dakota', 'short_name' => 'SD', 'tax' => 4.5],
            ['name' => 'Tennessee', 'short_name' => 'TN', 'tax' => 7.0],
            ['name' => 'Texas', 'short_name' => 'TX', 'tax' => 6.25],
            ['name' => 'Utah', 'short_name' => 'UT', 'tax' => 5.95],
            ['name' => 'Vermont', 'short_name' => 'VT', 'tax' => 6.0],
            ['name' => 'Virginia', 'short_name' => 'VA', 'tax' => 5.3],
            ['name' => 'Washington', 'short_name' => 'WA', 'tax' => 6.5],
            ['name' => 'West Virginia', 'short_name' => 'WV', 'tax' => 6.0],
            ['name' => 'Wisconsin', 'short_name' => 'WI', 'tax' => 5.0],
            ['name' => 'Wyoming', 'short_name' => 'WY', 'tax' => 4.0],
        ];

        $caProvinces = [
            ['name' => 'Alberta', 'short_name' => 'AB', 'tax' => 5.0],
            ['name' => 'British Columbia', 'short_name' => 'BC', 'tax' => 7.0],
            ['name' => 'Manitoba', 'short_name' => 'MB', 'tax' => 7.0],
            ['name' => 'New Brunswick', 'short_name' => 'NB', 'tax' => 15.0],
            ['name' => 'Newfoundland and Labrador', 'short_name' => 'NL', 'tax' => 15.0],
            ['name' => 'Nova Scotia', 'short_name' => 'NS', 'tax' => 15.0],
            ['name' => 'Ontario', 'short_name' => 'ON', 'tax' => 13.0],
            ['name' => 'Prince Edward Island', 'short_name' => 'PE', 'tax' => 15.0],
            ['name' => 'Quebec', 'short_name' => 'QC', 'tax' => 9.975],
            ['name' => 'Saskatchewan', 'short_name' => 'SK', 'tax' => 6.0],
            ['name' => 'Northwest Territories', 'short_name' => 'NT', 'tax' => 5.0],
            ['name' => 'Nunavut', 'short_name' => 'NU', 'tax' => 5.0],
            ['name' => 'Yukon', 'short_name' => 'YT', 'tax' => 5.0],
        ];

        foreach ($usStates as $state) {
            State::where('country_id', $usCountry->id)->where('name', $state['name'])->update([
                'short_name' => $state['short_name'],
                'tax' => $state['tax'],
                'status' => GlobalConstant::SWITCH[0], // Assuming active status
            ]);
        }

        foreach ($caProvinces as $province) {
            State::where('country_id', $caCountry->id)->where('name', $province['name'])->update([
                'short_name' => $province['short_name'],
                'tax' => $province['tax'],
                'status' => GlobalConstant::SWITCH[0], // Assuming active status
            ]);
        }
    }
}
