<?php
// modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_print.php — Financial Alerts print view

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

$ref             = htmlspecialchars($_GET['ref']             ?? '');
$alert_name      = htmlspecialchars($_GET['alert_name']      ?? '');
$condition_field = htmlspecialchars($_GET['condition_field'] ?? '');
$operator        = htmlspecialchars($_GET['operator']        ?? '');
$threshold_value = $_GET['threshold_value'] !== '' && isset($_GET['threshold_value']) ? number_format((float)$_GET['threshold_value'], 2) : '—';
$channel         = htmlspecialchars($_GET['channel']         ?? '');
$severity        = htmlspecialchars($_GET['severity']        ?? '');
$is_enabled      = ($_GET['is_enabled'] ?? '0') === '1';
$description     = htmlspecialchars($_GET['description']     ?? '');
$status          = htmlspecialchars($_GET['status']          ?? '');

$status_labels   = ['active' => 'Active', 'inactive' => 'Inactive'];
$status_label    = $status_labels[$status] ?? $status;
$channel_labels  = ['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'in_app' => 'In-App'];
$channel_label   = $channel_labels[$channel] ?? $channel;
$severity_label  = $severity ? ucfirst($severity) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Financial Alert Rule — <?= $ref ?></title>
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
    .badge-active   { color: #0a3622; background: #d1e7dd; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
    .badge-inactive { color: #41464b; background: #e2e3e5; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
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
      <h2>Financial Alert Rule</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>
      <tr><th>Alert Name</th><td><?= $alert_name ?></td></tr>
      <tr><th>Condition Field</th><td><?= $condition_field ?></td></tr>
      <tr><th>Condition</th><td><?= $condition_field ?> <?= $operator ?> <?= $threshold_value ?></td></tr>
      <tr><th>Channel</th><td><?= $channel_label ?></td></tr>
      <tr><th>Severity</th><td><?= $severity_label ?></td></tr>
      <tr><th>Enabled</th><td><?= $is_enabled ? 'Yes' : 'No' ?></td></tr>
      <tr><th>Status</th><td><span class="badge-<?= $status ?>"><?= $status_label ?></span></td></tr>
      <?php if ($description): ?><tr><th>Description</th><td><?= nl2br($description) ?></td></tr><?php endif; ?>
    </table>

    <div class="footer">
      <?= htmlspecialchars($company['name'] ?? '') ?>
      <?php if (!empty($company['address'])): ?> &mdash; <?= htmlspecialchars($company['address']) ?><?php endif; ?>
      <?php if (!empty($company['phone'])): ?> &mdash; <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
      <?php if (!empty($company['email'])): ?> &mdash; <?= htmlspecialchars($company['email']) ?><?php endif; ?>
    </div>
  </div>

</body>
</html>
