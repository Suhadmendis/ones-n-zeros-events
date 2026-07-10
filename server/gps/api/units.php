<?php
// GET /server/gps/api/units.php
// Returns every GPS unit known to the system: the serial-number table names.

require_once __DIR__ . '/../simulation/lib/gps_db.php';

header('Content-Type: application/json');

$pdo = gps_db_connect();
$units = $pdo->query("SHOW TABLES LIKE 'd\\_%'")->fetchAll(PDO::FETCH_COLUMN);
sort($units);

echo json_encode($units);
