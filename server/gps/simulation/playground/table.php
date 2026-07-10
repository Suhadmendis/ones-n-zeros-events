<?php

require_once __DIR__ . '/../lib/osrm_client.php';

$profile = osrm_profile($_GET['profile'] ?? 'driving');
$coordinates = osrm_coordinate_list($_GET['coordinates'] ?? '79.8612,6.9271;79.8997,6.9147;79.8750,6.9350');

if ($coordinates === null) {
    send_error('coordinates must look like "lng,lat;lng,lat[;...]"');
    exit;
}

$path = "/table/v1/$profile/$coordinates?annotations=duration,distance";

send_json(osrm_request($path));
