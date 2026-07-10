# GPS Driving Simulation Function --- Implementation Brief

## Goal

Create a PHP function that simulates one GPS unit moving along its
current OSRM route. The simulator runs approximately every 10 seconds.

For each run, the function must:

1.  Find the latest GPS record for the specific serial number.
2.  Read the last path `coordinate_id` or route sequence reached.
3.  Read the current Direction row for that serial number.
4.  Get `average_speed_kmh` from the **Direction table**.
5.  Calculate how far the vehicle should travel during the actual
    elapsed time.
6.  Move forward through the Path by **distance**, not by a fixed number
    of coordinates.
7.  Calculate the correct new latitude and longitude.
8.  Insert the new simulated GPS record.
9.  Handle arrival at the end of the route.

## Critical Rule

Do not simulate movement like this:

``` text
Every 10 seconds:
current_coordinate_id + 1
```

Do not simulate speed like this:

``` text
Low speed  -> next coordinate
High speed -> skip 3 coordinates
```

OSRM coordinates are not evenly spaced. For example:

``` text
Coordinate 100 -> 101 = 2 m
Coordinate 101 -> 102 = 8 m
Coordinate 102 -> 103 = 45 m
Coordinate 103 -> 104 = 3 m
```

Therefore, the correct model is:

``` text
Speed + elapsed time
        |
        v
Distance the vehicle should travel
        |
        v
Walk forward through route segments
        |
        v
Find the exact resulting position
```

## Main Calculation

``` text
speed_mps = average_speed_kmh / 3.6

travel_distance_m = speed_mps * elapsed_seconds
```

For a normal 10-second tick:

``` text
travel_distance_m = (average_speed_kmh / 3.6) * 10
```

Examples:

``` text
36 km/h -> 10 m/s -> 100 m in 10 seconds
72 km/h -> 20 m/s -> 200 m in 10 seconds
85 km/h -> 23.6111 m/s -> 236.111 m in 10 seconds
```

At 85 km/h, the function must move about 236.11 metres along the route.
That may pass 1 coordinate, 5 coordinates, 20 coordinates, or more. The
number of coordinates is not the movement unit; road distance is.

## Expected Data

The agent must inspect the real schema and adapt the names instead of
assuming these exact columns.

### Direction table

Conceptually:

``` text
serial_number
from_location
from_latitude
from_longitude
to_location
to_latitude
to_longitude
average_speed_kmh
waiting_time_minutes
```

The average speed belongs in the **Direction table**, not the GPS
history table.

### Path table

Conceptually:

``` text
id / coordinate_id
serial_number
sequence_number
latitude
longitude
```

The Path must have a reliable route order. If `coordinate_id` is a
global auto-increment ID, do not assume `coordinate_id + 1` is the next
point unless that is guaranteed. Prefer an explicit `sequence_number`
per route.

### GPS history table for each serial number

Conceptually:

``` text
id
coordinate_id / path_sequence
latitude
longitude
speed_kmh
route_progress_m
recorded_at
```

The function must first read the latest GPS record for the specific
serial number.

## Required Algorithm

### 1. Load current Direction

Find the Direction row for the serial number.

Read:

``` text
average_speed_kmh
FROM location
TO location
waiting state/information if applicable
```

If no Direction exists, return a controlled result. Do not crash and do
not insert a fake point.

### 2. Find latest GPS record

Query the GPS history for the serial number:

``` text
ORDER BY recorded_at DESC, id DESC
LIMIT 1
```

Read:

``` text
last coordinate_id or path sequence
last latitude
last longitude
last recorded_at
exact route progress
```

The agent must inspect how serial-number-specific GPS tables are named
and access them safely. Do not build unsafe dynamic SQL from untrusted
serial numbers.

### 3. Calculate actual elapsed time

The cron is intended to run every 10 seconds, but do not blindly assume
exactly 10 seconds passed.

Preferred:

``` text
elapsed_seconds = current_time - last_recorded_at
```

Example:

``` text
Last record: 10:00:00
Current run: 10:00:14
elapsed_seconds = 14
```

This lets the simulator catch up after a delayed cron run. Add sensible
validation or a maximum elapsed-time cap if a large delay would create
unrealistic movement.

### 4. Calculate travel distance

Example:

``` text
average_speed_kmh = 85
elapsed_seconds = 10

speed_mps = 85 / 3.6
          = 23.6111

travel_distance_m = 23.6111 * 10
                  = 236.111 m
```

### 5. Start from the last reached Path position

Use the last GPS record's `coordinate_id`, `path_sequence`, or exact
route progress.

Example:

``` text
Current sequence = 500

Load:
500
501
502
503
504
...
```

Always filter by the same serial number/current route.

### 6. Move through route segments by distance

For each consecutive pair, get or calculate the segment distance.

Example:

``` text
500 -> 501 = 12 m
501 -> 502 = 40 m
502 -> 503 = 75 m
503 -> 504 = 180 m
```

For a required movement of 236.11 m:

``` text
236.11 - 12 = 224.11 m remaining
224.11 - 40 = 184.11 m remaining
184.11 - 75 = 109.11 m remaining
```

The next segment is 180 m, but only 109.11 m remains. Therefore the new
GPS position is somewhere between points 503 and 504.

### 7. Interpolate the final position

``` text
segment_length = 180 m
remaining_movement = 109.11 m

fraction = 109.11 / 180
         = 0.6062
```

The new GPS position is approximately 60.62% of the way from point 503
to point 504.

The agent must implement a geographically appropriate interpolation
method. For short route segments, simple coordinate interpolation may be
acceptable, but verify it. A geodesic destination-point method may be
more accurate.

## Critical State Problem: Preserve Partial-Segment Progress

If the new position ends between two stored Path coordinates, storing
only `coordinate_id = 503` is not enough. Otherwise, the next tick may
start again from the beginning of segment 503 -\> 504 and repeat
distance.

The implementation must preserve exact progress.

Preferred options:

``` text
Option A:
path_sequence = 503
segment_progress_m = 109.11
```

or:

``` text
Option B:
route_progress_m = total cumulative distance travelled along route
```

The agent must not ignore this issue.

## Recommended Design: Cumulative Route Distance

Consider storing these values for Path points:

``` text
sequence_number
latitude
longitude
segment_distance_m
cumulative_distance_m
```

Example:

``` text
Seq 500 | segment 0 m   | cumulative 45000 m
Seq 501 | segment 12 m  | cumulative 45012 m
Seq 502 | segment 40 m  | cumulative 45052 m
Seq 503 | segment 75 m  | cumulative 45127 m
Seq 504 | segment 180 m | cumulative 45307 m
```

Then each tick becomes:

``` text
new_route_progress_m
=
old_route_progress_m + travel_distance_m
```

Example:

``` text
old progress = 45000 m
movement = 236.11 m
new progress = 45236.11 m
```

Find the two Path points surrounding 45236.11 m and interpolate between
them.

The agent should evaluate whether this is more accurate and efficient
than repeatedly summing route segments every 10 seconds.

## Insert the New GPS Record

Insert the calculated position with the fields required by the real
project.

Conceptually:

``` text
latitude
longitude
coordinate_id / path_sequence
segment_progress_m or route_progress_m
speed_kmh
recorded_at
```

## Detect Route Completion

Check:

``` text
new_route_progress_m >= total_route_distance_m
```

If true:

1.  Set the GPS position exactly to the final route coordinate.
2.  Insert the final arrival GPS record if required.
3.  Trigger the existing arrival/waiting logic.
4.  Keep the GPS at the destination while waiting.
5.  After waiting, use the existing route regeneration process:
    -   old TO becomes new FROM;
    -   choose a different random TO;
    -   get a new OSRM route;
    -   safely replace Direction and Path data;
    -   begin travelling again.

Do not overshoot beyond the destination.

## Pseudocode

``` text
FUNCTION simulateGpsMovement(serial_number):

    direction = get current direction for serial_number

    IF direction does not exist:
        RETURN no_direction

    IF vehicle is waiting:
        RETURN waiting

    last_gps = get latest GPS record for serial_number

    IF last_gps does not exist:
        initialize at first Path point
        RETURN initialized

    elapsed_seconds = now - last_gps.recorded_at

    validate/cap elapsed_seconds

    speed_kmh = direction.average_speed_kmh

    speed_mps = speed_kmh / 3.6

    travel_distance_m = speed_mps * elapsed_seconds

    current_progress_m = exact saved route progress

    new_progress_m = current_progress_m + travel_distance_m

    total_route_distance_m = get total Path distance

    IF new_progress_m >= total_route_distance_m:

        final_point = get final Path point

        insert final GPS record at final_point

        trigger arrival and waiting logic

        RETURN arrived

    ELSE:

        find Path segment containing new_progress_m

        calculate distance into that segment

        interpolate exact latitude/longitude

        insert GPS record with:
            calculated latitude
            calculated longitude
            relevant Path sequence / coordinate id
            exact route progress
            speed
            timestamp

        RETURN moved
```

## Worked Example: 85 km/h

``` text
85 / 3.6 = 23.6111 m/s

23.6111 * 10 = 236.111 m
```

Assume:

``` text
500 -> 501 = 12 m
501 -> 502 = 40 m
502 -> 503 = 75 m
503 -> 504 = 180 m
```

Movement:

``` text
236.111 - 12 = 224.111
224.111 - 40 = 184.111
184.111 - 75 = 109.111
```

The vehicle moves 109.111 m into segment 503 -\> 504.

``` text
fraction = 109.111 / 180
         = approximately 0.6062
```

Result:

``` text
New GPS position = approximately 60.62%
between Path point 503 and Path point 504.
```

Preserve that partial progress for the next tick.

## Agent Verification Requirements

Before finalizing the function, verify all of these:

1.  Do not assume route coordinates are equally spaced.
2.  Do not use fixed coordinate skipping as the movement model.
3.  Calculate movement from speed and actual elapsed time.
4.  Measure distance along consecutive Path points.
5.  Handle movement ending inside a segment.
6.  Preserve partial-segment or cumulative-route progress.
7.  Ensure Path points have a reliable sequence.
8.  Do not assume global auto-increment IDs are consecutive per serial
    number.
9.  Prevent overshooting the destination.
10. Handle delayed cron execution.
11. Handle duplicate or overlapping cron execution.
12. Prevent two processes from moving the same GPS unit at the same
    time.
13. Use a transaction and/or locking where appropriate.
14. Avoid duplicate movement records for the same simulation tick.
15. Verify calculated Path distance against OSRM route distance.
16. Test speeds 0, 20, 50, 85, and 100 km/h.
17. Test very short and very long Path segments.
18. Test one tick crossing many coordinates.
19. Test one tick ending inside a segment.
20. Test the final tick reaching the destination.
21. Check performance for all 10 GPS units.

## Use OSRM Annotation Distances If Available

The existing OSRM response may already contain:

``` text
routes[0].legs[0].annotation.distance
```

The agent should investigate using those per-segment distances when they
align exactly with the stored geometry.

Verify:

``` text
geometry point count
annotation distance count
segment-to-coordinate alignment
```

Normally:

``` text
N geometry points = N - 1 route segments
```

Do not assume alignment without checking the actual response and current
Path insertion logic.

## Concurrency Requirement

Because cron runs may overlap, protect against this problem:

``` text
Run A reads latest GPS record
Run B reads the same latest GPS record
Both calculate movement
Both insert a new GPS point
```

Use a suitable mechanism for the current project, such as:

``` text
database row lock
named lock
simulation lock table
unique tick key
transaction strategy
```

## Final Instruction to the Agent

Implement this as a reusable PHP function for **one serial number
first**. Then create a runner that loops through active simulated GPS
units and calls the function for each one.

Before writing final code:

1.  Inspect the actual database schema.
2.  Inspect the existing Path insertion code.
3.  Inspect how per-serial-number GPS tables are named.
4.  Verify whether OSRM annotation distances are stored or available.
5.  Decide how exact route progress will be persisted.
6.  Check cron overlap and concurrency risks.
7.  Improve this algorithm if a more accurate or efficient approach fits
    the current project.

The key principle is:

``` text
DO NOT MOVE BY NUMBER OF COORDINATES.

MOVE BY:

AVERAGE SPEED * ACTUAL ELAPSED TIME
=
DISTANCE ALONG THE ROUTE
```
