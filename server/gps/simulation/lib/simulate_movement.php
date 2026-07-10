<?php
// simulate_movement.php — drives one GPS unit along its stored OSRM route.
//
// simulateGpsMovement() only ever inserts the next record into the unit's
// d_<serial> table; scheduling belongs to an external cron (interval is the
// operator's concern). Movement is distance-based: actual elapsed time since
// the unit's last record × a momentum-smoothed speed, walked forward along
// gps_path by cumulative distance. Every inserted coordinate is an exact
// gps_path record, referenced via path_id.

require_once __DIR__ . '/gps_db.php';
require_once __DIR__ . '/gps_path.php';

const MAX_ELAPSED_SECONDS = 60;       // cap so a delayed cron run never teleports the vehicle
const MIN_SPEED_KMH = 3.0;
const MAX_TICK_SPEED_DELTA_KMH = 10.0; // consecutive records never differ by more than this
const FUEL_PERCENT_PER_KM = 0.05;
const MIN_FUEL_PERCENT = 5.0;
const REFUEL_BELOW_PERCENT = 20.0;    // tank refilled during the destination stop if below this

function simulateGpsMovement(string $serialNumber): array
{
    if (!preg_match('/^d_\d+$/', $serialNumber)) {
        throw new RuntimeException("Unsafe serial number: {$serialNumber}");
    }

    $pdo = gps_db_connect();

    // Overlapping cron runs must never move the same unit twice.
    $lock = $pdo->query('SELECT GET_LOCK(' . $pdo->quote("gps_sim_{$serialNumber}") . ', 0)')->fetchColumn();
    if ((int) $lock !== 1) {
        return ['status' => 'skipped', 'serial' => $serialNumber];
    }

    try {
        return simulateGpsMovementLocked($pdo, $serialNumber);
    } finally {
        $pdo->query('SELECT RELEASE_LOCK(' . $pdo->quote("gps_sim_{$serialNumber}") . ')');
    }
}

function simulateGpsMovementLocked(PDO $pdo, string $serialNumber): array
{
    $stmt = $pdo->prepare('SELECT * FROM gps_direction WHERE serial_number = :serial LIMIT 1');
    $stmt->execute(['serial' => $serialNumber]);
    $direction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$direction) {
        return ['status' => 'no_direction', 'serial' => $serialNumber];
    }

    // Elapsed time is computed on the DB clock (recorded_at is stamped by the
    // DB server, whose timezone differs from this machine's).
    $last = $pdo->query(
        "SELECT *, TIMESTAMPDIFF(SECOND, recorded_at, NOW()) AS elapsed_seconds
         FROM `{$serialNumber}` ORDER BY id DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    if (!$last) {
        initializeDeviceTelemetry($serialNumber);
        return ['status' => 'initialized', 'serial' => $serialNumber];
    }

    $final = fetchPathEndpoint($pdo, $serialNumber, 'DESC');

    // Arrived: the last record sits on the route's final point. Nothing is
    // inserted while waiting, so recorded_at stays frozen at the arrival time
    // and doubles as the wait clock.
    if ((int) $last['path_id'] === (int) $final['id']) {
        $waitSeconds = (int) $direction['waiting_time_minutes'] * 60;

        if ((int) $last['elapsed_seconds'] < $waitSeconds) {
            return [
                'status' => 'waiting',
                'serial' => $serialNumber,
                'wait_remaining_s' => $waitSeconds - (int) $last['elapsed_seconds'],
            ];
        }

        return startNextLeg($pdo, $serialNumber, $direction, $last);
    }

    $elapsed = min((int) $last['elapsed_seconds'], MAX_ELAPSED_SECONDS);
    if ($elapsed <= 0) {
        // Second call within the same second — never double-move a tick.
        return ['status' => 'too_soon', 'serial' => $serialNumber];
    }

    $speed = momentumSpeed((float) $last['speed'], (float) $direction['average_speed_kmh']);
    $travelM = ($speed / 3.6) * $elapsed;
    $totalM = (float) $final['cumulative_distance_m'];
    $newProgress = min((float) $last['route_progress_m'] + $travelM, $totalM);

    // Snap to the furthest path point actually reached — the inserted
    // coordinate must be a real gps_path record. Leftover sub-segment distance
    // stays in route_progress_m and carries into the next tick.
    $snap = $pdo->prepare(
        'SELECT id, seq, latitude, longitude FROM gps_path
         WHERE serial_number = :serial AND cumulative_distance_m <= :progress
         ORDER BY seq DESC LIMIT 1'
    );
    $snap->execute(['serial' => $serialNumber, 'progress' => $newProgress]);
    $point = $snap->fetch(PDO::FETCH_ASSOC);

    $arrived = (int) $point['id'] === (int) $final['id'];

    $creditedKm = ($newProgress - (float) $last['route_progress_m']) / 1000;
    $heading = ((float) $point['latitude'] === (float) $last['latitude'] && (float) $point['longitude'] === (float) $last['longitude'])
        ? (float) $last['heading']
        : bearingDegrees((float) $last['latitude'], (float) $last['longitude'], (float) $point['latitude'], (float) $point['longitude']);

    $pdo->prepare(
        "INSERT INTO `{$serialNumber}` (path_id, route_progress_m, latitude, longitude, speed, heading, engine_running, odometer_km, fuel_percentage)
         VALUES (:path_id, :progress, :lat, :lng, :speed, :heading, :engine, :odo, :fuel)"
    )->execute([
        'path_id'  => $point['id'],
        'progress' => round($newProgress, 2),
        'lat'      => $point['latitude'],
        'lng'      => $point['longitude'],
        'speed'    => $arrived ? 0 : $speed,
        'heading'  => $heading,
        'engine'   => $arrived ? 0 : 1,
        'odo'      => round((float) $last['odometer_km'] + $creditedKm, 2),
        'fuel'     => round(max(MIN_FUEL_PERCENT, (float) $last['fuel_percentage'] - $creditedKm * FUEL_PERCENT_PER_KM), 2),
    ]);

    return [
        'status'     => $arrived ? 'arrived' : 'moved',
        'serial'     => $serialNumber,
        'lat'        => (float) $point['latitude'],
        'lng'        => (float) $point['longitude'],
        'speed'      => $arrived ? 0.0 : $speed,
        'progress_m' => round($newProgress, 2),
        'total_m'    => $totalM,
    ];
}

// Waiting time is over: old TO becomes the new FROM, a different random TO is
// chosen, and the existing trip machinery (setGpsTrip) replaces the direction
// row and path. The odometer/fuel carry across legs via the leg-start record.
function startNextLeg(PDO $pdo, string $serialNumber, array $direction, array $last): array
{
    $newFrom = $direction['to_location'];
    $newTo = getRandomLocation($newFrom);

    setGpsTrip($serialNumber, $newFrom, $newTo, randomWaitingMinutes());

    $start = fetchPathEndpoint($pdo, $serialNumber, 'ASC');

    $fuel = (float) $last['fuel_percentage'];
    if ($fuel < REFUEL_BELOW_PERCENT) {
        $fuel = 100.0; // refuelled during the stop
    }

    $pdo->prepare(
        "INSERT INTO `{$serialNumber}` (path_id, route_progress_m, latitude, longitude, speed, heading, engine_running, odometer_km, fuel_percentage)
         VALUES (:path_id, 0, :lat, :lng, 0, 0, 1, :odo, :fuel)"
    )->execute([
        'path_id' => $start['id'],
        'lat'     => $start['latitude'],
        'lng'     => $start['longitude'],
        'odo'     => $last['odometer_km'],
        'fuel'    => $fuel,
    ]);

    return [
        'status' => 'new_leg',
        'serial' => $serialNumber,
        'from'   => $newFrom,
        'to'     => $newTo,
        'lat'    => (float) $start['latitude'],
        'lng'    => (float) $start['longitude'],
    ];
}

function fetchPathEndpoint(PDO $pdo, string $serialNumber, string $order): array
{
    $order = $order === 'ASC' ? 'ASC' : 'DESC';
    $stmt = $pdo->prepare(
        "SELECT id, seq, latitude, longitude, cumulative_distance_m FROM gps_path
         WHERE serial_number = :serial ORDER BY seq {$order} LIMIT 1"
    );
    $stmt->execute(['serial' => $serialNumber]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException("No gps_path rows found for {$serialNumber}");
    }

    return $row;
}

// Mean-reverting random walk around the route average: small drift + bounded
// noise, occasional surges (clear road) and slowdowns (junctions/traffic).
// Hard-capped so consecutive records never differ by more than
// MAX_TICK_SPEED_DELTA_KMH — a vehicle has momentum, it never jumps 40->80.
function momentumSpeed(float $lastSpeed, float $avg): float
{
    $drift = ($avg - $lastSpeed) * 0.15;
    $noise = random_int(-350, 350) / 100;
    $speed = $lastSpeed + $drift + $noise;

    $roll = random_int(1, 100);
    if ($roll <= 5) {
        $speed += random_int(300, 700) / 100;
    } elseif ($roll <= 15) {
        $speed -= random_int(300, 800) / 100;
    }

    $speed = max($lastSpeed - MAX_TICK_SPEED_DELTA_KMH, min($lastSpeed + MAX_TICK_SPEED_DELTA_KMH, $speed));
    $speed = max(MIN_SPEED_KMH, min($speed, $avg * 1.8));

    return round($speed, 2);
}

// Initial great-circle bearing from point 1 to point 2, degrees 0-360.
function bearingDegrees(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $dLng = deg2rad($lng2 - $lng1);

    $y = sin($dLng) * cos($phi2);
    $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($dLng);

    return round(fmod(rad2deg(atan2($y, $x)) + 360, 360), 2);
}
