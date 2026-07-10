<?php
// gps_path.php — assigns a device's current trip (gps_direction) and builds its
// road-network path (gps_path rows) between two named locations, using OSRM route geometry.

require_once __DIR__ . '/gps_db.php';
require_once __DIR__ . '/osrm_client.php';

function lookupGpsLocation(PDO $pdo, string $name): array
{
    $stmt = $pdo->prepare('SELECT latitude, longitude FROM gps_locations WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException("Unknown location: {$name}");
    }

    return $row;
}

// Returns a random location name from gps_locations, guaranteed not to be $name.
function getRandomLocation(string $name): string
{
    $pdo = gps_db_connect();
    $stmt = $pdo->prepare('SELECT name FROM gps_locations WHERE name != :name ORDER BY RAND() LIMIT 1');
    $stmt->execute(['name' => $name]);

    return $stmt->fetchColumn();
}

// Natural-looking waiting time in minutes: usually a shortish break, sometimes
// a long rest. Non-round values.
function randomWaitingMinutes(): int
{
    return random_int(1, 10) === 1 ? random_int(200, 360) : random_int(35, 200);
}

// Natural-looking per-route average speed in km/h (non-round).
function randomAverageSpeedKmh(): float
{
    return random_int(3800, 7200) / 100;
}

// Wipes any existing gps_direction and gps_path rows for $serialNumber, so a
// new trip can be assigned to that device from a clean slate.
function deleteGpsTripData(string $serialNumber): void
{
    $pdo = gps_db_connect();
    $pdo->prepare('DELETE FROM gps_direction WHERE serial_number = :serial')->execute(['serial' => $serialNumber]);
    $pdo->prepare('DELETE FROM gps_path WHERE serial_number = :serial')->execute(['serial' => $serialNumber]);
}

// Replaces any existing gps_path rows for $serialNumber with the road-network
// coordinates between $fromLocation and $toLocation (both must exist in
// gps_locations by name). Returns the number of points inserted.
function insertGpsPath(string $serialNumber, string $fromLocation, string $toLocation): int
{
    $pdo = gps_db_connect();

    $from = lookupGpsLocation($pdo, $fromLocation);
    $to = lookupGpsLocation($pdo, $toLocation);

    $coordinates = "{$from['longitude']},{$from['latitude']};{$to['longitude']},{$to['latitude']}";
    $path = "/route/v1/driving/{$coordinates}?overview=full&geometries=geojson&steps=true&annotations=true";
    $result = osrm_request($path);

    $points = $result['routes'][0]['geometry']['coordinates'] ?? [];
    if (empty($points)) {
        throw new RuntimeException("OSRM returned no route for {$fromLocation} -> {$toLocation}");
    }

    $pdo->prepare('DELETE FROM gps_path WHERE serial_number = :serial')->execute(['serial' => $serialNumber]);

    return insertGpsPathPoints($pdo, $serialNumber, $points);
}

// Batch-inserts OSRM geometry points ([lng, lat] pairs, in route order) into
// gps_path, assigning a 0-based seq and the cumulative haversine distance from
// the route start to each point. Returns the number of points inserted.
function insertGpsPathPoints(PDO $pdo, string $serialNumber, array $points): int
{
    $batchSize = 100;
    $inserted = 0;
    $seq = 0;
    $cum = 0.0;
    $prev = null;

    foreach (array_chunk($points, $batchSize) as $batch) {
        $placeholders = [];
        $params = [];

        foreach ($batch as $i => [$lng, $lat]) {
            if ($prev !== null) {
                $cum += haversineMeters($prev[1], $prev[0], (float) $lat, (float) $lng);
            }
            $prev = [(float) $lng, (float) $lat];

            $placeholders[] = "(:serial{$i}, :seq{$i}, :lat{$i}, :lng{$i}, :cum{$i})";
            $params["serial{$i}"] = $serialNumber;
            $params["seq{$i}"] = $seq++;
            $params["lat{$i}"] = $lat;
            $params["lng{$i}"] = $lng;
            $params["cum{$i}"] = round($cum, 2);
        }

        $sql = 'INSERT INTO gps_path (serial_number, seq, latitude, longitude, cumulative_distance_m) VALUES '
            . implode(',', $placeholders);
        $pdo->prepare($sql)->execute($params);

        $inserted += count($batch);
    }

    return $inserted;
}

function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

    return 2 * 6371000.0 * asin(sqrt($a));
}

// Assigns a fresh trip to a device: deletes its old direction/path rows,
// writes the new gps_direction row (with waiting time), then builds its
// gps_path. Returns the number of path points inserted.
function setGpsTrip(string $serialNumber, string $fromLocation, string $toLocation, int $waitingTimeMinutes, ?float $averageSpeedKmh = null): int
{
    deleteGpsTripData($serialNumber);

    $pdo = gps_db_connect();
    $from = lookupGpsLocation($pdo, $fromLocation);
    $to = lookupGpsLocation($pdo, $toLocation);

    $pdo->prepare(
        'INSERT INTO gps_direction (serial_number, from_location, from_latitude, from_longitude,
            to_location, to_latitude, to_longitude, waiting_time_minutes, average_speed_kmh)
         VALUES (:serial, :from_loc, :from_lat, :from_lng, :to_loc, :to_lat, :to_lng, :waiting, :speed)'
    )->execute([
        'serial'   => $serialNumber,
        'from_loc' => $fromLocation,
        'from_lat' => $from['latitude'],
        'from_lng' => $from['longitude'],
        'to_loc'   => $toLocation,
        'to_lat'   => $to['latitude'],
        'to_lng'   => $to['longitude'],
        'waiting'  => $waitingTimeMinutes,
        'speed'    => $averageSpeedKmh ?? randomAverageSpeedKmh(),
    ]);

    return insertGpsPath($serialNumber, $fromLocation, $toLocation);
}

// Inserts a device's first telemetry row at the starting point of its current
// gps_path (the lowest id for that serial_number), setting path_id to that
// gps_path row's id. Throws if the device has no gps_path rows yet.
function initializeDeviceTelemetry(string $serialNumber): void
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $serialNumber)) {
        throw new RuntimeException("Unsafe serial number / table name: {$serialNumber}");
    }

    $pdo = gps_db_connect();

    $stmt = $pdo->prepare(
        'SELECT id, latitude, longitude FROM gps_path WHERE serial_number = :serial ORDER BY seq ASC, id ASC LIMIT 1'
    );
    $stmt->execute(['serial' => $serialNumber]);
    $start = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$start) {
        throw new RuntimeException("No gps_path rows found for {$serialNumber}");
    }

    $pdo->prepare(
        "INSERT INTO `{$serialNumber}` (path_id, route_progress_m, latitude, longitude, speed, heading, engine_running, odometer_km, fuel_percentage)
         VALUES (:path_id, 0, :lat, :lng, 0, 0, 0, 0, 100)"
    )->execute([
        'path_id' => $start['id'],
        'lat'     => $start['latitude'],
        'lng'     => $start['longitude'],
    ]);
}

// Runs initializeDeviceTelemetry() for every device table (d_<serial_number>)
// found in the database. Returns the list of serial numbers processed.
function initializeAllDeviceTelemetry(): array
{
    $pdo = gps_db_connect();
    $tables = $pdo->query("SHOW TABLES LIKE 'd\\_%'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $serialNumber) {
        initializeDeviceTelemetry($serialNumber);
    }

    return $tables;
}
