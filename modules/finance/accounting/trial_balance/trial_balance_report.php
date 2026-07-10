<?php
// modules/finance/accounting/trial_balance/trial_balance_report.php
// Standalone printable report — no ERP layout/sidebar

require_once __DIR__ . '/../../../../server/supabase.php';

$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');
$basis     = trim($_GET['basis']     ?? 'accrual');

if (!$date_from || !$date_to || $date_from > $date_to) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#dc3545">Invalid date range. Please close this tab and try again.</p>');
}

// ── Fetch company info ──────────────────────────────────────────────────────
$company_rows = supabase_get(SB_API . 'sys_company_info?select=name,address,phone,email&limit=1');
$company      = $company_rows[0] ?? [];

// ── Call RPC for trial balance data ────────────────────────────────────────
$tb_rows = supabase_get(
    SB_API . 'rpc/get_trial_balance'
    . '?p_date_from=' . rawurlencode($date_from)
    . '&p_date_to='   . rawurlencode($date_to)
    . '&p_basis='     . rawurlencode($basis)
);

// Detect error
if (isset($tb_rows['code'])) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#dc3545">Could not load trial balance data: ' . htmlspecialchars($tb_rows['message'] ?? 'Unknown error') . '</p>');
}

// ── Organise rows by account type ───────────────────────────────────────────
$type_order   = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
$rows_by_type = array_fill_keys($type_order, []);

$grand_ob_dr = 0; $grand_ob_cr = 0;
$grand_pd_dr = 0; $grand_pd_cr = 0;
$grand_cl_dr = 0; $grand_cl_cr = 0;

foreach ($tb_rows as $r) {
    $type   = $r['account_type'] ?? 'Asset';
    $bucket = in_array($type, $type_order) ? $type : 'Asset';
    $rows_by_type[$bucket][] = $r;

    $grand_ob_dr += (float)($r['ob_debit']  ?? 0);
    $grand_ob_cr += (float)($r['ob_credit'] ?? 0);
    $grand_pd_dr += (float)($r['pd_debit']  ?? 0);
    $grand_pd_cr += (float)($r['pd_credit'] ?? 0);
    $grand_cl_dr += (float)($r['cl_debit']  ?? 0);
    $grand_cl_cr += (float)($r['cl_credit'] ?? 0);
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmt(float $v): string {
    if ($v == 0) return '—';
    return number_format($v, 2);
}
function fmtDate(string $v): string {
    if (!$v) return '';
    [$yr, $mo, $dy] = explode('-', $v);
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return $dy . ' ' . $months[(int)$mo - 1] . ' ' . $yr;
}

$generated_at = date('d M Y H:i');
$basis_label  = ($basis === 'accrual') ? 'Accrual (Posted entries only)' : 'All Entries';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trial Balance — <?= htmlspecialchars(fmtDate($date_from)) ?> to <?= htmlspecialchars(fmtDate($date_to)) ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 24px 32px;
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 12px;
      color: #1a1a1a;
      background: #fff;
    }

    .rpt-company     { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
    .rpt-company-sub { font-size: 11px; color: #6c757d; margin-bottom: 14px; }
    .rpt-title       { font-size: 20px; font-weight: 700; color: #1a1a2e; border-bottom: 2px solid #1a1a2e; padding-bottom: 6px; margin-bottom: 4px; }
    .rpt-period      { font-size: 12px; color: #495057; margin-bottom: 3px; }
    .rpt-meta        { font-size: 11px; color: #6c757d; margin-bottom: 16px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 5px 8px; text-align: left; }
    th { background: #1a1a2e; color: #fff; font-size: 10.5px; font-weight: 600; letter-spacing: .03em; white-space: nowrap; }
    th.num { text-align: right; }
    td.num  { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
    td.zero { text-align: right; font-family: 'Courier New', monospace; color: #ccc; }

    tr.type-header td {
      background: #e9ecef;
      font-weight: 700;
      font-size: 10.5px;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #495057;
      padding: 7px 8px 6px;
      border-top: 1px solid #adb5bd;
    }

    tr.data-row td  { border-bottom: 1px solid #f0f0f0; }
    td.code { font-family: 'Courier New', monospace; font-weight: 600; color: #0d6efd; }

    tr.sub-total td {
      background: #f8f9fa;
      font-weight: 600;
      border-top: 1px solid #dee2e6;
      border-bottom: 2px solid #dee2e6;
      font-size: 11.5px;
    }

    tr.grand-total td {
      background: #1a1a2e;
      color: #fff;
      font-weight: 700;
      font-size: 12.5px;
      border-top: 2px solid #0d6efd;
    }
    tr.grand-total td.num { color: #fff; }

    tr.balance-check td {
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      padding: 6px 8px;
    }
    tr.balance-ok td   { background: #d1e7dd; color: #0a3622; }
    tr.balance-fail td { background: #f8d7da; color: #58151c; }

    tr.no-data td {
      text-align: center;
      padding: 24px;
      color: #6c757d;
      font-style: italic;
    }

    .rpt-footer {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #dee2e6;
      font-size: 10.5px;
      color: #6c757d;
      display: flex;
      justify-content: space-between;
    }

    .toolbar { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 16px; }
    .btn { padding: 6px 14px; border-radius: 4px; font-size: 12px; cursor: pointer; border: 1px solid; font-family: inherit; }
    .btn-print     { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
    .btn-close-tab { background: #fff; color: #6c757d; border-color: #dee2e6; }
    .btn:hover { opacity: .87; }

    @media print {
      body { padding: 10px 14px; }
      .toolbar { display: none; }
      @page { margin: 14mm; }
    }
  </style>
</head>
<body>

<div class="toolbar">
  <button class="btn btn-close-tab" onclick="window.close()">Close</button>
  <button class="btn btn-print" onclick="window.print()">&#128438; Print / Save PDF</button>
</div>

<div class="rpt-header">
  <?php if (!empty($company['name'])): ?>
  <div class="rpt-company"><?= htmlspecialchars($company['name']) ?></div>
  <div class="rpt-company-sub"><?= htmlspecialchars(implode(' | ', array_filter([
    $company['address'] ?? '',
    $company['phone']   ?? '',
    $company['email']   ?? '',
  ]))) ?></div>
  <?php endif; ?>
  <div class="rpt-title">Trial Balance</div>
  <div class="rpt-period">Period: <strong><?= htmlspecialchars(fmtDate($date_from)) ?></strong> to <strong><?= htmlspecialchars(fmtDate($date_to)) ?></strong> &nbsp;|&nbsp; <?= htmlspecialchars($basis_label) ?></div>
  <div class="rpt-meta">Generated: <?= $generated_at ?></div>
</div>

<table>
  <thead>
    <tr>
      <th style="width:80px">Code</th>
      <th>Account Name</th>
      <th class="num" style="width:110px">Opening Dr</th>
      <th class="num" style="width:110px">Opening Cr</th>
      <th class="num" style="width:110px">Period Dr</th>
      <th class="num" style="width:110px">Period Cr</th>
      <th class="num" style="width:110px">Closing Dr</th>
      <th class="num" style="width:110px">Closing Cr</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $has_any = false;
  foreach ($type_order as $type):
    if (empty($rows_by_type[$type])) continue;
    $has_any = true;

    $t_ob_dr = $t_ob_cr = $t_pd_dr = $t_pd_cr = $t_cl_dr = $t_cl_cr = 0;
  ?>
    <tr class="type-header"><td colspan="8"><?= htmlspecialchars($type) ?></td></tr>
    <?php foreach ($rows_by_type[$type] as $r):
      $ob_dr = (float)($r['ob_debit']  ?? 0);
      $ob_cr = (float)($r['ob_credit'] ?? 0);
      $pd_dr = (float)($r['pd_debit']  ?? 0);
      $pd_cr = (float)($r['pd_credit'] ?? 0);
      $cl_dr = (float)($r['cl_debit']  ?? 0);
      $cl_cr = (float)($r['cl_credit'] ?? 0);
      $t_ob_dr += $ob_dr; $t_ob_cr += $ob_cr;
      $t_pd_dr += $pd_dr; $t_pd_cr += $pd_cr;
      $t_cl_dr += $cl_dr; $t_cl_cr += $cl_cr;
    ?>
    <tr class="data-row">
      <td class="code"><?= htmlspecialchars($r['account_code']) ?></td>
      <td><?= htmlspecialchars($r['account_name']) ?></td>
      <td class="<?= $ob_dr ? 'num' : 'zero' ?>"><?= fmt($ob_dr) ?></td>
      <td class="<?= $ob_cr ? 'num' : 'zero' ?>"><?= fmt($ob_cr) ?></td>
      <td class="<?= $pd_dr ? 'num' : 'zero' ?>"><?= fmt($pd_dr) ?></td>
      <td class="<?= $pd_cr ? 'num' : 'zero' ?>"><?= fmt($pd_cr) ?></td>
      <td class="<?= $cl_dr ? 'num' : 'zero' ?>"><?= fmt($cl_dr) ?></td>
      <td class="<?= $cl_cr ? 'num' : 'zero' ?>"><?= fmt($cl_cr) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="sub-total">
      <td colspan="2" style="text-align:right;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#6c757d">
        <?= htmlspecialchars($type) ?> Sub-total
      </td>
      <td class="num"><?= fmt($t_ob_dr) ?></td>
      <td class="num"><?= fmt($t_ob_cr) ?></td>
      <td class="num"><?= fmt($t_pd_dr) ?></td>
      <td class="num"><?= fmt($t_pd_cr) ?></td>
      <td class="num"><?= fmt($t_cl_dr) ?></td>
      <td class="num"><?= fmt($t_cl_cr) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$has_any): ?>
    <tr class="no-data"><td colspan="8">No accounts with activity found for the selected period.</td></tr>
  <?php endif; ?>
  </tbody>
  <tfoot>
    <tr class="grand-total">
      <td colspan="2">Grand Total</td>
      <td class="num"><?= number_format($grand_ob_dr, 2) ?></td>
      <td class="num"><?= number_format($grand_ob_cr, 2) ?></td>
      <td class="num"><?= number_format($grand_pd_dr, 2) ?></td>
      <td class="num"><?= number_format($grand_pd_cr, 2) ?></td>
      <td class="num"><?= number_format($grand_cl_dr, 2) ?></td>
      <td class="num"><?= number_format($grand_cl_cr, 2) ?></td>
    </tr>
    <?php
      $pd_ok = abs($grand_pd_dr - $grand_pd_cr) < 0.01;
      $cl_ok = abs($grand_cl_dr - $grand_cl_cr) < 0.01;
      $balanced = $pd_ok && $cl_ok;
    ?>
    <tr class="balance-check <?= $balanced ? 'balance-ok' : 'balance-fail' ?>">
      <td colspan="8">
        <?php if ($balanced): ?>
          ✓ Trial balance is in balance &mdash; Period: <?= number_format($grand_pd_dr, 2) ?> Dr = <?= number_format($grand_pd_cr, 2) ?> Cr &nbsp;|&nbsp; Closing: <?= number_format($grand_cl_dr, 2) ?> Dr = <?= number_format($grand_cl_cr, 2) ?> Cr
        <?php else: ?>
          ⚠ Out of balance &mdash;
          <?php if (!$pd_ok): ?>Period difference: <?= number_format(abs($grand_pd_dr - $grand_pd_cr), 2) ?><?php endif; ?>
          <?php if (!$cl_ok): ?> &nbsp;|&nbsp; Closing difference: <?= number_format(abs($grand_cl_dr - $grand_cl_cr), 2) ?><?php endif; ?>
        <?php endif; ?>
      </td>
    </tr>
  </tfoot>
</table>

<div class="rpt-footer">
  <div><?= htmlspecialchars($company['name'] ?? 'Ones n Zeros ERP') ?> &mdash; Confidential</div>
  <div>Generated <?= $generated_at ?> &nbsp;|&nbsp; Trial Balance</div>
</div>

</body>
</html>
