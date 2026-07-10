<?php
// GET /server/gps/api/trip.php?serial=d_xxxxxxxxxxxxxxxx
//
//   serial  required  GPS unit serial number (format d_XXXXXXXXXXXXXXXX)
//
// Returns the unit's current trip (gps_direction row): origin/destination
// names, the destination waiting time, and the trip's target average speed.
// This is static per trip — it only changes when the unit is assigned a new
// trip (see gps_path.php: setGpsTrip), unlike data.php/path.php which move
// every ping.

require_once __DIR__ . '/../simulation/lib/gps_db.php';

header('Content-Type: application/json');

function api_error(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

$serial = $_GET['serial'] ?? '';
if (!preg_match('/^d_\d+$/', $serial)) {
    api_error("serial is required and must look like d_XXXXXXXXXXXXXXXX");
}

$pdo = gps_db_connect();

$stmt = $pdo->prepare(
    'SELECT from_location, to_location, waiting_time_minutes, average_speed_kmh
     FROM gps_direction WHERE serial_number = :serial LIMIT 1'
);
$stmt->execute(['serial' => $serial]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    api_error("no trip found for GPS unit: {$serial}", 404);
}

echo json_encode($row);
