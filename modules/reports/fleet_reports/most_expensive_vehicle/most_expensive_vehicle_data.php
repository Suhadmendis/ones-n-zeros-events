<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $fuelExpenses = supabase_get(
        SB_API . 'm_fuel_expenses?select=vehicle_ref,total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );
    $vehExpenses = supabase_get(
        SB_API . 'm_vehicle_expenses?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model,year&limit=10000');

    $vehicleMap = [];
    foreach ($vehicles as $v) {
        $vehicleMap[$v['ref']] = $v;
    }

    $fuelAgg = [];
    foreach ($fuelExpenses as $f) {
        $vid = $f['vehicle_ref'] ?? null;
        if (!$vid) continue;
        $fuelAgg[$vid] = ($fuelAgg[$vid] ?? 0.0) + (float)($f['total'] ?? 0);
    }

    $maintAgg = [];
    $allVids = [];
    foreach ($vehExpenses as $e) {
        $vid = $e['vehicle_ref'] ?? null;
        if (!$vid) continue;
        $maintAgg[$vid] = ($maintAgg[$vid] ?? 0.0) + (float)($e['amount'] ?? 0);
        $allVids[$vid] = true;
    }
    foreach ($fuelAgg as $vid => $v) {
        $allVids[$vid] = true;
    }

    $result = [];
    foreach (array_keys($allVids) as $vid) {
        $v     = $vehicleMap[$vid] ?? null;
        $fuel  = $fuelAgg[$vid]  ?? 0.0;
        $maint = $maintAgg[$vid] ?? 0.0;
        $total = $fuel + $maint;
        $result[] = [
            'ref'               => $v['ref']          ?? $vid,
            'plate_number'      => $v['plate_number'] ?? '',
            'make'              => $v['make']         ?? '',
            'model'             => $v['model']        ?? '',
            'year'              => $v['year']         ?? '',
            'fuel_cost'         => round($fuel, 2),
            'maintenance_cost'  => round($maint, 2),
            'total_cost'        => round($total, 2),
        ];
    }

    usort($result, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);

    $rank = 1;
    foreach ($result as &$row) {
        $row['rank'] = $rank++;
    }
    unset($row);

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
