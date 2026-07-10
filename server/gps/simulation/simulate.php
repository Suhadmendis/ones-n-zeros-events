<?php
// simulate.php — cron entry point. cPanel calls this once per minute; a single
// call to runGpsSimulation() does everything: it runs TICKS_PER_RUN passes
// spaced TICK_INTERVAL_SECONDS apart, and each pass advances every GPS unit
// (all d_<serial> tables) once. If a run ever overlaps the next one, the
// per-unit GET_LOCK in simulateGpsMovement() prevents double movement.

require_once __DIR__ . '/lib/simulate_movement.php';

const TICKS_PER_RUN = 6;
const TICK_INTERVAL_SECONDS = 10;
const MAX_RUN_SECONDS = 55; // stop early rather than bleed into the next cron minute

// Runs one tick for every GPS unit (each d_<serial> table found in the DB).
// Returns one result array per unit; a failing unit never halts the others.
function simulateAllGpsUnits(): array
{
    $pdo = gps_db_connect();
    $serials = $pdo->query("SHOW TABLES LIKE 'd\\_%'")->fetchAll(PDO::FETCH_COLUMN);
    sort($serials);

    $results = [];

    foreach ($serials as $serial) {
        try {
            $results[] = simulateGpsMovement($serial);
        } catch (Throwable $e) {
            $results[] = ['status' => 'error', 'serial' => $serial, 'message' => $e->getMessage()];
        }
    }

    return $results;
}

// The one function the cron invocation drives: a full minute's worth of
// simulation ticks across all units, paced ~TICK_INTERVAL_SECONDS apart.
function runGpsSimulation(): void
{
    $start = time();

    for ($tick = 1; $tick <= TICKS_PER_RUN; $tick++) {
        foreach (simulateAllGpsUnits() as $r) {
            $detail = '';
            if (isset($r['lat'])) {
                $detail = sprintf('lat=%.6f lng=%.6f', $r['lat'], $r['lng']);
            }
            if (isset($r['progress_m'])) {
                $detail .= sprintf(' v=%.1fkm/h progress=%.0f/%.0fm', $r['speed'], $r['progress_m'], $r['total_m']);
            }
            if (isset($r['wait_remaining_s'])) {
                $detail = 'wait remaining ' . $r['wait_remaining_s'] . 's';
            }
            if ($r['status'] === 'new_leg') {
                $detail .= " {$r['from']} -> {$r['to']}";
            }
            if ($r['status'] === 'error') {
                $detail = $r['message'];
            }

            printf("[%s] tick %d/%d %-20s %-11s %s\n", date('H:i:s'), $tick, TICKS_PER_RUN, $r['serial'], strtoupper($r['status']), $detail);
        }

        if ($tick === TICKS_PER_RUN) {
            break;
        }

        // Pace to the next slot; a slow pass just shortens the pause.
        $sleep = ($start + $tick * TICK_INTERVAL_SECONDS) - time();
        if ($sleep > 0) {
            sleep($sleep);
        }

        if (time() - $start >= MAX_RUN_SECONDS) {
            break;
        }
    }
}

runGpsSimulation();
