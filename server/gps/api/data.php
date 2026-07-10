<?php
// GET /server/gps/api/data.php?serial=d_xxxxxxxxxxxxxxxx&limit=10&type=all
//
//   serial  required  GPS unit serial number (its table name, e.g. d_5794832657826592)
//   limit   optional  number of records, latest first (default 100, max 1000);
//                     limit=1 returns just the newest record
//   type    optional  'all' (default) = every column | 'location' = latitude/longitude/speed/heading/route_progress_m only
//
// Records are returned newest-first as a JSON array.

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

$limit = (int) ($_GET['limit'] ?? 100);
if ($limit < 1) {
    $limit = 1;
} elseif ($limit > 1000) {
    $limit = 1000;
}

$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['all', 'location'], true)) {
    api_error("type must be 'all' or 'location'");
}

$pdo = gps_db_connect();

$exists = $pdo->prepare('SHOW TABLES LIKE :serial');
$exists->execute(['serial' => $serial]);
if (!$exists->fetchColumn()) {
    api_error("unknown GPS unit: {$serial}", 404);
}

$columns = $type === 'location' ? 'latitude, longitude, speed, heading, route_progress_m' : '*';
$rows = $pdo->query("SELECT {$columns} FROM `{$serial}` ORDER BY id DESC LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
