<?php
// server/general/module_data.php — thin wrapper over the common number-series engine

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/number_series.php';

function fetchModuleData(string $systemName): array {
    return previewNextReference($systemName);
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $systemName = $_GET['system_name'] ?? '';
    echo json_encode(fetchModuleData($systemName));
}
