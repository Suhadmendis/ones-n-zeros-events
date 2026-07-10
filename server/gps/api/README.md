# GPS Data API

Read-only JSON API over the GPS simulation database (`onesnzer_gps` MySQL, connected via
`server/gps/simulation/lib/gps_db.php`). Each GPS unit has its own telemetry table named
exactly after its serial number (e.g. `d_5794832657826592`); these endpoints are how the
rest of the system fetches that data.

Base path: `/server/gps/api/` (production: `https://transport.onesnzeros.net/server/gps/api/`).
All responses are JSON. Errors return `{"error": "<message>"}` with HTTP 400/404.

---

## GET `units.php` — list all GPS units

No parameters. Returns every unit's serial number (sorted). A serial number doubles as the
unit's table name in the GPS database.

```
GET /server/gps/api/units.php
```

```json
["d_1579382946037952", "d_2688493057148063", "d_5794832657826592"]
```

---

## GET `data.php` — fetch a unit's GPS records

Records are returned **newest first**.

| Param    | Required | Default | Notes |
|----------|----------|---------|-------|
| `serial` | yes      | —       | A serial number from `units.php` (format `d_<digits>`). Unknown unit → 404. |
| `limit`  | no       | `100`   | Number of records. Clamped to 1–1000. `limit=1` = the latest record only. |
| `type`   | no       | `all`   | `all` = every column. `location` = `latitude` + `longitude` + `speed` + `heading` + `route_progress_m`. |

### `type=all` — full records

```
GET /server/gps/api/data.php?serial=d_5794832657826592&limit=1
```

```json
[
  {
    "id": 113,
    "path_id": 3791,
    "route_progress_m": "23253.64",
    "latitude": "7.0556420",
    "longitude": "80.0083780",
    "speed": "57.42",
    "heading": "40.92",
    "engine_running": 1,
    "odometer_km": "23.27",
    "fuel_percentage": "98.67",
    "recorded_at": "2026-07-03 13:11:51"
  }
]
```

Field meanings:

| Field | Meaning |
|---|---|
| `path_id` | `gps_path.id` of the route point this record sits on (route points of the *current* trip; historical records may reference points of an already-replaced route) |
| `route_progress_m` | exact metres travelled along the current route |
| `speed` | km/h at this ping (0 while parked/arrived) |
| `heading` | compass bearing in degrees, 0–360 |
| `engine_running` | 1 moving / 0 parked or waiting at destination |
| `odometer_km` | cumulative distance for the unit, carries across trips |
| `fuel_percentage` | 0–100; refuelled during a destination stop when low |
| `recorded_at` | DB-server timestamp of the ping (~10 s apart while the simulator runs) |

### `type=location` — coordinates + speed + heading + route progress

```
GET /server/gps/api/data.php?serial=d_5794832657826592&limit=3&type=location
```

```json
[
  {"latitude": "7.0570170", "longitude": "80.0082330", "speed": "52.10", "heading": "38.40", "route_progress_m": "23301.90"},
  {"latitude": "7.0556420", "longitude": "80.0083780", "speed": "57.42", "heading": "40.92", "route_progress_m": "23253.64"},
  {"latitude": "7.0548290", "longitude": "80.0076680", "speed": "49.85", "heading": "35.10", "route_progress_m": "23207.11"}
]
```

### Errors

```json
{"error": "serial is required and must look like d_XXXXXXXXXXXXXXXX"}   // 400
{"error": "unknown GPS unit: d_9999999999999999"}                       // 404
{"error": "type must be 'all' or 'location'"}                           // 400
```

---

## GET `path.php` — a unit's current route geometry

Returns the road-network route (built from OSRM) for that unit's *current* trip, ordered
from route start to end. This is the actual road-snapped geometry the simulator drives
along — combine it with a ping's `route_progress_m` to find exactly where on the road the
vehicle currently is, which is far more accurate than trusting a single ping's raw
lat/lng/heading (those can wobble off the road or misjudge a turn at a curve).

| Param    | Required | Notes |
|----------|----------|-------|
| `serial` | yes      | A serial number from `units.php` (format `d_<digits>`). No route yet → 404. |

```
GET /server/gps/api/path.php?serial=d_5794832657826592
```

```json
[
  {"seq": 0, "latitude": "6.9389770", "longitude": "79.8541980", "cumulative_distance_m": "0.00"},
  {"seq": 1, "latitude": "6.9389760", "longitude": "79.8541740", "cumulative_distance_m": "2.65"},
  {"seq": 2, "latitude": "6.9389850", "longitude": "79.8541420", "cumulative_distance_m": "6.32"}
]
```

To get a road-snapped position + true road-tangent heading: find the two points whose
`cumulative_distance_m` bracket the current `route_progress_m`, interpolate the position
between them proportionally, and use the compass bearing between them as the heading.
The route is replaced (and `cumulative_distance_m` resets toward 0) whenever the unit
starts a new trip, so re-fetch if a ping's `route_progress_m` drops below what you last saw.

### Errors

```json
{"error": "serial is required and must look like d_XXXXXXXXXXXXXXXX"}   // 400
{"error": "no route found for GPS unit: d_9999999999999999"}            // 404
```

---

## GET `trip.php` — a unit's current trip info

Returns the unit's current trip (origin/destination names, destination waiting time, target
average speed). Unlike `data.php`/`path.php` this is static per trip — it only changes when
the unit is assigned a new trip.

| Param    | Required | Notes |
|----------|----------|-------|
| `serial` | yes      | A serial number from `units.php` (format `d_<digits>`). No trip yet → 404. |

```
GET /server/gps/api/trip.php?serial=d_5794832657826592
```

```json
{
  "from_location": "Colombo",
  "to_location": "Kandy",
  "waiting_time_minutes": "87",
  "average_speed_kmh": "61.45"
}
```

### Errors

```json
{"error": "serial is required and must look like d_XXXXXXXXXXXXXXXX"}   // 400
{"error": "no trip found for GPS unit: d_9999999999999999"}             // 404
```

---

## GET `range.php` — a unit's full record history, optionally date-filtered

Same columns as `data.php?type=all`, but scoped by date instead of `limit`. Records are
returned **newest first**.

| Param    | Required | Notes |
|----------|----------|-------|
| `serial` | yes      | A serial number from `units.php` (format `d_<digits>`). Unknown unit → 404. |
| `from`   | no       | `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS`. Only records recorded at/after this. |
| `to`     | no       | `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS`. Only records recorded at/before this. |

- Neither `from` nor `to` → the entire table for that unit.
- Only `from` → everything from that date up to the latest ping.
- Only `to` → everything up to that date.
- Both → everything in that window.

```
GET /server/gps/api/range.php?serial=d_5794832657826592&from=2026-07-01
```

### Errors

```json
{"error": "serial is required and must look like d_XXXXXXXXXXXXXXXX"}   // 400
{"error": "unknown GPS unit: d_9999999999999999"}                       // 404
{"error": "from must look like YYYY-MM-DD or YYYY-MM-DD HH:MM:SS"}      // 400
{"error": "to must look like YYYY-MM-DD or YYYY-MM-DD HH:MM:SS"}        // 400
```

---

## Common recipes

- **Live position of one vehicle:** `data.php?serial=<sn>&limit=1&type=location`
- **Live positions of the whole fleet:** call `units.php`, then `data.php?serial=<sn>&limit=1` per unit
- **Trail/breadcrumb for a map:** `data.php?serial=<sn>&limit=200&type=location` (points are ~10 s apart)
- **Accurate position + heading (road-snapped):** `data.php?...&type=location` for `route_progress_m`, plus `path.php?serial=<sn>` for the route geometry — see `path.php` above
- **Which vehicle owns a serial number:** join via Supabase `m_gps_devices.serial_number` → `t_vehicle_gps.gps_device_ref` → `m_vehicles` (that mapping lives in the ERP database, not here)
- **Trip details for the "more info" panel:** `trip.php?serial=<sn>` for origin/destination/waiting time/average speed
