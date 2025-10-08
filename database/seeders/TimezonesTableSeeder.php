<?php

namespace Database\Seeders;

use App\Models\Timezone;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TimezonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $timezones = [
            'Alaska',
            'American Samoa',
            'Atlantic',
            'Central',
            'Eastern',
            'Guam',
            'Hawaii-Aleutian Islands',
            'Marshall Islands',
            'Mountain',
            'Pacific',
            'Palau',
            'Mountain (Arizona/non-DST)',
            'Ponape/Micronesia',
            'Atlantic (non-DST)',
            'Eastern (non-DST)',
            'Central (non-DST)',
            'Newfoundland',
            'Western European (WET)',
            'Central European (CET)',
            'Eastern European (EET)',
            'Russia (Moscow)',
            'Belarus',
            'British Time',
            'Venezuela',
            'Irish Time',
            'Argentina',
            'Bolivia',
            'Brazil',
            'Brazil (Amazonas)',
            'Brazil (Acre)',
            'Chile',
            'Columbia',
            'Ecuador',
            'French Guiana',
            'Guyana',
            'Paraguay',
            'Peru',
            'Suriname',
            'Uruguay',
            'Venezuela',
            'Bangladesh',
            'China',
            'India',
            'Japan',
            'Pakistan',
            'Atlantic (DST)',
        ];

        foreach ($timezones as $timezone) {
            Timezone::create(['name' => $timezone]);
        }
    }
}
