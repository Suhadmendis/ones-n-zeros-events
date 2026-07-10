<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list"></i>
        </a>
      </li>
    </ul>

    <div class="app-header-search d-none d-md-flex">
      <form class="app-header-search-inner" role="search" action="#" method="get" onsubmit="return false;" autocomplete="off">
        <i class="bi bi-search app-header-search-icon"></i>
        <input class="form-control app-header-search-input" type="search" placeholder="Search modules..." aria-label="Search modules" autocomplete="off">
        <kbd class="app-header-search-shortcut" aria-hidden="true"></kbd>
        <div class="dropdown-menu app-header-search-results"></div>
      </form>
    </div>

    <ul class="navbar-nav ms-auto align-items-center">
      <li class="nav-item d-md-none">
        <a class="nav-link" href="#" data-lte-toggle="search" role="button" aria-label="Toggle search" aria-expanded="false">
          <i class="bi bi-search"></i>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
          <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
          <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" href="#" id="bd-theme" aria-label="Toggle color scheme"
           data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
          <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
          <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme"
            style="--bs-dropdown-min-width: 8rem">
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="light" aria-pressed="false">
              <i class="bi bi-sun-fill me-2"></i> Light
              <i class="bi bi-check-lg ms-auto d-none"></i>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center"
                    data-bs-theme-value="dark" aria-pressed="false">
              <i class="bi bi-moon-fill me-2"></i> Dark
              <i class="bi bi-check-lg ms-auto d-none"></i>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item d-flex align-items-center active"
                    data-bs-theme-value="auto" aria-pressed="true">
              <i class="bi bi-circle-half me-2"></i> Auto
              <i class="bi bi-check-lg ms-auto d-none"></i>
            </button>
          </li>
        </ul>
      </li>
      <?php $__u = current_user(); ?>
      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-5"></i>
          <span class="d-none d-md-inline ms-1"><?= htmlspecialchars($__u['full_name'] ?? 'Account') ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <li class="px-3 py-2 border-bottom">
            <div class="fw-semibold"><?= htmlspecialchars($__u['full_name'] ?? '') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($__u['email'] ?? '') ?></div>
            <?php foreach ($__u['roles'] ?? [] as $__role): ?>
              <span class="badge bg-secondary text-uppercase mt-1 me-1" style="font-size:.65rem;"><?= htmlspecialchars($__role) ?></span>
            <?php endforeach; ?>
          </li>
          <li class="user-footer">
            <a href="#" class="btn btn-outline-secondary">Profile</a>
            <a href="/logout.php" class="btn btn-outline-danger float-end">Sign out</a>
          </li>
        </ul>
      </li>
    </ul>

    <div class="navbar-search-block d-none d-md-none" role="search">
      <form class="app-header-search-inner" role="search" action="#" method="get" onsubmit="return false;" autocomplete="off">
        <i class="bi bi-search app-header-search-icon"></i>
        <input class="form-control app-header-search-input" type="search" placeholder="Search modules..." aria-label="Search modules" autocomplete="off">
        <div class="dropdown-menu app-header-search-results"></div>
      </form>
    </div>
  </div>
</nav>
