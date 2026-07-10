<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$trips    = supabase_get(SB_API . 'm_trips?select=amount,driver_salary,cleaner_salary&date=gte.'.$from.'&date=lte.'.$to);
$fuel     = supabase_get(SB_API . 'm_fuel_expenses?select=total&date=gte.'.$from.'&date=lte.'.$to);
$veh_exp  = supabase_get(SB_API . 'm_vehicle_expenses?select=amount&date=gte.'.$from.'&date=lte.'.$to);
$gen_exp  = supabase_get(SB_API . 'm_general_expenses?select=amount&date=gte.'.$from.'&date=lte.'.$to);
$advances = supabase_get(SB_API . 'm_advance_payments?select=amount&date=gte.'.$from.'&date=lte.'.$to);

$revenue = $driver_payouts = $cleaner_payouts = 0;
$trip_count = count($trips);
foreach ($trips as $t) {
    $revenue        += floatval($t['amount']);
    $driver_payouts += floatval($t['driver_salary'] ?? 0);
    $cleaner_payouts+= floatval($t['cleaner_salary'] ?? 0);
}
$fuel_cost       = array_sum(array_column($fuel,    'total'));
$vehicle_expenses= array_sum(array_column($veh_exp, 'amount'));
$general_expenses= array_sum(array_column($gen_exp, 'amount'));
$advance_payments= array_sum(array_column($advances,'amount'));
$estimated_profit= $revenue - $fuel_cost - $vehicle_expenses - $general_expenses - $driver_payouts - $cleaner_payouts;

echo json_encode([
    'revenue'           => round($revenue,2),
    'trip_count'        => $trip_count,
    'fuel_cost'         => round($fuel_cost,2),
    'vehicle_expenses'  => round($vehicle_expenses,2),
    'general_expenses'  => round($general_expenses,2),
    'driver_payouts'    => round($driver_payouts,2),
    'cleaner_payouts'   => round($cleaner_payouts,2),
    'advance_payments'  => round($advance_payments,2),
    'estimated_profit'  => round($estimated_profit,2),
]);
