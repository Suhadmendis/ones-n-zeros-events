<?php

// Connection to the external onesnzer_gps MySQL server (separate from the
// app's main Supabase/Postgres database — this one holds live GPS data).

define('GPS_DB_HOST', '209.42.255.1');
define('GPS_DB_PORT', 3306);
define('GPS_DB_NAME', 'onesnzer_gps');
define('GPS_DB_USER', 'onesnzer_akila');
define('GPS_DB_PASS', 'SaGaRa4000');

function gps_db_connect(): PDO
{
    // One shared connection per request/process: the simulator calls this from
    // many helpers per tick, and its GET_LOCK/RELEASE_LOCK pair must run on the
    // same session.
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . GPS_DB_HOST . ';port=' . GPS_DB_PORT . ';dbname=' . GPS_DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, GPS_DB_USER, GPS_DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    return $pdo;
}
