<?php
require_once __DIR__ . '/../server/session.php';
require_once __DIR__ . '/../server/general/module_system.php';

require_login();

$pageTitle   = 'Module Status — Ones n Zeros ERP';
$pageHeading = 'Module Status';
$breadcrumbs = [['label' => 'Module Status']];
$isReport    = false;

$modules = getModules(null, true);

$root = realpath(__DIR__ . '/..');
foreach ($modules as &$m) {
    $fp   = $m['folder_path'] ?? '';
    $sn   = $m['system_name'];
    $base = $fp ? "{$root}/{$fp}/{$sn}" : '';
    $m['files'] = [
        'php'    => $base && file_exists("{$base}.php"),
        'js'     => $base && file_exists("{$base}.js"),
        'data'   => $base && file_exists("{$base}_data.php"),
        'search' => $base && file_exists("{$base}_search.php"),
        'srchjs' => $base && file_exists("{$base}_search.js"),
        'print'  => $base && file_exists("{$base}_print.php"),
    ];
    $m['file_count'] = count(array_filter($m['files']));
}
unset($m);

$sections = [];
foreach ($modules as $m) {
    $sections[$m['section']][] = $m;
}

$totalModules  = count($modules);
$totalComplete = count(array_filter($modules, fn($m) => $m['file_count'] === 6));
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
  ?>
  <script>window.__LOG_USER__ = <?= json_encode($__logUser) ?>;</script>
  <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <?php include __DIR__ . '/../partials/header.php'; ?>
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <main class="app-main">
    <?php include __DIR__ . '/../partials/page-header.php'; ?>

    <div class="app-content">
      <div class="container-fluid">

        <!-- Summary strip -->
        <div class="card mb-3">
          <div class="card-body d-flex align-items-center gap-4 flex-wrap py-2">
            <div class="text-center">
              <div class="fs-5 fw-semibold"><?= $totalModules ?></div>
              <div class="text-muted" style="font-size:.75rem">Total modules</div>
            </div>
            <div class="text-center">
              <div class="fs-5 fw-semibold text-success"><?= $totalComplete ?></div>
              <div class="text-muted" style="font-size:.75rem">Complete (6/6)</div>
            </div>
            <div class="text-center">
              <div class="fs-5 fw-semibold text-warning"><?= $totalModules - $totalComplete ?></div>
              <div class="text-muted" style="font-size:.75rem">Incomplete</div>
            </div>
            <div class="text-center">
              <div class="fs-5 fw-semibold"><?= count($sections) ?></div>
              <div class="text-muted" style="font-size:.75rem">Sections</div>
            </div>
            <div class="ms-auto">
              <a href="?" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
              </a>
            </div>
          </div>
        </div>

        <!-- Accordion: one item per section -->
        <div class="accordion" id="moduleAccordion">
          <?php $accIdx = 0; foreach ($sections as $sectionName => $mods): ?>
          <?php
            $meta       = [
                'color' => $mods[0]['section_color'] ?? 'secondary',
                'icon'  => $mods[0]['section_icon']  ?? 'bi-grid',
            ];
            $complete   = count(array_filter($mods, fn($m) => $m['file_count'] === 6));
            $total      = count($mods);
            $badgeColor = $complete === $total ? 'success' : ($complete > 0 ? 'warning' : 'danger');
            $collapseId = 'section-' . $accIdx;
          ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $accIdx > 0 ? 'collapsed' : '' ?>"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#<?= $collapseId ?>"
                      aria-expanded="<?= $accIdx === 0 ? 'true' : 'false' ?>">
                <i class="bi <?= $meta['icon'] ?> me-2 text-<?= $meta['color'] ?>"></i>
                <span class="fw-semibold me-2"><?= htmlspecialchars($sectionName) ?></span>
                <span class="badge bg-<?= $badgeColor ?>"><?= $complete ?>/<?= $total ?></span>
              </button>
            </h2>
            <div id="<?= $collapseId ?>"
                 class="accordion-collapse collapse <?= $accIdx === 0 ? 'show' : '' ?>"
                 data-bs-parent="#moduleAccordion">
              <div class="accordion-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="table-dark">
                      <tr>
                        <th class="text-center" style="width:2.5rem">#</th>
                        <th>Module</th>
                        <th>system_name</th>
                        <th class="text-center" style="width:5rem">section_ref</th>
                        <th class="text-center" style="width:4rem">Ref</th>
                        <th class="text-center" style="width:3rem" title="Auto-generates reference number">Flag</th>
                        <th class="text-center" style="width:3.5rem">.php</th>
                        <th class="text-center" style="width:3.5rem">.js</th>
                        <th class="text-center" style="width:3.5rem">_data</th>
                        <th class="text-center" style="width:3.5rem">_search</th>
                        <th class="text-center" style="width:3.5rem">_srch.js</th>
                        <th class="text-center" style="width:3.5rem">_print</th>
                        <th class="text-center" style="width:4rem">Files</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($mods as $i => $m): ?>
                      <?php
                        $rowClass = match(true) {
                            $m['file_count'] === 6 => '',
                            $m['file_count'] === 0 => 'table-danger',
                            default                => 'table-warning',
                        };
                      ?>
                      <tr class="<?= $rowClass ?>">
                        <td class="text-center text-muted small"><?= $i + 1 ?></td>
                        <td>
                          <?php if ($m['has_file']): ?>
                            <a href="/home.php?page=<?= htmlspecialchars($m['system_name']) ?>"
                               class="text-decoration-none">
                              <?= htmlspecialchars($m['module']) ?>
                            </a>
                          <?php else: ?>
                            <span class="text-muted"><?= htmlspecialchars($m['module']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="font-monospace small text-muted"><?= htmlspecialchars($m['system_name']) ?></td>
                        <td class="text-center">
                          <span class="badge bg-light text-dark border font-monospace" style="font-size:.7rem">
                            <?= htmlspecialchars($m['section_ref'] ?? '—') ?>
                          </span>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-<?= $meta['color'] ?> text-<?= $meta['color'] === 'warning' ? 'dark' : 'white' ?>">
                            <?= htmlspecialchars($m['reference']) ?>
                          </span>
                        </td>
                        <td class="text-center">
                          <?php if ($m['flag']): ?>
                            <i class="bi bi-hash text-primary" title="Auto-generates reference number"></i>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                        <?php foreach (['php', 'js', 'data', 'search', 'srchjs', 'print'] as $fk): ?>
                        <td class="text-center">
                          <?php if ($m['folder_path']): ?>
                            <?php if ($m['files'][$fk]): ?>
                              <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                              <i class="bi bi-x-circle-fill text-danger"></i>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="text-muted small">—</span>
                          <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-center">
                          <span class="badge bg-<?= $m['file_count'] === 6 ? 'success' : ($m['file_count'] === 0 ? 'danger' : 'warning') ?> text-<?= $m['file_count'] !== 6 && $m['file_count'] !== 0 ? 'dark' : 'white' ?>">
                            <?= $m['file_count'] ?>/6
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <?php $accIdx++; endforeach; ?>
        </div>

      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

</div>

<?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
