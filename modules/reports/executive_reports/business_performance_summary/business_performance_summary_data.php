<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$_sbError = null;

$action = $_GET['action'] ?? 'summary';
$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');

if ($action === 'summary') {
    $trips    = supabase_get("/rest/v1/m_trips?select=amount,driver_salary,cleaner_salary&date=gte.{$from}&date=lte.{$to}");
    $fuel     = supabase_get("/rest/v1/m_fuel_expenses?select=total&date=gte.{$from}&date=lte.{$to}");
    $ve       = supabase_get("/rest/v1/m_vehicle_expenses?select=amount&date=gte.{$from}&date=lte.{$to}");
    $ge       = supabase_get("/rest/v1/m_general_expenses?select=amount&date=gte.{$from}&date=lte.{$to}");
    $adv      = supabase_get("/rest/v1/m_advance_payments?select=amount&date=gte.{$from}&date=lte.{$to}");

    $revenue        = array_sum(array_column($trips, 'amount'));
    $driver_payouts = array_sum(array_column($trips, 'driver_salary'));
    $cleaner_payouts= array_sum(array_column($trips, 'cleaner_salary'));
    $fuel_cost      = array_sum(array_column($fuel, 'total'));
    $vehicle_exp    = array_sum(array_column($ve, 'amount'));
    $general_exp    = array_sum(array_column($ge, 'amount'));
    $advance_payments = array_sum(array_column($adv, 'amount'));
    $trip_count     = count($trips);
    $estimated_profit = $revenue - $fuel_cost - $vehicle_exp - $general_exp - $driver_payouts - $cleaner_payouts;

    $out = [
        'trip_count'       => $trip_count,
        'revenue'          => $revenue,
        'fuel_cost'        => $fuel_cost,
        'vehicle_exp'      => $vehicle_exp,
        'general_exp'      => $general_exp,
        'driver_payouts'   => $driver_payouts,
        'cleaner_payouts'  => $cleaner_payouts,
        'advance_payments' => $advance_payments,
        'estimated_profit' => $estimated_profit,
    ];
    if ($_sbError) $out['warning'] = $_sbError;
    echo json_encode($out);
}
