<?php

namespace App\Services;

class CommonService{

    public static function generateUuidMd5()
    {
        $uuid = uuid_create(UUID_TYPE_RANDOM);
        $md5Hash = md5($uuid);

        return $md5Hash;
    }

    public function geocodeAddressOSM($address)
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";

        $opts = [
            "http" => [
                "header" => "User-Agent: MyLaravelApp/1.0 (https://example.com)\r\n"
            ]
        ];

        $context = stream_context_create($opts);

        $response = file_get_contents($url, false, $context);

        $data = json_decode($response);

        if (!empty($data)) {
            return [
                'latitude' => $data[0]->lat,
                'longitude' => $data[0]->lon
            ];
        }

        return null;
    }
}
