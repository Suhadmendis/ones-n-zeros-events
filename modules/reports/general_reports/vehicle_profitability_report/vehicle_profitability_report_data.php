<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    // Fetch trips, fuel expenses, vehicle expenses in range (parallel-style, sequential calls)
    $trips = supabase_get(
        SB_API . 'm_trips?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );
    $fuelExpenses = supabase_get(
        SB_API . 'm_fuel_expenses?select=vehicle_ref,total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );
    $vehExpenses = supabase_get(
        SB_API . 'm_vehicle_expenses?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    // Fetch all vehicles
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model&limit=10000');

    // Build vehicle lookup
    $vehicleMap = [];
    foreach ($vehicles as $v) {
        $vehicleMap[$v['ref']] = $v;
    }

    // Collect all vehicle refs that appear in any of the three datasets
    $allVids = [];

    $revenueAgg  = [];
    foreach ($trips as $t) {
        $vid = $t['vehicle_ref'] ?? null;
        if (!$vid) continue;
        $allVids[$vid] = true;
        $revenueAgg[$vid] = ($revenueAgg[$vid] ?? 0.0) + (float)($t['amount'] ?? 0);
    }

    $fuelAgg = [];
    foreach ($fuelExpenses as $f) {
        $vid = $f['vehicle_ref'] ?? null;
        if (!$vid) continue;
        $allVids[$vid] = true;
        $fuelAgg[$vid] = ($fuelAgg[$vid] ?? 0.0) + (float)($f['total'] ?? 0);
    }

    $vehExpAgg = [];
    foreach ($vehExpenses as $e) {
        $vid = $e['vehicle_ref'] ?? null;
        if (!$vid) continue;
        $allVids[$vid] = true;
        $vehExpAgg[$vid] = ($vehExpAgg[$vid] ?? 0.0) + (float)($e['amount'] ?? 0);
    }

    // Build result rows for every vehicle that appeared in any dataset
    $result = [];
    foreach (array_keys($allVids) as $vid) {
        $v       = $vehicleMap[$vid] ?? null;
        $revenue = $revenueAgg[$vid]  ?? 0.0;
        $fuel    = $fuelAgg[$vid]     ?? 0.0;
        $expense = $vehExpAgg[$vid]   ?? 0.0;
        $profit  = $revenue - $fuel - $expense;

        $result[] = [
            'ref'          => $v['ref']          ?? $vid,
            'plate_number' => $v['plate_number'] ?? '',
            'make'         => $v['make']         ?? '',
            'model'        => $v['model']        ?? '',
            'revenue'      => round($revenue, 2),
            'fuel_cost'    => round($fuel, 2),
            'veh_expense'  => round($expense, 2),
            'profit'       => round($profit, 2),
        ];
    }

    // Sort by profit descending
    usort($result, fn($a, $b) => $b['profit'] <=> $a['profit']);

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
