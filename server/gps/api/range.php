<?php
// GET /server/gps/api/range.php?serial=d_xxxxxxxxxxxxxxxx&from=YYYY-MM-DD[ HH:MM:SS]&to=YYYY-MM-DD[ HH:MM:SS]
//
//   serial  required  GPS unit serial number (its table name, e.g. d_5794832657826592)
//   from    optional  only records recorded at/after this date(time)
//   to      optional  only records recorded at/before this date(time)
//
// With neither from nor to: returns every record ever logged for the unit (the full table).
// With only from: returns everything from that date up to the latest ping.
// With only to: returns everything up to that date.
// Records are returned newest-first, same convention as data.php.

require_once __DIR__ . '/../simulation/lib/gps_db.php';

header('Content-Type: application/json');

function api_error(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function valid_date(string $value): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $value);
}

$serial = $_GET['serial'] ?? '';
if (!preg_match('/^d_\d+$/', $serial)) {
    api_error("serial is required and must look like d_XXXXXXXXXXXXXXXX");
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

if ($from !== null && !valid_date($from)) {
    api_error("from must look like YYYY-MM-DD or YYYY-MM-DD HH:MM:SS");
}
if ($to !== null && !valid_date($to)) {
    api_error("to must look like YYYY-MM-DD or YYYY-MM-DD HH:MM:SS");
}

$pdo = gps_db_connect();

$exists = $pdo->prepare('SHOW TABLES LIKE :serial');
$exists->execute(['serial' => $serial]);
if (!$exists->fetchColumn()) {
    api_error("unknown GPS unit: {$serial}", 404);
}

$where = [];
$params = [];
if ($from !== null) {
    $where[] = 'recorded_at >= :from';
    $params['from'] = $from;
}
if ($to !== null) {
    $where[] = 'recorded_at <= :to';
    $params['to'] = $to;
}

$sql = "SELECT * FROM `{$serial}`";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
