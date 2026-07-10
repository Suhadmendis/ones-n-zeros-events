<?php
// quotation_entries_print.php

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

$ref   = htmlspecialchars($_GET['ref'] ?? '');
$lines = json_decode($_GET['lines'] ?? '[]', true) ?: [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Quotation — <?= $ref ?></title>
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
    .lines { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .lines th { background: #1a1a2e; color: #fff; padding: 8px 10px; font-size: 11px; text-align: left; }
    .lines td { padding: 7px 10px; font-size: 12px; border-bottom: 1px solid #eee; }
    .lines tr:nth-child(even) td { background: #f9f9f9; }
    .lines .num { text-align: right; }
    .totals { width: 100%; margin-bottom: 32px; }
    .totals table { margin-left: auto; width: 260px; border-collapse: collapse; }
    .totals td { padding: 5px 10px; font-size: 12px; }
    .totals td.label { color: #555; }
    .totals td.val { text-align: right; font-family: 'Courier New', monospace; }
    .totals tr.grand td { border-top: 2px solid #1a1a2e; font-weight: 700; font-size: 13px; padding-top: 8px; }
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
      <h2>Quotation</h2>
      <div class="ref">Ref: <?= $ref ?><?php if (!empty($_GET['revision_no']) && $_GET['revision_no'] !== '0'): ?> &nbsp;|&nbsp; Rev. <?= htmlspecialchars($_GET['revision_no']) ?><?php endif; ?></div>
    </div>
    <table class="details">
      <tr><th>Quotation No.</th><td><?= $ref ?></td></tr>
      <tr><th>Customer</th><td><?= htmlspecialchars($_GET['customer_name'] ?? '') ?></td></tr>
      <tr><th>Contact Person</th><td><?= htmlspecialchars($_GET['contact_person'] ?? '') ?></td></tr>
      <tr><th>Subject / Title</th><td><?= htmlspecialchars($_GET['subject'] ?? '') ?></td></tr>
      <tr><th>Customer Reference</th><td><?= htmlspecialchars($_GET['customer_reference'] ?? '') ?></td></tr>
      <tr><th>Status</th><td><?= htmlspecialchars($_GET['quotation_status_name'] ?? '') ?></td></tr>
      <tr><th>Quotation Date</th><td><?= htmlspecialchars($_GET['quotation_date'] ?? '') ?></td></tr>
      <tr><th>Valid Until</th><td><?= htmlspecialchars($_GET['valid_until'] ?? '') ?></td></tr>
      <tr><th>Currency</th><td><?= htmlspecialchars($_GET['currency_name'] ?? '') ?></td></tr>
      <tr><th>Salesperson / Prepared By</th><td><?= htmlspecialchars($_GET['salesperson_name'] ?? '') ?></td></tr>
      <tr><th>Price List</th><td><?= htmlspecialchars($_GET['price_list'] ?? '') ?></td></tr>
      <tr><th>Payment Terms</th><td><?= htmlspecialchars($_GET['payment_terms'] ?? '') ?></td></tr>
      <tr><th>Delivery / Completion Period</th><td><?= htmlspecialchars($_GET['delivery_period'] ?? '') ?></td></tr>
      <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($_GET['notes'] ?? '')) ?></td></tr>
      <tr><th>Terms &amp; Conditions</th><td><?= nl2br(htmlspecialchars($_GET['terms_conditions'] ?? '')) ?></td></tr>
    </table>
    <?php if ($lines): ?>
    <table class="lines">
      <thead>
        <tr>
          <th>#</th><th>Item / Service</th><th>Description</th><th>Qty</th><th>Unit</th>
          <th class="num">Unit Price</th><th class="num">Discount</th><th class="num">Tax</th><th class="num">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lines as $i => $line): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($line['item_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($line['description'] ?? '') ?></td>
          <td><?= htmlspecialchars($line['qty'] ?? '') ?></td>
          <td><?= htmlspecialchars($line['unit'] ?? '') ?></td>
          <td class="num"><?= htmlspecialchars(number_format((float)($line['unit_price'] ?? 0), 2)) ?></td>
          <td class="num"><?= htmlspecialchars(number_format((float)($line['discount'] ?? 0), 2)) ?></td>
          <td class="num"><?= htmlspecialchars(number_format((float)($line['tax'] ?? 0), 2)) ?></td>
          <td class="num"><?= htmlspecialchars(number_format((float)($line['amount'] ?? 0), 2)) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <div class="totals">
      <table>
        <tr><td class="label">Subtotal</td><td class="val"><?= htmlspecialchars(number_format((float)($_GET['subtotal'] ?? 0), 2)) ?></td></tr>
        <tr><td class="label">Discount</td><td class="val"><?= htmlspecialchars(number_format((float)($_GET['discount'] ?? 0), 2)) ?></td></tr>
        <tr><td class="label">Tax</td><td class="val"><?= htmlspecialchars(number_format((float)($_GET['tax'] ?? 0), 2)) ?></td></tr>
        <tr class="grand"><td class="label">Total Amount</td><td class="val"><?= htmlspecialchars(number_format((float)($_GET['total_amount'] ?? 0), 2)) ?></td></tr>
      </table>
    </div>
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
