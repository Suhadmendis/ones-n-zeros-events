<?php
require_once __DIR__ . '/../../../../server/supabase.php';

$ch = curl_init(SUPABASE_URL . SB_API . 'sys_company_info?select=*&limit=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json'
]);
$companyRows = json_decode(curl_exec($ch), true) ?? [];
$company = $companyRows[0] ?? ['name' => 'Ones n Zeros', 'address' => '', 'phone' => '', 'email' => ''];

$ref           = htmlspecialchars($_GET['ref'] ?? '');
$date          = htmlspecialchars($_GET['date'] ?? '');
$recipientType = htmlspecialchars($_GET['recipient_type'] ?? '');
$recipientRef  = htmlspecialchars($_GET['recipient_ref'] ?? '');
$recipientName = htmlspecialchars($_GET['recipient_name'] ?? '');
$paymentType   = htmlspecialchars($_GET['payment_type'] ?? '');
$amount        = htmlspecialchars($_GET['amount'] ?? '');
$remark        = htmlspecialchars($_GET['remark'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment <?= $ref ?> | Ones n Zeros ERP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 12px; }
        .company-name { font-size: 22px; font-weight: bold; }
        .company-contact { text-align: right; font-size: 12px; color: #555; line-height: 1.6; }
        .doc-title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin: 20px 0; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .details-table tr:nth-child(odd)  td { background: #f7f7f7; }
        .details-table tr:nth-child(even) td { background: #fff; }
        .details-table td { padding: 8px 12px; border: 1px solid #ddd; }
        .details-table td:first-child { font-weight: bold; width: 35%; color: #444; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { border-top: 1px solid #333; padding-top: 6px; font-size: 12px; color: #555; }
        .print-controls { margin-bottom: 20px; }
        .print-controls button { margin-right: 8px; padding: 6px 16px; cursor: pointer; }
        @media print {
            .print-controls { display: none; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="print-controls">
    <button onclick="window.print()"><b>Print</b></button>
    <button onclick="window.close()">Close</button>
</div>

<div class="page-header">
    <div class="company-name"><?= htmlspecialchars($company['name']) ?></div>
    <div class="company-contact">
        <?= nl2br(htmlspecialchars($company['address'] ?? '')) ?>
        <?php if (!empty($company['phone'])): ?><br>Tel: <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
        <?php if (!empty($company['email'])): ?><br><?= htmlspecialchars($company['email']) ?><?php endif; ?>
    </div>
</div>

<div class="doc-title">Payment / Salary Disburse</div>

<table class="details-table">
    <tr><td>Reference No.</td><td><?= $ref ?></td></tr>
    <tr><td>Date</td><td><?= $date ?></td></tr>
    <tr><td>Recipient Type</td><td><?= ucfirst($recipientType) ?></td></tr>
    <tr><td>Recipient Ref</td><td><?= $recipientRef ?></td></tr>
    <tr><td>Recipient Name</td><td><?= $recipientName ?></td></tr>
    <tr><td>Payment Type</td><td><?= $paymentType ?></td></tr>
    <tr><td>Amount (LKR)</td><td><strong><?= number_format((float)$amount, 2) ?></strong></td></tr>
    <?php if ($remark !== ''): ?>
    <tr><td>Remark</td><td><?= $remark ?></td></tr>
    <?php endif; ?>
</table>

<div class="signatures">
    <div class="signature-box">
        <div style="height:50px;"></div>
        <div class="signature-line">Prepared By</div>
    </div>
    <div class="signature-box">
        <div style="height:50px;"></div>
        <div class="signature-line">Authorized By</div>
    </div>
</div>

</body>
</html>
