<?php
require_once __DIR__ . '/../../../../server/supabase.php';

// Fetch company info
$ch = curl_init(SUPABASE_URL . SB_API . 'sys_company_info?select=*&limit=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json'
]);
$companyRows = json_decode(curl_exec($ch), true) ?? [];
$company = $companyRows[0] ?? ['name' => 'Ones n Zeros', 'address' => '', 'phone' => '', 'email' => ''];

// GET params
$ref             = htmlspecialchars($_GET['ref'] ?? '');
$date            = htmlspecialchars($_GET['date'] ?? '');
$recipientType   = htmlspecialchars($_GET['recipient_type'] ?? '');
$recipientRef    = htmlspecialchars($_GET['recipient_ref'] ?? '');
$recipientName   = htmlspecialchars($_GET['recipient_name'] ?? '');
$principalAmount = htmlspecialchars($_GET['principal_amount'] ?? '');
$recoveredAmount = htmlspecialchars($_GET['recovered_amount'] ?? '0');
$status          = htmlspecialchars($_GET['status'] ?? '');

$balance = (float)$principalAmount - (float)$recoveredAmount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan <?= $ref ?> | Ones n Zeros ERP</title>
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
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success   { background: #d4edda; color: #155724; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
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

<div class="doc-title">Loan Record</div>

<table class="details-table">
    <tr><td>Reference No.</td><td><?= $ref ?></td></tr>
    <tr><td>Date</td><td><?= $date ?></td></tr>
    <tr><td>Recipient Type</td><td><?= ucfirst($recipientType) ?></td></tr>
    <tr><td>Recipient Ref</td><td><?= $recipientRef ?></td></tr>
    <tr><td>Recipient Name</td><td><?= $recipientName ?></td></tr>
    <tr><td>Principal Amount (LKR)</td><td><?= number_format((float)$principalAmount, 2) ?></td></tr>
    <tr><td>Recovered Amount (LKR)</td><td><?= number_format((float)$recoveredAmount, 2) ?></td></tr>
    <tr><td>Outstanding Balance (LKR)</td><td><strong><?= number_format($balance, 2) ?></strong></td></tr>
    <tr>
        <td>Status</td>
        <td>
            <span class="badge <?= $status === 'active' ? 'badge-success' : 'badge-secondary' ?>">
                <?= ucfirst($status) ?>
            </span>
        </td>
    </tr>
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
