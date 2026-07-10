<?php

require_once __DIR__ . '/../lib/osrm_client.php';

$profile = osrm_profile($_GET['profile'] ?? 'driving');
$coordinates = osrm_coordinate_list(
    $_GET['coordinates'] ?? '79.8612,6.9271;79.8700,6.9250;79.8800,6.9200;79.8997,6.9147'
);

if ($coordinates === null) {
    send_error('coordinates must look like "lng,lat;lng,lat[;...]"');
    exit;
}

$path = "/match/v1/$profile/$coordinates?overview=full&geometries=geojson";

send_json(osrm_request($path));
