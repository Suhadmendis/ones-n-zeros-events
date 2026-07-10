<?php
// modules/finance/period_closing/period_closing_print.php — Period Closing print view

require_once __DIR__ . '/../../../../server/supabase.php';

$url = SUPABASE_URL . SB_API . 'sys_company_info?select=name,address,phone,email&limit=1';
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: '               . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json',
]);
$rows    = json_decode(curl_exec($ch), true) ?? [];
$company = $rows[0] ?? [];

$ref            = htmlspecialchars($_GET['ref']            ?? '');
$period         = htmlspecialchars($_GET['period']         ?? '');
$period_name    = htmlspecialchars($_GET['period_name']    ?? '');
$closing_date   = htmlspecialchars($_GET['closing_date']   ?? '');
$total_revenue  = (float) ($_GET['total_revenue']          ?? 0);
$total_expenses = (float) ($_GET['total_expenses']         ?? 0);
$net_profit     = (float) ($_GET['net_profit']             ?? 0);
$notes          = htmlspecialchars($_GET['notes']          ?? '');
$status         = htmlspecialchars($_GET['status']         ?? '');

$status_labels = ['open' => 'Open', 'closed' => 'Closed', 'locked' => 'Locked'];
$status_label  = $status_labels[$status] ?? $status;
$fmt           = fn($v) => number_format((float)$v, 2);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Period Closing — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #000; background: #f4f4f4; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 16mm 18mm; display: flex; flex-direction: column; box-shadow: 0 0 12px rgba(0,0,0,0.15); }
    .letterhead { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 3px solid #1a1a2e; margin-bottom: 24px; }
    .letterhead .company-name { font-size: 22px; font-weight: 700; color: #1a1a2e; }
    .letterhead .company-contact { text-align: right; font-size: 11px; color: #444; line-height: 1.7; }
    .doc-title { text-align: center; margin-bottom: 24px; }
    .doc-title h2 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; border-bottom: 1px solid #ccc; display: inline-block; padding-bottom: 4px; }
    .doc-title .ref { font-size: 12px; color: #555; margin-top: 4px; }
    .details { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
    .details tr:nth-child(even) td { background: #f9f9f9; }
    .details th { width: 38%; padding: 9px 14px; text-align: left; font-weight: 600; font-size: 12px; color: #333; border: 1px solid #ddd; background: #f0f0f0; }
    .details td { padding: 9px 14px; border: 1px solid #ddd; font-size: 12px; color: #111; }
    .details td.num { text-align: right; font-family: monospace; }
    .profit-pos { color: #198754; font-weight: 700; }
    .profit-neg { color: #dc3545; font-weight: 700; }
    .badge-open   { color: #0a3622; background: #d1e7dd; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge-closed { color: #664d03; background: #fff3cd; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge-locked { color: #58151c; background: #f8d7da; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
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
      <h2>Period Closing</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>
      <tr><th>Period</th><td><?= $period ?></td></tr>
      <tr><th>Period Name</th><td><?= $period_name ?></td></tr>
      <tr><th>Closing Date</th><td><?= $closing_date ?></td></tr>
      <tr><th>Status</th><td><span class="badge-<?= $status ?>"><?= $status_label ?></span></td></tr>
      <?php if ($notes): ?><tr><th>Notes</th><td><?= nl2br($notes) ?></td></tr><?php endif; ?>
      <tr><th>Total Revenue</th><td class="num"><?= $fmt($total_revenue) ?></td></tr>
      <tr><th>Total Expenses</th><td class="num"><?= $fmt($total_expenses) ?></td></tr>
      <tr>
        <th>Net Profit</th>
        <td class="num <?= $net_profit >= 0 ? 'profit-pos' : 'profit-neg' ?>"><?= $fmt($net_profit) ?></td>
      </tr>
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
