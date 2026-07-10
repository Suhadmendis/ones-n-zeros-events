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

function qp_field(string $key): string {
    return htmlspecialchars($_GET[$key] ?? '');
}
function qp_money(string $key): string {
    return htmlspecialchars(number_format((float) ($_GET[$key] ?? 0), 2));
}
function qp_datetime(string $key): string {
    $raw = $_GET[$key] ?? '';
    if ($raw === '') return '';
    $ts = strtotime($raw);
    return $ts === false ? htmlspecialchars($raw) : date('j M Y, g:i A', $ts);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quotation — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }

    :root {
      --page-width: 210mm;
      --page-margin-bottom: 12mm;
      --paper-bg: #d4f3f5;
      --ink: #2b2b2b;
      --muted: #4e4e4e;
      --accent: #14a79f;
      --grid: #16b2ac;
      --line-dark: #2c2c2c;
    }

    body {
      background: #e8eef0;
      color: var(--ink);
      font-family: Arial, Helvetica, sans-serif;
      line-height: 1.25;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .actions { text-align: center; padding: 12px; }
    .actions button { padding: 7px 20px; font-size: 13px; cursor: pointer; margin: 0 4px; border: 1px solid #0d8079; border-radius: 4px; background: #f5f5f5; }
    .actions button.btn-print { background: var(--accent); color: #fff; border-color: var(--accent); }

    .quotation-document {
      width: var(--page-width);
      margin: 0 auto;
      background: var(--paper-bg);
      box-shadow: 0 10px 26px rgba(0, 0, 0, 0.14);
    }

    /* ── Hero ─────────────────────────────────────────────────────────── */
    .document-hero {
      height: 38mm;
      position: relative;
      background:
        linear-gradient(rgba(0, 0, 0, 0.42), rgba(0, 0, 0, 0.45)),
        url("/assets/img/quotations/quotation_print_top_bg_cover.jpg") center / cover no-repeat;
    }

    .document-hero::after {
      content: "";
      position: absolute;
      inset: auto 0 0 0;
      height: 10mm;
      background: linear-gradient(to bottom, rgba(212, 243, 245, 0), rgba(212, 243, 245, 1));
    }

    .hero-overlay {
      position: relative;
      z-index: 1;
      padding: 12mm 12mm 0;
      text-align: center;
      color: #fff;
    }

    .hero-overlay h1 {
      margin: 0;
      font-size: 8.5mm;
      line-height: 0.95;
      letter-spacing: -0.3mm;
      font-weight: 700;
    }

    .hero-overlay p {
      margin: 0.8mm 0 0;
      font-family: Georgia, "Times New Roman", serif;
      font-style: italic;
      font-size: 3.9mm;
      line-height: 1;
    }

    /* ── Body shell ───────────────────────────────────────────────────── */
    .document-shell {
      padding: 2mm 10mm var(--page-margin-bottom);
    }

    .document-meta {
      margin: 0 0.5mm 4mm;
    }

    .meta-group {
      display: grid;
      gap: 0.8mm;
    }

    .meta-group-secondary {
      margin-top: 4mm;
    }

    .meta-row {
      display: grid;
      grid-template-columns: 41mm 1fr;
      column-gap: 3mm;
      align-items: start;
      padding: 0.4mm 0;
    }

    .meta-label,
    .meta-value {
      font-size: 3.1mm;
      font-weight: 700;
      color: #424242;
    }

    .meta-value {
      overflow-wrap: anywhere;
    }

    /* ── Item table ───────────────────────────────────────────────────── */
    .quotation-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      border: 0.5mm solid var(--grid);
    }

    .quotation-table col.col-item { width: 45.3%; }
    .quotation-table col.col-qty { width: 6.4%; }
    .quotation-table col.col-amount { width: 16.4%; }
    .quotation-table col.col-flora { width: 31.9%; }

    .quotation-table thead { display: table-header-group; }
    .quotation-table tfoot { display: table-footer-group; }

    .quotation-table thead th {
      background: var(--accent);
      color: #fff;
      font-size: 3.6mm;
      line-height: 1.08;
      font-weight: 700;
      text-align: left;
      padding: 2mm 2mm;
      border-right: 0.5mm solid var(--grid);
      vertical-align: top;
    }

    .quotation-table thead th:nth-child(2),
    .quotation-table thead th:nth-child(3),
    .quotation-table thead th:nth-child(4) {
      text-align: center;
    }

    .quotation-table thead th:last-child { border-right: 0; }

    /* No forced break points — rows flow naturally and however many fit
       on a page do. avoid-page just stops a single row being sliced in
       half at a page boundary; a row that doesn't fully fit gets pushed
       whole onto the next page instead. A very tall row (long notes,
       a photo) can end up alone on its page; a handful of short rows
       will happily share one. */
    .quotation-table tbody tr {
      break-inside: avoid-page;
      page-break-inside: avoid;
    }

    .quotation-table td {
      background: #fff;
      border-top: 0.5mm solid var(--grid);
      border-right: 0.5mm solid var(--grid);
      vertical-align: top;
      padding: 2.4mm 2mm;
      overflow-wrap: anywhere;
      break-inside: avoid-page;
      page-break-inside: avoid;
    }

    .item-block {
      break-inside: avoid-page;
      page-break-inside: avoid;
    }

    .quotation-table td:last-child { border-right: 0; }

    .item-block h2 {
      margin: 0 0 1.2mm;
      font-size: 3.7mm;
      line-height: 1.1;
      font-weight: 700;
      break-after: avoid;
      page-break-after: avoid;
    }

    .item-points {
      margin: 0 0 1.6mm;
      padding: 0;
      list-style: none;
    }

    .item-points li {
      margin: 0 0 0.6mm;
      font-size: 3mm;
      line-height: 1.14;
      font-weight: 600;
      color: var(--muted);
      orphans: 2;
      widows: 2;
    }

    .item-points li::before {
      content: "- ";
    }

    .item-photo {
      display: block;
      margin-top: 2mm;
      width: 88%;
      max-height: 30mm;
      object-fit: cover;
      border: 0.35mm solid rgba(60, 60, 60, 0.15);
    }

    .qty,
    .money,
    .flora {
      color: #4f4f4f;
    }

    .qty {
      font-size: 4.2mm;
      text-align: center;
      white-space: nowrap;
    }

    .money {
      text-align: right;
      white-space: nowrap;
      vertical-align: top;
      padding-top: 2.4mm;
      padding-right: 2.8mm;
    }

    .money-single {
      font-size: 4.5mm;
      display: block;
    }

    .money-calc {
      font-size: 3.7mm;
      line-height: 1.15;
      display: block;
    }

    .money-total {
      margin-top: 0.5mm;
      font-size: 5mm;
      line-height: 1.05;
      display: block;
    }

    .flora {
      text-align: right;
      font-size: 3.2mm;
      line-height: 1.24;
      font-weight: 700;
      padding-right: 2.4mm;
    }

    .flora div {
      margin: 0;
    }

    /* ── Summary ──────────────────────────────────────────────────────── */
    .document-summary {
      margin-top: 5mm;
    }

    .summary-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border: 0.5mm solid var(--line-dark);
    }

    .summary-table th,
    .summary-table td {
      padding: 3.2mm 3.6mm;
      font-size: 3.1mm;
      font-weight: 700;
      border-top: 0.5mm solid var(--line-dark);
    }

    .summary-table tr:first-child th,
    .summary-table tr:first-child td {
      border-top: 0;
    }

    .summary-table th {
      text-align: left;
      border-right: 0.5mm solid var(--line-dark);
      width: 60%;
    }

    .summary-table td {
      text-align: right;
      white-space: nowrap;
    }

    .summary-total th,
    .summary-total td {
      font-size: 3.6mm;
    }

    /* ── Text blocks (payment terms / delivery / notes / T&C) ───────────── */
    .terms-block {
      margin-top: 5mm;
      break-inside: avoid;
      page-break-inside: avoid;
    }

    .terms-block h3 {
      margin: 0 0 2mm;
      text-align: center;
      font-size: 5mm;
      color: #444;
      break-after: avoid;
      page-break-after: avoid;
    }

    .terms-block p {
      margin: 0;
      font-size: 3.4mm;
      line-height: 1.5;
      font-weight: 600;
      color: #4a4a4a;
      text-align: center;
      orphans: 2;
      widows: 2;
    }

    .terms-block p.align-left {
      text-align: left;
    }

    /* Terms and Conditions: left-aligned, lettered (A, B, C…) points. */
    .terms-block ol.lettered-list {
      margin: 0;
      padding-left: 6mm;
      text-align: left;
      list-style-type: upper-alpha;
    }

    .terms-block ol.lettered-list li {
      margin: 0 0 1mm;
      font-size: 3.4mm;
      line-height: 1.5;
      font-weight: 600;
      color: #4a4a4a;
      orphans: 2;
      widows: 2;
    }

    .terms-block ol.lettered-list li:last-child {
      margin-bottom: 0;
    }

    /* ── Closing / footer ─────────────────────────────────────────────── */
    .closing-block {
      margin-top: 7mm;
      text-align: center;
    }

    .closing-block p {
      margin: 0;
      font-size: 3.6mm;
      line-height: 1.7;
      color: #5a5a5a;
      orphans: 3;
      widows: 3;
    }

    .document-signoff {
      margin-top: 5mm;
      text-align: center;
      break-inside: avoid;
      page-break-inside: avoid;
    }

    .tagline {
      font-family: Georgia, "Times New Roman", serif;
      font-size: 3.9mm;
      font-style: italic;
      color: #555;
    }

    .brand-name {
      font-size: 3.5mm;
      font-weight: 700;
      color: #4c4c4c;
    }

    /* @page margin is left at 0 on purpose — Chrome/Safari never paint the
       @page margin box, even when html/body have a background, so any
       margin declared there prints as a blank white border on every page.
       The 10mm breathing room is recreated as padding on .quotation-document
       instead, which IS paintable, so the teal background reaches every
       physical page edge. */
    @page { size: A4; margin: 0; }

    @media screen {
      body { padding: 18px 0; }
    }

    @media print {
      html, body {
        background: var(--paper-bg);
      }

      body { padding: 0; }

      .actions { display: none; }

      .quotation-document {
        /* @page has no margin, so this box fills the whole physical page;
           the padding here recreates the old 10mm margin while keeping
           the teal background painted all the way to the page edge.
           box-decoration-break: clone re-applies that padding (and the
           background) on every page fragment — without it, top/bottom
           padding would only land on the very first/last printed page,
           leaving continuation pages with content flush against the
           physical top edge. */
        width: auto;
        margin: 0;
        box-shadow: none;
        background: var(--paper-bg);
        padding: 10mm;
        -webkit-box-decoration-break: clone;
        box-decoration-break: clone;
      }

      /* table-row-group (not table-header-group) so the header renders
         once, only on the first page, instead of repeating on every
         page the table spans across. */
      .quotation-table thead { display: table-row-group; }

      .quotation-table tr,
      .document-signoff {
        break-inside: avoid-page;
        page-break-inside: avoid;
      }

      .summary-table,
      .summary-table tbody,
      .summary-table tr {
        break-inside: avoid-page;
        page-break-inside: avoid;
      }

      .terms-block h3,
      .closing-block p {
        break-inside: auto;
        page-break-inside: auto;
      }
    }
  </style>
</head>
<body>
  <div class="actions">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
  </div>

  <main class="quotation-document">
    <header class="document-hero">
      <div class="hero-overlay">
        <h1><?= htmlspecialchars($company['name'] ?? '') ?></h1>
        <p>Quotation</p>
      </div>
    </header>

    <section class="document-shell">
      <section class="document-meta">
        <div class="meta-group">
          <div class="meta-row"><span class="meta-label">From:</span><span class="meta-value"><?= htmlspecialchars($company['name'] ?? '') ?></span></div>
          <?php if (!empty($company['address'])): ?>
          <div class="meta-row"><span class="meta-label">Address:</span><span class="meta-value"><?= nl2br(htmlspecialchars($company['address'])) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($company['phone'])): ?>
          <div class="meta-row"><span class="meta-label">Phone:</span><span class="meta-value"><?= htmlspecialchars($company['phone']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($company['email'])): ?>
          <div class="meta-row"><span class="meta-label">Email:</span><span class="meta-value"><?= htmlspecialchars($company['email']) ?></span></div>
          <?php endif; ?>
        </div>

        <div class="meta-group meta-group-secondary">
          <div class="meta-row"><span class="meta-label">Quotation No:</span><span class="meta-value"><?= $ref ?><?php if (!empty($_GET['revision_no']) && $_GET['revision_no'] !== '0'): ?> &nbsp;|&nbsp; Rev. <?= qp_field('revision_no') ?><?php endif; ?></span></div>
          <?php if (!empty($_GET['customer_name'])): ?>
          <div class="meta-row"><span class="meta-label">Customer:</span><span class="meta-value"><?= qp_field('customer_name') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['contact_person'])): ?>
          <div class="meta-row"><span class="meta-label">Attention:</span><span class="meta-value"><?= qp_field('contact_person') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['subject'])): ?>
          <div class="meta-row"><span class="meta-label">Subject:</span><span class="meta-value"><?= qp_field('subject') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['customer_reference'])): ?>
          <div class="meta-row"><span class="meta-label">Customer Ref:</span><span class="meta-value"><?= qp_field('customer_reference') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['quotation_status_name'])): ?>
          <div class="meta-row"><span class="meta-label">Status:</span><span class="meta-value"><?= qp_field('quotation_status_name') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['quotation_date'])): ?>
          <div class="meta-row"><span class="meta-label">Quotation Date:</span><span class="meta-value"><?= qp_datetime('quotation_date') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['valid_until'])): ?>
          <div class="meta-row"><span class="meta-label">Valid Until:</span><span class="meta-value"><?= qp_datetime('valid_until') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['currency_name'])): ?>
          <div class="meta-row"><span class="meta-label">Currency:</span><span class="meta-value"><?= qp_field('currency_name') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['salesperson_name'])): ?>
          <div class="meta-row"><span class="meta-label">Salesperson:</span><span class="meta-value"><?= qp_field('salesperson_name') ?></span></div>
          <?php endif; ?>
          <?php if (!empty($_GET['price_list'])): ?>
          <div class="meta-row"><span class="meta-label">Price List:</span><span class="meta-value"><?= qp_field('price_list') ?></span></div>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($lines): ?>
      <table class="quotation-table">
        <colgroup>
          <col class="col-item">
          <col class="col-qty">
          <col class="col-amount">
          <col class="col-flora">
        </colgroup>
        <thead>
          <tr>
            <th scope="col">Arrangement</th>
            <th scope="col">Qty</th>
            <th scope="col">Structure and<br>Floral cost</th>
            <th scope="col">Fresh varieties<br>and Foliage</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lines as $line):
            $qty        = (float) ($line['qty'] ?? 0);
            $unit_price = (float) ($line['unit_price'] ?? 0);
            $amount     = (float) ($line['amount'] ?? ($qty * $unit_price));
            $isPlain    = ($qty == 1);
            $flora      = array_filter(array_map('trim', explode("\n", (string) ($line['flora_notes'] ?? ''))));
          ?>
          <tr class="line-item">
            <td>
              <div class="item-block">
                <h2><?= htmlspecialchars($line['item_name'] ?? '') ?></h2>
                <?php if (!empty($line['description'])): ?>
                <ul class="item-points">
                  <?php foreach (array_filter(array_map('trim', explode("\n", $line['description']))) as $point): ?>
                  <li><?= htmlspecialchars($point) ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php if (!empty($line['image'])): ?>
                <img class="item-photo" src="<?= htmlspecialchars($line['image']) ?>" alt="">
                <?php endif; ?>
              </div>
            </td>
            <td class="qty"><?= htmlspecialchars(str_pad((string) ($line['qty'] ?? ''), 2, '0', STR_PAD_LEFT)) ?></td>
            <td class="money">
              <?php if ($isPlain): ?>
              <div class="money-single"><?= number_format($amount, 2) ?></div>
              <?php else: ?>
              <div class="money-calc"><?= htmlspecialchars($line['qty'] ?? 0) ?> x <?= number_format($unit_price, 2) ?></div>
              <div class="money-total"><?= number_format($amount, 2) ?></div>
              <?php endif; ?>
            </td>
            <td class="flora">
              <?php if ($flora): ?>
                <?php foreach ($flora as $item): ?>
                <div><?= htmlspecialchars($item) ?></div>
                <?php endforeach; ?>
              <?php else: ?>
                <div>-</div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <section class="document-summary" aria-labelledby="summary-heading">
        <table class="summary-table">
          <tbody>
            <tr><th scope="row">Subtotal</th><td><?= qp_money('subtotal') ?></td></tr>
            <?php if (!empty($_GET['discount']) && (float) $_GET['discount'] != 0): ?>
            <tr><th scope="row">Discount</th><td><?= qp_money('discount') ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($_GET['tax']) && (float) $_GET['tax'] != 0): ?>
            <tr><th scope="row">Tax</th><td><?= qp_money('tax') ?></td></tr>
            <?php endif; ?>
            <tr class="summary-total"><th scope="row">Total Amount</th><td><?= qp_money('total_amount') ?></td></tr>
          </tbody>
        </table>
      </section>

      <?php if (!empty($_GET['payment_terms'])): ?>
      <section class="terms-block">
        <h3>Payment Terms</h3>
        <p><?= nl2br(qp_field('payment_terms')) ?></p>
      </section>
      <?php endif; ?>

      <?php if (!empty($_GET['delivery_period'])): ?>
      <section class="terms-block">
        <h3>Delivery / Completion Period</h3>
        <p><?= nl2br(qp_field('delivery_period')) ?></p>
      </section>
      <?php endif; ?>

      <?php if (!empty($_GET['notes'])): ?>
      <section class="terms-block">
        <h3>Notes</h3>
        <p class="align-left"><?= nl2br(qp_field('notes')) ?></p>
      </section>
      <?php endif; ?>

      <?php if (!empty($_GET['terms_conditions'])): ?>
      <section class="terms-block">
        <h3>Terms and Conditions</h3>
        <ol class="lettered-list">
          <?php foreach (array_filter(array_map('trim', explode("\n", $_GET['terms_conditions']))) as $point): ?>
          <li><?= htmlspecialchars($point) ?></li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <section class="closing-block">
        <p>
          We would be delighted to hear from you! If you have any questions or need further clarification, we understand that
unexpected circumstances can arise if you require any flexibility with arranging payments. We
'
re here to help find a solution
that works for you. Please don
't hesitate to contact us at +9476 857 3000. We
'
re passionate about serving clients of your
caliber and would love the opportunity to make your event a truly unforgettable experience.
          <!-- <?php if (!empty($company['phone'])): ?> at <?= htmlspecialchars($company['phone']) ?><?php endif; ?>. -->
          </p>
      </section>

      <footer class="document-signoff">
        <div class="tagline">Thank you for your business.</div>
        <div class="brand-name"><?= htmlspecialchars($company['name'] ?? '') ?></div>
      </footer>
    </section>
  </main>
</body>
</html>
