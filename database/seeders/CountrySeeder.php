<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CountrySeeder extends Seeder
{

    function __construct()
    {
        ini_set('memory_limit', '12900M');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $this->loadCountry();
            $this->loadState();

            DB::commit();
            $this->command->info('Country & State Successfully Seeded');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->info($e->getMessage());
        }
    }

    public function loadCountry()
    {
        $data = collect(json_decode(Storage::get('public/country/countries.json'), true));

        $countries = $data['countries'];

        foreach ($countries as $country) {
            $newCountry = new Country();
            $newCountry->name = $country['name'];
            $newCountry->save();
        }

        return true;
    }

    public function loadState()
    {
        $data = collect(json_decode(Storage::get('public/country/states.json'), true));

        $states = $data['states'];

        foreach ($states as $state) {
            $newState = new State();
            $newState->country_id = $state['country_id'];
            $newState->name = $state['name'];
            $newState->save();
        }

        return true;
    }
}
