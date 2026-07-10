<?php

// Thin wrapper around the Nominatim (OpenStreetMap) search API — a
// different service from OSRM, so it gets its own endpoint rather than
// living in osrm_client.php.

$query = trim($_GET['q'] ?? '');

header('Content-Type: application/json');

if ($query === '') {
    http_response_code(400);
    echo json_encode(['code' => 'BadRequest', 'message' => 'q is required']);
    exit;
}

$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'q'      => $query,
    'format' => 'json',
    'limit'  => 1,
]);

// Nominatim's usage policy requires a descriptive User-Agent on every request.
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header'  => "User-Agent: OnesNZerosERP-GPSPlayground/1.0 (internal tool)\r\n",
    ],
]);

$json = @file_get_contents($url, false, $context);

if ($json === false) {
    http_response_code(502);
    echo json_encode(['code' => 'RequestFailed', 'message' => 'Could not reach Nominatim']);
    exit;
}

echo $json;
