<?php
require_once 'server/session.php';
require_once 'server/config.php';
require_once 'server/logger.php';
require_once 'server/general/access_engine.php';
require_login();

$page  = preg_replace('/[^a-z0-9_]/', '', $_GET['page'] ?? '');
$label = ucwords(str_replace('_', ' ', preg_replace('/^stg_/', '', $page)));

$pageTitle   = $label ? $label . ' — Ones n Zeros ERP' : 'Ones n Zeros ERP';
$pageHeading = $label;
$breadcrumbs = $label ? [['label' => $label]] : [];

// Locate module under modules/<section>/<page>/ or modules/<section>/<subsection>/<page>/
$moduleFile    = null;
$moduleSection = null;
$moduleFound   = false;
$_dirs = array_merge(
    glob("modules/*/{$page}", GLOB_ONLYDIR) ?: [],
    glob("modules/*/*/{$page}", GLOB_ONLYDIR) ?: []
);
foreach ($_dirs as $dir) {
    $moduleFound  = true;
    $moduleSection = basename(dirname($dir));
    if (file_exists("{$dir}/{$page}.php")) {
        $moduleFile = "{$dir}/{$page}.php";
    }
    break;
}
// Fetch proper module title from sys_tms_modules
if ($page) {
    $_ch = curl_init(SUPABASE_URL . '/rest/v1/sys_tms_modules?select=name&folder=eq.' . urlencode($page) . '&limit=1');
    curl_setopt_array($_ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ],
    ]);
    $_rows = json_decode(curl_exec($_ch), true) ?? [];
    if (!empty($_rows[0]['name'])) {
        $label       = $_rows[0]['name'];
        $pageTitle   = $label . ' — Ones n Zeros ERP';
        $pageHeading = $label;
        $breadcrumbs = [['label' => $label]];
    }
}

$isReport = $moduleFound && str_contains($moduleSection, 'report');
$isEntry  = $moduleFound && !$isReport;

// Log page view
if ($page) {
    $section = $isReport ? 'Reports' : ($isEntry ? 'Entry' : 'Other');
    log_activity([
        'action_type'    => 'page_view',
        'module'         => $page,
        'module_section' => $section,
        'module_label'   => $label,
        'description'    => "Viewed page: {$label}",
        'metadata'       => ['page' => $page, 'type' => $isReport ? 'report' : ($isEntry ? 'entry' : 'other')],
    ]);
}

$companyInfo = [];
$printUser   = [];
if ($isReport) {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/sys_company_info?select=name,address,phone,email&limit=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ],
    ]);
    $rows = json_decode(curl_exec($ch), true) ?? [];
    $companyInfo = $rows[0] ?? [];

    $u = current_user();
    $printUser = [
        'ref'  => $u['ref'] ?? '',
        'name' => $u['full_name'] ?? '',
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <?php
    $__u = current_user();
    $__logUser = [
        'id'   => $__u['id']   ?? null,
        'ref'  => $__u['ref']  ?? null,
        'name' => $__u['full_name'] ?? '',
        'role' => implode(', ', $__u['roles'] ?? []),
    ];
    // Effective can_view/create/edit/delete/... for the module on this page,
    // so its Vue app can gate New/Edit/Delete/Print buttons — see
    // server/general/access_engine.php.
    $__modulePerms = $page ? access_getEffectiveModule($__u['ref'] ?? '', $page) : null;
  ?>
  <script>window.__LOG_USER__ = <?= json_encode($__logUser) ?>;</script>
  <script>window.__MODULE_PERMS__ = <?= json_encode($__modulePerms) ?>;</script>
  <?php include 'partials/head.php'; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <?php include 'partials/header.php'; ?>
  <?php include 'partials/sidebar.php'; ?>

  <main class="app-main">
    <?php include 'partials/page-header.php'; ?>

    <div class="app-content">
      <div class="container-fluid">

        <?php if ($moduleFile): ?>
          <?php include $moduleFile; ?>
        <?php elseif ($moduleFound): ?>
          <div class="alert alert-info d-flex align-items-center gap-2 mt-3">
            <i class="bi bi-tools fs-5"></i>
            <span><strong><?= htmlspecialchars($pageHeading) ?></strong> — This module is currently under development.</span>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </main>

  <?php include 'partials/footer.php'; ?>

</div>

<?php if ($isReport && $companyInfo): ?>
<div id="print-footer">
  <div class="pf-company">
    <?= htmlspecialchars($companyInfo['name']    ?? '') ?> &nbsp;|&nbsp;
    <?= htmlspecialchars($companyInfo['address'] ?? '') ?> &nbsp;|&nbsp;
    <?= htmlspecialchars($companyInfo['phone']   ?? '') ?> &nbsp;|&nbsp;
    <?= htmlspecialchars($companyInfo['email']   ?? '') ?>
  </div>
  <div class="pf-user">
    Printed by: <?= htmlspecialchars($printUser['ref']) ?> &mdash; <?= htmlspecialchars($printUser['name']) ?>
    &nbsp;|&nbsp; <?= date('Y-m-d H:i') ?>
  </div>
</div>
<?php endif; ?>
<?php include 'partials/scripts.php'; ?>

</body>
</html>
