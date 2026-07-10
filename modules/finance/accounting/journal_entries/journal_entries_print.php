<?php
// modules/finance/journal_entries/journal_entries_print.php — JE print view

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

$ref           = htmlspecialchars($_GET['ref']           ?? '');
$journal_date  = htmlspecialchars($_GET['journal_date']  ?? '');
$period        = htmlspecialchars($_GET['period']        ?? '');
$description   = htmlspecialchars($_GET['description']   ?? '');
$reference_doc = htmlspecialchars($_GET['reference_doc'] ?? '');
$status        = htmlspecialchars($_GET['status']        ?? '');
$total_debit   = (float) ($_GET['total_debit']           ?? 0);
$total_credit  = (float) ($_GET['total_credit']          ?? 0);
$lines         = json_decode($_GET['lines'] ?? '[]', true) ?: [];

$status_labels = ['draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed'];
$status_label  = $status_labels[$status] ?? $status;
$fmt           = fn($v) => number_format((float)$v, 2);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Journal Entry — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #000; background: #f4f4f4; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 16mm 18mm; display: flex; flex-direction: column; box-shadow: 0 0 12px rgba(0,0,0,0.15); }
    .letterhead { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 3px solid #1a1a2e; margin-bottom: 24px; }
    .letterhead .company-name { font-size: 22px; font-weight: 700; color: #1a1a2e; }
    .letterhead .company-contact { text-align: right; font-size: 11px; color: #444; line-height: 1.7; }
    .doc-title { text-align: center; margin-bottom: 18px; }
    .doc-title h2 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; border-bottom: 1px solid #ccc; display: inline-block; padding-bottom: 4px; }
    .doc-title .ref { font-size: 12px; color: #555; margin-top: 4px; }
    .meta { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .meta td { padding: 5px 10px; font-size: 12px; }
    .meta td:first-child { font-weight: 600; width: 20%; color: #333; }
    .lines { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .lines th { background: #1a1a2e; color: #fff; padding: 8px 10px; font-size: 11px; text-align: left; }
    .lines th.num, .lines td.num { text-align: right; }
    .lines td { padding: 7px 10px; font-size: 12px; border-bottom: 1px solid #eee; }
    .lines tr:nth-child(even) td { background: #f9f9f9; }
    .lines tfoot td { font-weight: 700; border-top: 2px solid #1a1a2e; padding: 8px 10px; }
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
      <h2>Journal Entry</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>

    <table class="meta">
      <tr><td>Date</td><td><?= $journal_date ?></td><td>Period</td><td><?= $period ?></td></tr>
      <tr><td>Description</td><td colspan="3"><?= $description ?></td></tr>
      <?php if ($reference_doc): ?><tr><td>Reference Doc</td><td colspan="3"><?= $reference_doc ?></td></tr><?php endif; ?>
      <tr><td>Status</td><td><?= $status_label ?></td><td></td><td></td></tr>
    </table>

    <table class="lines">
      <thead>
        <tr>
          <th>#</th>
          <th>Code</th>
          <th>Account Name</th>
          <th>Description</th>
          <th class="num">Debit</th>
          <th class="num">Credit</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lines as $i => $line): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($line['account_code'] ?? '') ?></td>
          <td><?= htmlspecialchars($line['account_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($line['description']  ?? '') ?></td>
          <td class="num"><?= $fmt($line['debit_amount']  ?? 0) ?></td>
          <td class="num"><?= $fmt($line['credit_amount'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align:right">Total</td>
          <td class="num"><?= $fmt($total_debit) ?></td>
          <td class="num"><?= $fmt($total_credit) ?></td>
        </tr>
      </tfoot>
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
