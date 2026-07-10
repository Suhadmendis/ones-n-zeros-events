<?php
// server/general/number_series.php — Common reference-number generation engine
// Replaces the old per-module sys_company_info sequence-column mechanism.

require_once __DIR__ . '/../supabase.php';

function getNumberSeriesBySystemName(string $systemName): ?array {
    $modules   = supabase_get(SB_API . 'sys_tms_modules?select=ref&folder=eq.' . urlencode($systemName) . '&limit=1');
    $moduleRef = $modules[0]['ref'] ?? null;
    if (!$moduleRef) return null;

    $rows = supabase_get(
        SB_API . 'sys_tms_module_number_series?select=id,current_number,prefix,suffix,padding_length,separator,reset_period,updated_at&module_ref=eq.'
        . urlencode($moduleRef) . '&limit=1'
    );
    return $rows[0] ?? null;
}

function formatReference(array $series, int $number): string {
    $padded = str_pad((string) $number, (int) ($series['padding_length'] ?? 7), '0', STR_PAD_LEFT);
    return ($series['prefix'] ?? '') . ($series['separator'] ?? '-') . $padded . ($series['suffix'] ?? '');
}

function isNumberSeriesPeriodElapsed(array $series): bool {
    $period = $series['reset_period'] ?? 'never';
    if ($period === 'never' || empty($series['updated_at'])) return false;

    $last = new DateTime($series['updated_at']);
    $now  = new DateTime();
    switch ($period) {
        case 'yearly':  return $last->format('Y')    !== $now->format('Y');
        case 'monthly': return $last->format('Y-m')  !== $now->format('Y-m');
        case 'daily':   return $last->format('Y-m-d') !== $now->format('Y-m-d');
        default:        return false;
    }
}

// Read-only preview — used to show the next reference before save, without consuming it.
function previewNextReference(string $systemName): array {
    $series = getNumberSeriesBySystemName($systemName);
    $module = supabase_get(SB_API . 'sys_tms_modules?select=name&folder=eq.' . urlencode($systemName) . '&limit=1');

    if (!$series) {
        return ['ref' => '', 'module' => $module[0]['name'] ?? ''];
    }

    $current = isNumberSeriesPeriodElapsed($series) ? 0 : (int) $series['current_number'];
    return [
        'ref'    => formatReference($series, $current + 1),
        'module' => $module[0]['name'] ?? '',
    ];
}

// Atomically consumes the next reference at save time. Uses optimistic-concurrency
// retry (conditional PATCH on the previously-read current_number) since this project
// talks to Supabase purely via PostgREST — no stored procedures elsewhere to lean on.
function consumeNextReference(string $systemName): string {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $series = getNumberSeriesBySystemName($systemName);
        if (!$series) {
            throw new RuntimeException("No number series configured for module '{$systemName}'");
        }

        $current = isNumberSeriesPeriodElapsed($series) ? 0 : (int) $series['current_number'];
        $next    = $current + 1;

        $updated = supabase_patch(
            SB_API . 'sys_tms_module_number_series?id=eq.' . $series['id'] . '&current_number=eq.' . $current,
            ['current_number' => $next, 'updated_at' => date('c')]
        );

        if (!empty($updated)) {
            return formatReference($series, $next);
        }

        usleep(50000 * ($attempt + 1)); // concurrent save won the race — back off and retry
    }

    throw new RuntimeException("Failed to generate reference for '{$systemName}' after retries (concurrent contention)");
}
