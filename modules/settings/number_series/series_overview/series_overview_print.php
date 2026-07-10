<?php
// modules/settings/number_series/series_overview/series_overview_print.php — Series Overview print view

require_once __DIR__ . '/../../../../server/supabase.php';

$rows    = supabase_get(SB_API . 'sys_company_info?select=name,address,phone,email&limit=1');
$company = $rows[0] ?? [];

$ref            = htmlspecialchars($_GET['ref']            ?? '');
$module_name    = htmlspecialchars($_GET['module_name']    ?? '');
$prefix         = htmlspecialchars($_GET['prefix']         ?? '');
$suffix         = htmlspecialchars($_GET['suffix']         ?? '');
$separator      = htmlspecialchars($_GET['separator']      ?? '-');
$padding_length = (int) ($_GET['padding_length'] ?? 7);
$current_number = (int) ($_GET['current_number'] ?? 0);
$reset_period   = htmlspecialchars($_GET['reset_period']   ?? 'never');
$record_status  = htmlspecialchars($_GET['record_status']  ?? '');

$preview = $prefix . $separator . str_pad($current_number + 1, $padding_length, '0', STR_PAD_LEFT) . $suffix;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Series Overview — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #000; background: #f4f4f4; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 16mm 18mm; display: flex; flex-direction: column; box-shadow: 0 0 12px rgba(0,0,0,0.15); }
    .letterhead { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 3px solid #1a1a2e; margin-bottom: 24px; }
    .letterhead .company-name { font-size: 22px; font-weight: 700; color: #1a1a2e; letter-spacing: 0.5px; }
    .letterhead .company-contact { text-align: right; font-size: 11px; color: #444; line-height: 1.7; }
    .doc-title { text-align: center; margin-bottom: 24px; }
    .doc-title h2 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; border-bottom: 1px solid #ccc; display: inline-block; padding-bottom: 4px; }
    .doc-title .ref { font-size: 12px; color: #555; margin-top: 4px; }
    .details { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
    .details tr:nth-child(even) td { background: #f9f9f9; }
    .details th { width: 38%; padding: 9px 14px; text-align: left; font-weight: 600; font-size: 12px; color: #333; border: 1px solid #ddd; background: #f0f0f0; }
    .details td { padding: 9px 14px; border: 1px solid #ddd; font-size: 12px; color: #111; }
    .signatures { display: flex; justify-content: space-between; margin-top: auto; padding-top: 40px; }
    .sig-block { text-align: center; width: 40%; }
    .sig-block .sig-line { border-top: 1px solid #555; margin-bottom: 6px; }
    .sig-block .sig-label { font-size: 11px; color: #555; }
    .footer { margin-top: 32px; padding-top: 10px; border-top: 1px solid #ccc; text-align: center; font-size: 10px; color: #777; line-height: 1.8; }
    .actions { text-align: center; padding: 12px; background: #fff; }
    .actions button { padding: 7px 20px; font-size: 13px; cursor: pointer; margin: 0 4px; border: 1px solid #999; border-radius: 4px; background: #f5f5f5; }
    .actions button.btn-print { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
    @media print { body { background: #fff; } .actions { display: none; } .page { margin: 0; box-shadow: none; width: 100%; padding: 12mm 14mm; } }
  </style>
</head>
<body>

  <div class="actions">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
  </div>

  <div class="page">

    <div class="letterhead">
      <div><div class="company-name"><?= htmlspecialchars($company['name'] ?? '') ?></div></div>
      <div class="company-contact">
        <?php if (!empty($company['address'])): ?><?= nl2br(htmlspecialchars($company['address'])) ?><br><?php endif; ?>
        <?php if (!empty($company['phone'])): ?>Tel: <?= htmlspecialchars($company['phone']) ?><br><?php endif; ?>
        <?php if (!empty($company['email'])): ?><?= htmlspecialchars($company['email']) ?><?php endif; ?>
      </div>
    </div>

    <div class="doc-title">
      <h2>Series Overview</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>
      <tr><th>Module</th><td><?= $module_name ?></td></tr>
      <tr><th>Prefix</th><td><?= $prefix ?: '—' ?></td></tr>
      <tr><th>Suffix</th><td><?= $suffix ?: '—' ?></td></tr>
      <tr><th>Separator</th><td><?= $separator ?: '—' ?></td></tr>
      <tr><th>Padding</th><td><?= $padding_length ?></td></tr>
      <tr><th>Current Number</th><td><?= $current_number ?></td></tr>
      <tr><th>Reset Period</th><td><?= ucfirst($reset_period) ?></td></tr>
      <tr><th>Next Preview</th><td><strong><?= $preview ?></strong></td></tr>
      <tr><th>Status</th><td><?= $record_status ? ucfirst($record_status) : '—' ?></td></tr>
    </table>

    <div class="signatures">
      <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Prepared By</div></div>
      <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Authorized By</div></div>
    </div>

    <div class="footer">
      <?= htmlspecialchars($company['name'] ?? '') ?>
      <?php if (!empty($company['address'])): ?> &mdash; <?= htmlspecialchars($company['address']) ?><?php endif; ?>
      <?php if (!empty($company['phone'])): ?> &mdash; <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
      <?php if (!empty($company['email'])): ?> &mdash; <?= htmlspecialchars($company['email']) ?><?php endif; ?>
    </div>

  </div>

</body>
</html>
