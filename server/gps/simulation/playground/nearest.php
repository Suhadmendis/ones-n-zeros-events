<?php

require_once __DIR__ . '/../lib/osrm_client.php';

$profile = osrm_profile($_GET['profile'] ?? 'driving');
$coordinate = osrm_single_coordinate($_GET['coordinate'] ?? '79.8612,6.9271');
$number = max(1, min(10, (int) ($_GET['number'] ?? 3)));

if ($coordinate === null) {
    send_error('coordinate must look like "lng,lat"');
    exit;
}

$path = "/nearest/v1/$profile/$coordinate?number=$number";

send_json(osrm_request($path));
