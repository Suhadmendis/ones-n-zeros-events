<?php $pageTitle = $pageTitle ?? 'Ones n Zeros ERP'; ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= htmlspecialchars($pageTitle) ?></title>

<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
<meta name="color-scheme" content="light dark" />
<meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
<meta name="author" content="Ones n Zeros" />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous" media="print" onload="this.media='all'" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
      crossorigin="anonymous" />
<?php $__adminlteCssV = @filemtime(dirname(__DIR__) . '/css/adminlte.css'); ?>
<link rel="preload" href="/css/adminlte.css?v=<?= $__adminlteCssV ?>" as="style" />
<link rel="stylesheet" href="/css/adminlte.css?v=<?= $__adminlteCssV ?>" />

<?php if (!defined('SUPABASE_URL')) require_once dirname(__DIR__) . '/server/config.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js" crossorigin="anonymous"></script>
<script>
  window.__SUPABASE_URL__ = '<?= SUPABASE_URL ?>';
  window.__SUPABASE_KEY__ = '<?= SUPABASE_PUBLISHABLE ?>';
</script>
<script src="/js/supabase-client.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
<script src="/js/flatpickr-vue-directive.js"></script>
<script src="/js/report-utils.js"></script>
<script src="/js/module-help.js"></script>
<script src="/js/activity-logger.js"></script>
<style>
#print-footer { display: none; }

@media print {
  /* ── Hide UI chrome ─────────────────────────────────── */
  .app-header, .app-sidebar, .app-footer    { display: none !important; }
  .app-main                                  { margin-left: 0 !important; }
  .app-content-header                        { display: none !important; }
  .app-content .card.mb-3                    { display: none !important; }
  .report-export-btns                        { display: none !important; }
  .app-content                               { padding: 0 !important; }
  .app-content .container-fluid             { padding: 0 !important; }

  /* ── Clean card chrome ──────────────────────────────── */
  .card       { border: none !important; box-shadow: none !important; }
  .card-body  { padding: 0 !important; }
  .card-header {
    background: none !important;
    border-bottom: 1px solid #ccc !important;
    padding: 6px 0 4px !important;
    font-size: 10pt;
  }

  /* ── Professional table ─────────────────────────────── */
  thead.table-dark {
    --bs-table-bg:           #fff;
    --bs-table-color:        #000;
    --bs-table-border-color: #555;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  thead.table-dark th {
    border-bottom: 2px solid #000 !important;
    font-weight: 700;
  }

  /* Status badges → plain text */
  .badge {
    background: none !important;
    background-color: transparent !important;
    border: none !important;
    color: inherit !important;
    padding: 0 !important;
    font-size: inherit !important;
    font-weight: inherit !important;
    border-radius: 0 !important;
  }

  /* Remove row colour highlights */
  .table > tbody > tr[class*="table-"] {
    --bs-table-bg:    transparent !important;
    --bs-table-color: #000 !important;
  }
  .table > tbody > tr[class*="table-"] > td,
  .table > tbody > tr[class*="table-"] > th {
    background-color: transparent !important;
    color: #000 !important;
  }

  /* ── Page setup: zero @page margin removes browser URL/title headers ── */
  @page { margin: 0; }
  body  {
    padding: 1.5cm 1.5cm 2.5cm 1.5cm !important;
    margin: 0 !important;
  }

  /* ── Company / user footer — repeats on every page ──── */
  #print-footer {
    display: block !important;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    border-top: 1px solid #999;
    background: #fff !important;
    padding: 5px 1.5cm 5px;
    font-size: 8pt;
    color: #444;
    line-height: 1.6;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  #print-footer .pf-company { font-weight: 600; color: #111; }
  #print-footer .pf-user    { color: #666; }
}
</style>
