<?php
// entries/trip_running_chart/trip_running_chart_print.php — Trip / Running Chart print view

require_once __DIR__ . '/../../../../server/supabase.php';

$url = SUPABASE_URL . SB_API . 'sys_company_info?select=name,address,phone,email&limit=1';
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json',
]);
$rows    = json_decode(curl_exec($ch), true) ?? [];
$company = $rows[0] ?? [];

$ref          = htmlspecialchars($_GET['ref']          ?? '');
$vehicle_ref  = htmlspecialchars($_GET['vehicle_ref']  ?? '');
$vehicle_plate= htmlspecialchars($_GET['vehicle_plate']?? '');
$date         = htmlspecialchars($_GET['date']         ?? '');
$opening_km   = htmlspecialchars($_GET['opening_km']   ?? '');
$closing_km   = htmlspecialchars($_GET['closing_km']   ?? '');
$mileage      = htmlspecialchars($_GET['mileage']      ?? '');
$driver_ref   = htmlspecialchars($_GET['driver_ref']   ?? '');
$driver_name  = htmlspecialchars($_GET['driver_name']  ?? '');
$cleaner_ref  = htmlspecialchars($_GET['cleaner_ref']  ?? '');
$cleaner_name = htmlspecialchars($_GET['cleaner_name'] ?? '');
$item_ref     = htmlspecialchars($_GET['item_ref']     ?? '');
$item_name    = htmlspecialchars($_GET['item_name']    ?? '');
$run_no       = htmlspecialchars($_GET['run_no']       ?? '');
$from_loc     = htmlspecialchars($_GET['from_loc']     ?? '');
$to_loc       = htmlspecialchars($_GET['to_loc']       ?? '');
$amount         = htmlspecialchars($_GET['amount']         ?? '');
$driver_salary  = htmlspecialchars($_GET['driver_salary']  ?? '');
$cleaner_salary = htmlspecialchars($_GET['cleaner_salary'] ?? '');
$department   = htmlspecialchars($_GET['department']   ?? '');
$remark       = htmlspecialchars($_GET['remark']       ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Trip / Running Chart — <?= $ref ?></title>
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
    .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #555; letter-spacing: 0.5px; margin: 20px 0 6px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
    .details { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .details tr:nth-child(even) td { background: #f9f9f9; }
    .details th { width: 38%; padding: 8px 12px; text-align: left; font-weight: 600; font-size: 12px; color: #333; border: 1px solid #ddd; background: #f0f0f0; }
    .details td { padding: 8px 12px; border: 1px solid #ddd; font-size: 12px; color: #111; }
    .details .total-row td { font-weight: 700; font-size: 13px; background: #f0f0f0; }
    .signatures { display: flex; justify-content: space-between; margin-top: auto; padding-top: 40px; }
    .sig-block { text-align: center; width: 40%; }
    .sig-block .sig-line { border-top: 1px solid #555; margin-bottom: 6px; }
    .sig-block .sig-label { font-size: 11px; color: #555; }
    .footer { margin-top: 32px; padding-top: 10px; border-top: 1px solid #ccc; text-align: center; font-size: 10px; color: #777; line-height: 1.8; }
    .actions { text-align: center; padding: 12px; }
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
      <h2>Trip / Running Chart</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <div class="section-label">Vehicle &amp; Trip Details</div>
    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>
      <tr><th>Date</th><td><?= $date ?></td></tr>
      <tr><th>Vehicle Ref</th><td><?= $vehicle_ref ?></td></tr>
      <tr><th>Plate Number</th><td><?= $vehicle_plate ?></td></tr>
      <tr><th>Opening KM</th><td><?= $opening_km ?></td></tr>
      <tr><th>Closing KM</th><td><?= $closing_km ?></td></tr>
      <tr><th>Mileage</th><td><?= $mileage ?> km</td></tr>
      <?php if ($run_no): ?><tr><th>Run No.</th><td><?= $run_no ?></td></tr><?php endif; ?>
      <tr><th>From</th><td><?= $from_loc ?></td></tr>
      <tr><th>To</th><td><?= $to_loc ?></td></tr>
    </table>

    <div class="section-label">Personnel</div>
    <table class="details">
      <tr><th>Driver</th><td><?= $driver_ref ? $driver_ref . ' — ' . $driver_name : $driver_name ?></td></tr>
      <?php if ($cleaner_name): ?><tr><th>Cleaner</th><td><?= $cleaner_ref ? $cleaner_ref . ' — ' . $cleaner_name : $cleaner_name ?></td></tr><?php endif; ?>
    </table>

    <?php if ($item_name || $item_ref): ?>
    <div class="section-label">Item</div>
    <table class="details">
      <?php if ($item_ref): ?><tr><th>Item Ref</th><td><?= $item_ref ?></td></tr><?php endif; ?>
      <?php if ($item_name): ?><tr><th>Item Name</th><td><?= $item_name ?></td></tr><?php endif; ?>
    </table>
    <?php endif; ?>

    <div class="section-label">Financials</div>
    <table class="details">
      <tr class="total-row"><th>Amount</th><td>LKR <?= $amount ?></td></tr>
      <tr><th>Driver Salary</th><td>LKR <?= $driver_salary ?></td></tr>
      <?php if ($cleaner_salary !== ''): ?><tr><th>Cleaner Salary</th><td>LKR <?= $cleaner_salary ?></td></tr><?php endif; ?>
    </table>

    <?php if ($department || $remark): ?>
    <div class="section-label">Additional</div>
    <table class="details">
      <?php if ($department): ?><tr><th>Department</th><td><?= $department ?></td></tr><?php endif; ?>
      <?php if ($remark): ?><tr><th>Remark</th><td><?= $remark ?></td></tr><?php endif; ?>
    </table>
    <?php endif; ?>

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
