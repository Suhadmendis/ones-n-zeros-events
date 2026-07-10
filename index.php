<?php
require_once 'server/session.php';
require_once 'server/supabase.php';
require_login();
$pageTitle = 'Dashboard — Ones n Zeros ERP';

// Bootstrap's theme colors have generated text-bg-* utility classes, but the extended
// palette (indigo/purple/pink/orange/teal) only exists as --bs-{name} CSS custom
// properties — so tiles are colored via inline style, not a Bootstrap color class.
$_DARK_TEXT_COLORS = ['warning', 'info', 'light', 'orange', 'teal'];

$_sec = supabase_get(SB_API . 'sys_tms_sections?select=name,web_icon,web_color&order=sort_order.asc');
$_sectionStyles = [];
foreach ($_sec as $s) {
    $color    = $s['web_color'] ?: 'secondary';
    $darkText = in_array($color, $_DARK_TEXT_COLORS, true);
    $_sectionStyles[$s['name']] = [
        'boxStyle' => [
            'backgroundColor' => 'var(--bs-' . $color . ')',
            'color'           => $darkText ? '#000' : '#fff',
        ],
        'link' => $darkText ? 'link-dark' : 'link-light',
        'icon' => $s['web_icon'] ?: 'bi-circle',
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'partials/head.php'; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <?php include 'partials/header.php'; ?>
  <?php include 'partials/sidebar.php'; ?>

  <main class="app-main">
    <?php
      $pageHeading = 'Dashboard';
      $breadcrumbs = [['label' => 'Dashboard']];
      include 'partials/page-header.php';
    ?>

    <div class="app-content">
      <div class="container-fluid">

        <div id="dashboard-app">

          <!-- Loading -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-secondary" role="status"></div>
          </div>

          <template v-else>

            <!-- OVERVIEW: section headings with subsection tiles (or module tiles if no subsections) -->
            <div v-if="view === 'overview'">
              <div v-for="(subsections, section) in grouped" :key="section" class="mb-3">
                <h6 class="text-secondary text-uppercase small mb-2 mt-3">{{ section }}</h6>
                <div class="row">

                  <!-- Section has subsections → show subsection tiles -->
                  <template v-if="hasSubsections(section)">
                    <div v-for="(mods, sub) in subsections" :key="sub"
                         class="col-lg-4 col-6 mb-2" style="cursor:pointer"
                         @click="drillInto(section, sub)">
                      <div class="small-box" :style="styleFor(section).boxStyle">
                        <div class="inner">
                          <h3 class="fs-5">{{ sub }}</h3>
                          <p>{{ moduleCount(section, sub) }} modules</p>
                        </div>
                        <i class="small-box-icon bi" :class="styleFor(section).icon"></i>
                        <span class="small-box-footer" :class="styleFor(section).link">
                          Open <i class="bi bi-arrow-right-short"></i>
                        </span>
                      </div>
                    </div>
                  </template>

                  <!-- No subsections → show module tiles directly -->
                  <template v-else>
                    <div v-for="m in Object.values(subsections)[0]" :key="m.id" class="col-lg-4 col-6 mb-2">
                      <div class="small-box" :class="{ 'text-bg-secondary opacity-50': !m.has_file }" :style="m.has_file ? styleFor(section).boxStyle : {}">
                        <div class="inner">
                          <h3 class="fs-5">{{ m.module }}</h3>
                          <p>{{ section }}</p>
                        </div>
                        <i class="small-box-icon bi" :class="styleFor(section).icon"></i>
                        <a v-if="m.has_file" :href="'/home.php?page=' + m.system_name"
                           class="small-box-footer link-underline-opacity-0 link-underline-opacity-50-hover"
                           :class="styleFor(section).link">
                          View <i class="bi bi-arrow-right-short"></i>
                        </a>
                        <span v-else class="small-box-footer link-light" style="cursor:default;">Coming soon</span>
                      </div>
                    </div>
                  </template>

                </div>
              </div>
            </div>

            <!-- MODULE VIEW: modules within a selected subsection -->
            <div v-if="view === 'modules'">
              <div class="d-flex align-items-center gap-2 mb-3 mt-2">
                <button class="btn btn-sm btn-outline-secondary" @click="goBack">
                  <i class="bi bi-arrow-left"></i> Back
                </button>
                <span class="text-muted small">{{ activeSection }} › {{ activeSubsection }}</span>
              </div>
              <div class="row">
                <div v-for="m in currentModules" :key="m.id" class="col-lg-4 col-6 mb-2">
                  <div class="small-box" :class="{ 'text-bg-secondary opacity-50': !m.has_file }" :style="m.has_file ? styleFor(activeSection).boxStyle : {}">
                    <div class="inner">
                      <h3 class="fs-5">{{ m.module }}</h3>
                      <p>{{ activeSubsection }}</p>
                    </div>
                    <i class="small-box-icon bi" :class="styleFor(activeSection).icon"></i>
                    <a v-if="m.has_file" :href="'/home.php?page=' + m.system_name"
                       class="small-box-footer link-underline-opacity-0 link-underline-opacity-50-hover"
                       :class="styleFor(activeSection).link">
                      View <i class="bi bi-arrow-right-short"></i>
                    </a>
                    <span v-else class="small-box-footer link-light" style="cursor:default;">Coming soon</span>
                  </div>
                </div>
              </div>
            </div>

          </template>

        </div>

      </div>
    </div>
  </main>

  <?php include 'partials/footer.php'; ?>

</div>

<?php include 'partials/scripts.php'; ?>
<script>window.__SECTION_STYLES__ = <?= json_encode($_sectionStyles) ?>;</script>
<script src="/js/index.js"></script>

</body>
</html>
