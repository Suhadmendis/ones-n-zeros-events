<?php

// One-off maintenance script: re-geocodes every row in gps_locations against
// Nominatim and updates latitude/longitude in place. Run from the CLI:
//   php refresh_coordinates.php
// Respects Nominatim's usage policy (descriptive User-Agent, max 1 req/sec).

function read_database_url(): string
{
    $envPath = __DIR__ . '/../../../../.env';
    foreach (file($envPath) as $line) {
        if (str_starts_with($line, 'DATABASE_URL=')) {
            return trim(substr($line, strlen('DATABASE_URL=')));
        }
    }
    fwrite(STDERR, "DATABASE_URL not found in .env\n");
    exit(1);
}

$databaseUrl = read_database_url();

$parts = parse_url($databaseUrl);
$pdo = new PDO(
    "pgsql:host={$parts['host']};port={$parts['port']};dbname=" . ltrim($parts['path'], '/'),
    $parts['user'],
    $parts['pass']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Names that match their own district (Matale, Kurunegala, Ratnapura, ...)
// return the district *boundary* as the top hit, and compound names
// (Dehiwala-Mount Lavinia, Panadura, ...) can rank an unrelated shop/POI
// address above the town itself. Ask for several candidates and only accept
// an actual settlement point, or failing that an administrative boundary —
// never a POI. Returns null (leave the row untouched) if nothing suitable
// turns up, rather than write a wrong point.
function geocode(string $place): ?array
{
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q'      => $place,
        'format' => 'json',
        'limit'  => 10,
    ]);

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: OnesNZerosERP-GPSPlayground/1.0 (internal tool)\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return null;
    }

    $results = json_decode($json, true);
    if (empty($results)) {
        return null;
    }

    $settlementTypes = ['city', 'town', 'village', 'suburb', 'municipality', 'hamlet'];

    foreach ($results as $result) {
        if ($result['class'] === 'place' && in_array($result['type'], $settlementTypes, true)) {
            return $result;
        }
    }

    foreach ($results as $result) {
        if ($result['class'] === 'boundary' && $result['type'] === 'administrative') {
            return $result;
        }
    }

    return null;
}

$rows = $pdo->query('SELECT id, name FROM gps_locations ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$update = $pdo->prepare('UPDATE gps_locations SET latitude = :lat, longitude = :lng WHERE id = :id');

foreach ($rows as $row) {
    $result = geocode($row['name'] . ', Sri Lanka');

    if ($result === null) {
        echo "SKIP  {$row['name']} (no match)\n";
    } else {
        $update->execute([
            'lat' => $result['lat'],
            'lng' => $result['lon'],
            'id'  => $row['id'],
        ]);
        echo "OK    {$row['name']} -> {$result['lat']}, {$result['lon']} ({$result['type']})\n";
    }

    sleep(1); // Nominatim: max 1 request per second
}
