<?php
// Build sidebar nav from tms_modules, filtered by user permissions
require_once __DIR__ . '/../server/general/module_system.php';

$_sidebarUser  = current_user();
$_allowedRefs  = getPermittedModuleRefs($_sidebarUser['ref'] ?? '');

// Group: section → subsection ('' for none) → modules
$sidebarModules  = [];
$sectionIcons    = [];
$subsectionIcons = [];
foreach (getModules($_allowedRefs) as $row) {
    if ($row['has_file']) {
        $sub = $row['subsection'] ?: '';
        $sidebarModules[$row['section']][$sub][] = $row;
        $sectionIcons[$row['section']] = $row['section_icon'] ?? 'bi-circle';
        if ($sub !== '') {
            $subsectionIcons[$sub] = $row['subsection_icon'] ?? 'bi-folder';
        }
    }
}

$currentPage = preg_replace('/[^a-z0-9_]/', '', $_GET['page'] ?? '');
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="/index.php" class="brand-link">
      <img src="/assets/img/AdminLTELogo.png" alt="Logo"
           class="brand-image opacity-75 shadow" />
      <span class="brand-text fw-light">Ones n Zeros ERP</span>
    </a>
  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column"
          data-lte-toggle="treeview"
          role="navigation"
          aria-label="Main navigation"
          data-accordion="false"
          id="navigation">

        <!-- Dashboard -->
        <li class="nav-item<?= ($currentPage === '' ? ' menu-open' : '') ?>">
          <a href="/index.php" class="nav-link<?= ($currentPage === '' ? ' active' : '') ?>">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <?php foreach ($sidebarModules as $section => $subsections):
          $sectionActive = false;
          foreach ($subsections as $mods)
              foreach ($mods as $m)
                  if ($m['system_name'] === $currentPage) { $sectionActive = true; break 2; }
          $sectionIcon = $sectionIcons[$section] ?? 'bi-circle';
        ?>
        <li class="nav-item<?= $sectionActive ? ' menu-open' : '' ?>">
          <a href="#" class="nav-link<?= $sectionActive ? ' active' : '' ?>">
            <i class="nav-icon bi <?= htmlspecialchars($sectionIcon) ?>"></i>
            <p><?= htmlspecialchars($section) ?><i class="nav-arrow bi bi-chevron-right"></i></p>
          </a>
          <ul class="nav nav-treeview">

            <?php foreach ($subsections as $sub => $mods):
              if ($sub !== ''):
                $subActive = (bool) array_filter($mods, fn($m) => $m['system_name'] === $currentPage);
                $subIcon   = $subsectionIcons[$sub] ?? 'bi-folder';
            ?>
              <li class="nav-item<?= $subActive ? ' menu-open' : '' ?>">
                <a href="#" class="nav-link<?= $subActive ? ' active' : '' ?>">
                  <i class="nav-icon bi <?= htmlspecialchars($subIcon) ?>"></i>
                  <p><?= htmlspecialchars($sub) ?><i class="nav-arrow bi bi-chevron-right"></i></p>
                </a>
                <ul class="nav nav-treeview">
                  <?php foreach ($mods as $m): ?>
                  <li class="nav-item">
                    <a href="/home.php?page=<?= urlencode($m['system_name']) ?>"
                       class="nav-link<?= $m['system_name'] === $currentPage ? ' active' : '' ?>">
                      <i class="nav-icon bi bi-circle<?= $m['system_name'] === $currentPage ? '-fill' : '' ?>"></i>
                      <p><?= htmlspecialchars($m['module']) ?></p>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </li>

            <?php else: // section has no subsections — render modules directly ?>
              <?php foreach ($mods as $m): ?>
              <li class="nav-item">
                <a href="/home.php?page=<?= urlencode($m['system_name']) ?>"
                   class="nav-link<?= $m['system_name'] === $currentPage ? ' active' : '' ?>">
                  <i class="nav-icon bi bi-circle<?= $m['system_name'] === $currentPage ? '-fill' : '' ?>"></i>
                  <p><?= htmlspecialchars($m['module']) ?></p>
                </a>
              </li>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php endforeach; ?>
          </ul>
        </li>
        <?php endforeach; ?>

      </ul>
    </nav>
  </div>
</aside>
