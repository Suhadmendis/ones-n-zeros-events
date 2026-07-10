<?php
require_once __DIR__ . '/../../../../server/supabase.php';

$rows = supabase_get(SB_API . 'sys_company_info?select=*&limit=1');
$company = $rows[0] ?? [];

$ref             = htmlspecialchars($_GET['ref'] ?? '');
$date            = htmlspecialchars($_GET['date'] ?? '');
$recipient_type  = htmlspecialchars($_GET['recipient_type'] ?? '');
$recipient_ref   = htmlspecialchars($_GET['recipient_ref'] ?? '');
$recipient_name  = htmlspecialchars($_GET['recipient_name'] ?? '');
$amount          = htmlspecialchars($_GET['amount'] ?? '');
$reason          = htmlspecialchars($_GET['reason'] ?? '');

$company_name    = htmlspecialchars($company['name'] ?? 'Ones n Zeros ERP');
$company_address = htmlspecialchars($company['address'] ?? '');
$company_phone   = htmlspecialchars($company['phone'] ?? '');
$company_email   = htmlspecialchars($company['email'] ?? '');

$formatted_amount       = $amount !== '' ? 'LKR ' . number_format((float)$amount, 2) : '—';
$formatted_date         = $date !== '' ? date('d M Y', strtotime($date)) : '—';
$formatted_recipient    = $recipient_type ? ucfirst($recipient_type) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deduction — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; background: #fff; padding: 30px; }
    .print-btn-bar { display: flex; gap: 10px; margin-bottom: 20px; }
    .btn { padding: 6px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
    .btn-primary { background: #0d6efd; color: #fff; }
    .btn-secondary { background: #6c757d; color: #fff; }
    .letterhead { border-bottom: 3px solid #0d6efd; padding-bottom: 14px; margin-bottom: 20px; }
    .letterhead h1 { font-size: 22px; color: #0d6efd; margin-bottom: 4px; }
    .letterhead p { font-size: 12px; color: #555; line-height: 1.5; }
    .doc-title { font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; color: #333; }
    table.details { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    table.details th, table.details td { border: 1px solid #dee2e6; padding: 8px 12px; }
    table.details th { background: #f8f9fa; width: 30%; font-weight: 600; color: #444; }
    table.details td { color: #222; }
    .amount-cell { font-weight: bold; font-size: 15px; color: #842029; }
    .signatures { display: flex; justify-content: space-between; margin-top: 48px; }
    .sig-box { text-align: center; width: 30%; }
    .sig-box .sig-line { border-top: 1px solid #333; margin-bottom: 6px; padding-top: 6px; font-size: 12px; color: #555; }
    .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #dee2e6; padding-top: 10px; }
    @media print {
      .print-btn-bar { display: none !important; }
      body { padding: 15px; }
    }
  </style>
</head>
<body>

<div class="print-btn-bar">
  <button class="btn btn-primary" onclick="window.print()"><span>&#128438;</span> Print</button>
  <button class="btn btn-secondary" onclick="window.close()">&#10005; Close</button>
</div>

<div class="letterhead">
  <h1><?= $company_name ?></h1>
  <p>
    <?php if ($company_address): ?><?= $company_address ?><br><?php endif; ?>
    <?php if ($company_phone): ?>Tel: <?= $company_phone ?><?php endif; ?>
    <?php if ($company_email): ?> &nbsp;|&nbsp; <?= $company_email ?><?php endif; ?>
  </p>
</div>

<div class="doc-title">Deduction Voucher</div>

<table class="details">
  <tr>
    <th>Reference No.</th>
    <td><strong><?= $ref ?></strong></td>
  </tr>
  <tr>
    <th>Date</th>
    <td><?= $formatted_date ?></td>
  </tr>
  <tr>
    <th>Recipient Type</th>
    <td><?= $formatted_recipient ?></td>
  </tr>
  <tr>
    <th>Recipient Ref</th>
    <td><?= $recipient_ref ?: '—' ?></td>
  </tr>
  <tr>
    <th>Recipient Name</th>
    <td><?= $recipient_name ?: '—' ?></td>
  </tr>
  <tr>
    <th>Amount Deducted</th>
    <td class="amount-cell"><?= $formatted_amount ?></td>
  </tr>
  <tr>
    <th>Reason</th>
    <td><?= $reason ?: '—' ?></td>
  </tr>
</table>

<div class="signatures">
  <div class="sig-box">
    <div class="sig-line">Prepared By</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">Acknowledged By</div>
  </div>
  <div class="sig-box">
    <div class="sig-line">Approved By</div>
  </div>
</div>

<div class="footer">
  <?= $company_name ?> &mdash; Deduction Voucher &mdash; <?= $ref ?> &mdash; Printed: <?= date('d M Y H:i') ?>
</div>

</body>
</html>
