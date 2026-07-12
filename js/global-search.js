// js/global-search.js — header search over the section/subsection/module menu hierarchy
//
// Reuses the /server/general/access_engine.php endpoint (same effective
// enabled+permitted data partials/sidebar.php renders from) rather than
// introducing a new backend route.

(() => {
  'use strict';

  let indexPromise = null;

  function loadIndex() {
    if (!indexPromise) {
      indexPromise = axios.get('/server/general/access_engine.php')
        .then(res => (res.data || []).filter(m => m.has_file && m.system_name))
        .catch(() => []);
    }
    return indexPromise;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function breadcrumb(m) {
    return [m.section, m.subsection].filter(Boolean).join(' › ');
  }

  function matches(m, query) {
    const haystack = `${m.module} ${m.section} ${m.subsection}`.toLowerCase();
    return haystack.includes(query);
  }

  function closePanel(panel) {
    panel.classList.remove('show');
    panel.innerHTML = '';
  }

  function renderResults(panel, items) {
    if (!items.length) {
      panel.innerHTML = '<span class="dropdown-item-text text-muted">No matching modules</span>';
      panel.classList.add('show');
      return;
    }
    panel.innerHTML = items.map((m, i) => `
      <a class="dropdown-item app-header-search-result${i === 0 ? ' active' : ''}"
         href="/home.php?page=${encodeURIComponent(m.system_name)}">
        <div class="fw-medium">${escapeHtml(m.module)}</div>
        <div class="app-header-search-result-path">${escapeHtml(breadcrumb(m))}</div>
      </a>
    `).join('');
    panel.classList.add('show');
  }

  function moveActive(panel, delta) {
    const items = Array.from(panel.querySelectorAll('.app-header-search-result'));
    if (!items.length) return;
    const current = items.findIndex(el => el.classList.contains('active'));
    let next = (current + delta + items.length) % items.length;
    items.forEach(el => el.classList.remove('active'));
    items[next].classList.add('active');
    items[next].scrollIntoView({ block: 'nearest' });
  }

  function initSearchBox(input) {
    const panel = input.closest('.app-header-search-inner')?.querySelector('.app-header-search-results');
    if (!panel) return;

    input.addEventListener('focus', () => loadIndex());

    input.addEventListener('input', () => {
      const query = input.value.trim().toLowerCase();
      if (!query) {
        closePanel(panel);
        return;
      }
      loadIndex().then(index => {
        renderResults(panel, index.filter(m => matches(m, query)).slice(0, 8));
      });
    });

    input.addEventListener('keydown', event => {
      if (!panel.classList.contains('show')) return;
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(panel, 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(panel, -1);
      } else if (event.key === 'Enter') {
        event.preventDefault();
        const active = panel.querySelector('.app-header-search-result.active') || panel.querySelector('.app-header-search-result');
        if (active) window.location.href = active.getAttribute('href');
      } else if (event.key === 'Escape') {
        closePanel(panel);
        input.blur();
      }
    });

    document.addEventListener('click', event => {
      if (event.target !== input && !panel.contains(event.target)) {
        closePanel(panel);
      }
    });
  }

  function isMac() {
    return /Mac|iPod|iPhone|iPad/.test(navigator.platform);
  }

  function isVisible(el) {
    return !!el && el.offsetParent !== null;
  }

  function focusSearch() {
    const desktopInput = document.querySelector('.app-header-search .app-header-search-input');
    if (isVisible(desktopInput)) {
      desktopInput.focus();
      return;
    }
    const mobileBlock = document.querySelector('.navbar-search-block');
    const mobileInput = mobileBlock?.querySelector('.app-header-search-input');
    if (mobileBlock && mobileInput && window.adminlte?.NavbarSearch) {
      new window.adminlte.NavbarSearch(mobileBlock).open();
      document.querySelector('[data-lte-toggle="search"]')?.setAttribute('aria-expanded', 'true');
    } else if (mobileInput) {
      mobileInput.focus();
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.app-header-search-input').forEach(initSearchBox);

    const hint = document.querySelector('.app-header-search-shortcut');
    if (hint) hint.textContent = isMac() ? '⌘K' : 'Ctrl K';
  });

  document.addEventListener('keydown', event => {
    if (event.key.toLowerCase() !== 'k' || !(event.metaKey || event.ctrlKey)) return;
    event.preventDefault();
    focusSearch();
  });
})();
