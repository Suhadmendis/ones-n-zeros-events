// js/module-help.js — shared "Help" button + modal for module pages
//
// Any module page that includes a button with class `.module-help-btn` gets
// a working Help modal for free — content is fetched live from
// sys_tms_module_documentation, no per-page modal markup required. See
// module_documentation module to edit content.

(() => {
  'use strict';

  // Prefer the page's own SYSTEM_NAME constant (per MODULE_GUIDE.md), but not
  // every module injects it — home.php's own routing param is always present
  // and holds the identical value, so it's a reliable fallback.
  function resolveSystemName() {
    if (typeof SYSTEM_NAME !== 'undefined' && SYSTEM_NAME) return SYSTEM_NAME;
    return new URLSearchParams(window.location.search).get('page') || '';
  }

  const SECTIONS = [
    ['purpose', 'Purpose', 'bi-bullseye'],
    ['business_problem', 'Business Problem', 'bi-exclamation-circle'],
    ['business_impact', 'Business Impact', 'bi-graph-up-arrow'],
    ['when_to_use', 'When to Use', 'bi-clock-history'],
    ['workflow', 'Workflow', 'bi-diagram-3'],
    ['prerequisites', 'Prerequisites', 'bi-list-check'],
    ['best_practices', 'Best Practices', 'bi-star'],
    ['notes', 'Notes', 'bi-sticky'],
  ];

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function getOrCreateModal() {
    let modal = document.getElementById('moduleHelpModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'moduleHelpModal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-question-circle me-2 text-primary"></i>
              <span id="moduleHelpModalTitle">Help</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="moduleHelpModalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);
    return modal;
  }

  function renderHelp(data) {
    const titleEl = document.getElementById('moduleHelpModalTitle');
    const bodyEl = document.getElementById('moduleHelpModalBody');

    if (!data || !data.found) {
      titleEl.textContent = 'Help';
      bodyEl.innerHTML = `<p class="text-muted mb-0">No documentation has been added for this module yet.</p>`;
      return;
    }

    titleEl.textContent = data.title || 'Help';

    let html = '';
    if (data.subtitle) {
      html += `<p class="text-muted mb-3">${escapeHtml(data.subtitle)}</p>`;
    }

    let first = true;
    for (const [field, label, icon] of SECTIONS) {
      const value = data[field];
      if (!value) continue;
      if (!first) html += '<hr>';
      first = false;
      html += `
        <h6 class="fw-bold text-primary mb-2"><i class="bi ${icon} me-1"></i>${label}</h6>
        <p class="mb-0" style="white-space: pre-wrap;">${escapeHtml(value)}</p>`;
    }

    if (first) {
      html += `<p class="text-muted mb-0">No documentation has been added for this module yet.</p>`;
    }

    bodyEl.innerHTML = html;
  }

  function openHelp() {
    const modal = getOrCreateModal();
    renderHelp(null); // reset while loading
    document.getElementById('moduleHelpModalBody').innerHTML =
      '<div class="text-center py-4"><span class="spinner-border text-primary" role="status"></span></div>';
    bootstrap.Modal.getOrCreateInstance(modal).show();

    axios.get('/server/general/module_help.php', { params: { system_name: resolveSystemName() } })
      .then(res => renderHelp(res.data))
      .catch(() => renderHelp(null));
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!resolveSystemName()) return;
    document.querySelectorAll('.module-help-btn').forEach(btn => {
      btn.addEventListener('click', openHelp);
    });
  });
})();
