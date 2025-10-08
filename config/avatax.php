<?php
// config/avatax.php

return [
    'app_name' => 'TekHubX',
    'app_version' => '1.0',
    'machine_name' => 'localhost',
    'environment' => env('AVATAX_ENVIRONMENT', 'sandbox'), // Change to 'production' for live
    'account_id' => env('AVATAX_ACCOUNT_ID', '2006459509'),
    'license_key' => env('AVATAX_LICENSE_KEY', '150E9222B534693B'),
    'company_code' => env('AVATAX_COMPANY_CODE', 'TekHubX'),
];
