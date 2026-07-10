<?php
// GET /server/gps/api/path.php?serial=d_xxxxxxxxxxxxxxxx
//
//   serial  required  GPS unit serial number (format d_XXXXXXXXXXXXXXXX)
//
// Returns the road-network route geometry (built from OSRM) for that unit's
// current trip, ordered from route start to end. Combine with a ping's
// route_progress_m (from data.php?type=location) to find exactly where along
// this geometry the vehicle currently is — the two points bracketing that
// progress give the true road-tangent heading, and the interpolated point
// between them gives a road-snapped position, both more accurate than raw
// point-to-point GPS ping math.

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
    'SELECT seq, latitude, longitude, cumulative_distance_m FROM gps_path WHERE serial_number = :serial ORDER BY seq ASC'
);
$stmt->execute(['serial' => $serial]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    api_error("no route found for GPS unit: {$serial}", 404);
}

echo json_encode($rows);
