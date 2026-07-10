<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'snapshot';
$date   = $_GET['date']   ?? date('Y-m-d');

if ($action === 'snapshot') {
    $trips    = supabase_get("/rest/v1/m_trips?select=id,ref,amount,driver_salary,cleaner_salary,from_loc,to_loc,run_no,vehicle_ref,driver_ref&date=eq.{$date}");
    $fuel     = supabase_get("/rest/v1/m_fuel_expenses?select=total&date=eq.{$date}");
    $ve       = supabase_get("/rest/v1/m_vehicle_expenses?select=amount&date=eq.{$date}");
    $ge       = supabase_get("/rest/v1/m_general_expenses?select=amount&date=eq.{$date}");

    // Fetch vehicles and drivers for join
    $vehicles = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number");
    $drivers  = supabase_get("/rest/v1/m_drivers?select=ref,name");

    $veh_map = [];
    foreach ($vehicles as $v) $veh_map[$v['ref']] = $v;
    $drv_map = [];
    foreach ($drivers as $d) $drv_map[$d['ref']] = $d;

    $revenue       = array_sum(array_column($trips, 'amount'));
    $driver_p      = array_sum(array_column($trips, 'driver_salary'));
    $cleaner_p     = array_sum(array_column($trips, 'cleaner_salary'));
    $fuel_cost     = array_sum(array_column($fuel, 'total'));
    $vehicle_exp   = array_sum(array_column($ve, 'amount'));
    $general_exp   = array_sum(array_column($ge, 'amount'));
    $net_position  = $revenue - $fuel_cost - $vehicle_exp - $general_exp;

    $trip_list = array_map(function($t) use ($veh_map, $drv_map) {
        $veh = $veh_map[$t['vehicle_ref']] ?? null;
        $drv = $drv_map[$t['driver_ref']] ?? null;
        return [
            'ref'         => $t['ref'] ?? '',
            'vehicle_ref' => $veh['ref'] ?? '',
            'plate'       => $veh['plate_number'] ?? '',
            'driver'      => $drv['name'] ?? '',
            'from_loc'    => $t['from_loc'] ?? '',
            'to_loc'      => $t['to_loc'] ?? '',
            'run_no'      => $t['run_no'] ?? '',
            'amount'         => floatval($t['amount'] ?? 0),
            'driver_salary'  => floatval($t['driver_salary'] ?? 0),
            'cleaner_salary' => floatval($t['cleaner_salary'] ?? 0),
        ];
    }, $trips);

    echo json_encode([
        'trip_count'   => count($trips),
        'revenue'      => $revenue,
        'fuel_cost'    => $fuel_cost,
        'vehicle_exp'  => $vehicle_exp,
        'general_exp'  => $general_exp,
        'driver_p'     => $driver_p,
        'cleaner_p'    => $cleaner_p,
        'net_position' => $net_position,
        'trip_list'    => $trip_list,
    ]);
}
