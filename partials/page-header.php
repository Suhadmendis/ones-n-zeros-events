<?php
// Usage: set $pageHeading and $breadcrumbs before including this file.
// $pageHeading  (string)  — the h3 title shown on the left
// $breadcrumbs  (array)   — each item: ['label' => '...', 'url' => '...']
//                           last item is always rendered as active (no url needed)
$pageHeading = $pageHeading ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$isReport    = $isReport    ?? false;
?>
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0"><?= htmlspecialchars($pageHeading) ?></h3>
      </div>
      <div class="col-sm-6 d-flex justify-content-sm-end align-items-center gap-2 flex-wrap mt-2 mt-sm-0">
<ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
          <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <?php $isLast = $i === array_key_last($breadcrumbs); ?>
            <?php if ($isLast): ?>
              <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($crumb['label']) ?>
              </li>
            <?php else: ?>
              <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>">
                  <?= htmlspecialchars($crumb['label']) ?>
                </a>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</div>
