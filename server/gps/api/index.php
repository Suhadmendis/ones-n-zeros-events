<?php
// GET /server/gps/api/index.php
// GET /server/gps/api/index.php?endpoint=<name>&...
//
// Single entry point for the GPS API — call it with no params to get a
// directory of every endpoint this folder serves (name, file, params,
// summary), or pass `endpoint` to dispatch straight to that endpoint's
// script (its own query params still apply, e.g. &serial=...&limit=...).
// This exists so a developer can land on one file and see everything the
// GPS API produces, instead of having to open units.php/data.php/path.php/
// trip.php individually.
//
//   endpoint=units  -> units.php   list every GPS unit's serial number
//   endpoint=data   -> data.php    fetch a unit's GPS records (serial, limit, type)
//   endpoint=path   -> path.php    a unit's current route geometry (serial)
//   endpoint=trip   -> trip.php    a unit's current trip info (serial)
//   endpoint=range  -> range.php   a unit's full record history, optionally date-filtered (serial, from, to)

header('Content-Type: application/json');

const ENDPOINTS = [
    'units' => [
        'file' => 'units.php',
        'params' => [],
        'summary' => "List every GPS unit known to the system (serial numbers).",
    ],
    'data' => [
        'file' => 'data.php',
        'params' => [
            'serial' => 'required, format d_XXXXXXXXXXXXXXXX',
            'limit' => 'optional, default 100, max 1000, newest first',
            'type' => "optional, 'all' (default, every column) or 'location' (lat/lng/speed/heading/route_progress_m)",
        ],
        'summary' => "Fetch a unit's GPS records, newest first.",
    ],
    'path' => [
        'file' => 'path.php',
        'params' => ['serial' => 'required, format d_XXXXXXXXXXXXXXXX'],
        'summary' => "A unit's current road-network route geometry (built from OSRM), start to end.",
    ],
    'trip' => [
        'file' => 'trip.php',
        'params' => ['serial' => 'required, format d_XXXXXXXXXXXXXXXX'],
        'summary' => "A unit's current trip info: origin/destination, waiting time, target average speed.",
    ],
    'range' => [
        'file' => 'range.php',
        'params' => [
            'serial' => 'required, format d_XXXXXXXXXXXXXXXX',
            'from' => 'optional, YYYY-MM-DD or YYYY-MM-DD HH:MM:SS — records at/after this date',
            'to' => 'optional, YYYY-MM-DD or YYYY-MM-DD HH:MM:SS — records at/before this date',
        ],
        'summary' => "A unit's full record history (every column); omit from/to for the whole table, pass only from for everything since that date.",
    ],
];

$endpoint = $_GET['endpoint'] ?? null;

if ($endpoint === null) {
    echo json_encode([
        'base_path' => '/server/gps/api/',
        'usage' => 'GET index.php?endpoint=<name>&... (or call the endpoint file directly)',
        'endpoints' => ENDPOINTS,
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!isset(ENDPOINTS[$endpoint])) {
    http_response_code(400);
    echo json_encode(['error' => "unknown endpoint: {$endpoint}", 'available' => array_keys(ENDPOINTS)]);
    exit;
}

// Forward straight through to the target script; it reads the rest of $_GET itself.
require __DIR__ . '/' . ENDPOINTS[$endpoint]['file'];
