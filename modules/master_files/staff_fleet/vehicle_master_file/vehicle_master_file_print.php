<?php
// entries/vehicle_master_file/vehicle_master_file_print.php — Vehicle print view

require_once __DIR__ . '/../../../../server/supabase.php';

// Fetch company info
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

$ref           = htmlspecialchars($_GET['ref']           ?? '');
$plate_number  = htmlspecialchars($_GET['plate_number']  ?? '');
$make          = htmlspecialchars($_GET['make']          ?? '');
$model         = htmlspecialchars($_GET['model']         ?? '');
$type          = htmlspecialchars($_GET['type']          ?? '');
$fuel_type     = htmlspecialchars($_GET['fuel_type']     ?? '');
$status        = htmlspecialchars($_GET['status']        ?? '');
$last_location = htmlspecialchars($_GET['last_location'] ?? '');
$mileage       = htmlspecialchars($_GET['mileage']       ?? '');
$year          = htmlspecialchars($_GET['year']          ?? '');
$capacity      = htmlspecialchars($_GET['capacity']      ?? '');

$type_label = match($type) {
    'lorry'   => 'Lorry',
    'bowser'  => 'Bowser',
    'tipper'  => 'Tipper',
    'truck'   => 'Truck',
    'van'     => 'Van',
    'bus'     => 'Bus',
    default   => $type,
};

$fuel_label = match($fuel_type) {
    'petrol' => 'Petrol',
    'diesel' => 'Diesel',
    default  => $fuel_type,
};

$status_label = match($status) {
    'active'   => 'Active',
    'inactive' => 'Inactive',
    default    => $status,
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vehicle — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, sans-serif;
      font-size: 13px;
      color: #000;
      background: #f4f4f4;
    }

    .page {
      width: 210mm;
      min-height: 297mm;
      margin: 20px auto;
      background: #fff;
      padding: 16mm 18mm;
      display: flex;
      flex-direction: column;
      box-shadow: 0 0 12px rgba(0,0,0,0.15);
    }

    /* ── Letterhead ── */
    .letterhead {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding-bottom: 12px;
      border-bottom: 3px solid #1a1a2e;
      margin-bottom: 24px;
    }
    .letterhead .company-name {
      font-size: 22px;
      font-weight: 700;
      color: #1a1a2e;
      letter-spacing: 0.5px;
    }
    .letterhead .company-contact {
      text-align: right;
      font-size: 11px;
      color: #444;
      line-height: 1.7;
    }

    /* ── Document title ── */
    .doc-title {
      text-align: center;
      margin-bottom: 24px;
    }
    .doc-title h2 {
      font-size: 15px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #1a1a2e;
      border-bottom: 1px solid #ccc;
      display: inline-block;
      padding-bottom: 4px;
    }
    .doc-title .ref {
      font-size: 12px;
      color: #555;
      margin-top: 4px;
    }

    /* ── Details table ── */
    .details {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 32px;
    }
    .details tr:nth-child(even) td { background: #f9f9f9; }
    .details th {
      width: 38%;
      padding: 9px 14px;
      text-align: left;
      font-weight: 600;
      font-size: 12px;
      color: #333;
      border: 1px solid #ddd;
      background: #f0f0f0;
    }
    .details td {
      padding: 9px 14px;
      border: 1px solid #ddd;
      font-size: 12px;
      color: #111;
    }

    /* ── Signature area ── */
    .signatures {
      display: flex;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 40px;
    }
    .sig-block { text-align: center; width: 40%; }
    .sig-block .sig-line {
      border-top: 1px solid #555;
      margin-bottom: 6px;
    }
    .sig-block .sig-label { font-size: 11px; color: #555; }

    /* ── Footer ── */
    .footer {
      margin-top: 32px;
      padding-top: 10px;
      border-top: 1px solid #ccc;
      text-align: center;
      font-size: 10px;
      color: #777;
      line-height: 1.8;
    }

    /* ── Actions (hidden on print) ── */
    .actions {
      text-align: center;
      padding: 12px;
      background: #fff;
    }
    .actions button {
      padding: 7px 20px;
      font-size: 13px;
      cursor: pointer;
      margin: 0 4px;
      border: 1px solid #999;
      border-radius: 4px;
      background: #f5f5f5;
    }
    .actions button.btn-print {
      background: #1a1a2e;
      color: #fff;
      border-color: #1a1a2e;
    }

    @media print {
      body { background: #fff; }
      .actions { display: none; }
      .page { margin: 0; box-shadow: none; width: 100%; padding: 12mm 14mm; }
    }
  </style>
</head>
<body>

  <div class="actions">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
  </div>

  <div class="page">

    <!-- Letterhead -->
    <div class="letterhead">
      <div>
        <div class="company-name"><?= htmlspecialchars($company['name'] ?? '') ?></div>
      </div>
      <div class="company-contact">
        <?php if (!empty($company['address'])): ?>
          <?= nl2br(htmlspecialchars($company['address'])) ?><br>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?>
          Tel: <?= htmlspecialchars($company['phone']) ?><br>
        <?php endif; ?>
        <?php if (!empty($company['email'])): ?>
          <?= htmlspecialchars($company['email']) ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Document title -->
    <div class="doc-title">
      <h2>Vehicle Master File</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <!-- Vehicle details -->
    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>
      <tr><th>Plate Number</th><td><?= $plate_number ?></td></tr>
      <tr><th>Make</th><td><?= $make ?></td></tr>
      <tr><th>Model</th><td><?= $model ?></td></tr>
      <tr><th>Type</th><td><?= $type_label ?></td></tr>
      <tr><th>Fuel Type</th><td><?= $fuel_label ?></td></tr>
      <tr><th>Status</th><td><?= $status_label ?></td></tr>
      <tr><th>Year</th><td><?= $year ?></td></tr>
      <tr><th>Mileage</th><td><?= $mileage ?></td></tr>
      <tr><th>Capacity</th><td><?= $capacity ?></td></tr>
      <tr><th>Last Location</th><td><?= $last_location ?></td></tr>
    </table>

    <!-- Signature area -->
    <div class="signatures">
      <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Prepared By</div>
      </div>
      <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-label">Authorized By</div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <?= htmlspecialchars($company['name'] ?? '') ?>
      <?php if (!empty($company['address'])): ?> &mdash; <?= htmlspecialchars($company['address']) ?><?php endif; ?>
      <?php if (!empty($company['phone'])): ?> &mdash; <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
      <?php if (!empty($company['email'])): ?> &mdash; <?= htmlspecialchars($company['email']) ?><?php endif; ?>
    </div>

  </div>

</body>
</html>
