// stg_enable_modules.js — Sections enable/disable settings

const { createApp } = Vue;

// Curated icon choices per section (Bootstrap Icons, keyed by section ref).
// Falls back to DEFAULT_ICONS for any section not listed here.
const ICON_CHOICES = {
  COMP: ['bi-building-gear', 'bi-building', 'bi-briefcase', 'bi-diagram-3', 'bi-building-check', 'bi-person-workspace'],
  MSTR: ['bi-folder2-open', 'bi-archive', 'bi-collection', 'bi-files', 'bi-database', 'bi-folder'],
  OPS:  ['bi-truck', 'bi-gear', 'bi-diagram-2', 'bi-signpost-split', 'bi-cone-striped', 'bi-truck-front'],
  RPTS: ['bi-file-earmark-bar-graph', 'bi-graph-up', 'bi-bar-chart', 'bi-clipboard-data', 'bi-pie-chart', 'bi-file-earmark-text'],
  FIN:  ['bi-cash-stack', 'bi-currency-dollar', 'bi-wallet2', 'bi-bank', 'bi-credit-card', 'bi-piggy-bank'],
  STGS: ['bi-sliders2', 'bi-gear', 'bi-tools', 'bi-sliders', 'bi-toggle-on', 'bi-gear-fill'],
};
const DEFAULT_ICONS = ['bi-circle', 'bi-square', 'bi-star', 'bi-flag', 'bi-bookmark', 'bi-grid'];

// Bootstrap's 8 theme colors plus its extended base-color palette. All 13 exist as
// --bs-{name} CSS custom properties in css/adminlte.css, but only the 8 theme colors
// have generated bg-*/text-bg-* utility classes — so swatches are colored via inline
// `var(--bs-{name})` styles instead of Bootstrap color utility classes.
const COLOR_CHOICES = [
  'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark',
  'indigo', 'purple', 'pink', 'orange', 'teal',
];

createApp({
  data() {
    return {
      systemName:    SYSTEM_NAME,
      title:         '',
      loading:       false,
      sections:      [],
      pendingToggle: null,
      colorChoices:  COLOR_CHOICES,
    };
  },

  mounted() {
    this.fetchTitle();
    this.fetchSections();

    document.getElementById('confirm-toggle-yes').addEventListener('click', () => {
      this.confirmToggle();
    });

    document.getElementById('confirmToggleModal').addEventListener('hidden.bs.modal', () => {
      this.pendingToggle = null;
    });
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => { this.title = 'Enable Modules'; });
    },

    fetchSections() {
      this.loading = true;
      axios.get('/modules/settings/modules_mgmt/stg_enable_modules/stg_enable_modules_data.php?action=list')
        .then(res => { this.sections = res.data; })
        .catch(err => { console.error('Failed to load sections', err); })
        .finally(() => { this.loading = false; });
    },

    onToggleClick(section, field) {
      const newValue    = section[field] == 1 ? 0 : 1;
      const fieldLabel  = field === 'web_enable' ? 'Web' : 'App';
      const stateLabel  = newValue === 1 ? 'enable' : 'disable';

      document.getElementById('confirm-toggle-body').textContent =
        'Are you sure you want to ' + stateLabel + ' ' + fieldLabel + ' access for "' + section.name + '"?';

      this.pendingToggle = {
        ref: section.ref,
        field,
        newValue,
        sectionName: section.name,
      };

      bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmToggleModal')).show();
    },

    confirmToggle() {
      if (!this.pendingToggle) return;
      const { ref, field, newValue } = this.pendingToggle;

      axios.post(
        '/modules/settings/modules_mgmt/stg_enable_modules/stg_enable_modules_data.php?action=toggle',
        { ref, field, value: newValue }
      ).then(() => {
        const section = this.sections.find(s => s.ref === ref);
        if (section) section[field] = newValue;
      }).catch(err => {
        console.error('Failed to update toggle', err);
      }).finally(() => {
        bootstrap.Modal.getInstance(document.getElementById('confirmToggleModal'))?.hide();
      });
    },

    iconChoices(section) {
      const choices = ICON_CHOICES[section.ref] || DEFAULT_ICONS;
      if (section.web_icon && !choices.includes(section.web_icon)) {
        return [section.web_icon, ...choices];
      }
      return choices;
    },

    onIconSelect(section, icon) {
      if (icon === section.web_icon) return;
      this.setStyle(section, 'web_icon', icon);
    },

    onColorSelect(section, color) {
      if (color === section.web_color) return;
      this.setStyle(section, 'web_color', color);
    },

    colorVar(name) {
      return 'var(--bs-' + (name || 'secondary') + ')';
    },

    setStyle(section, field, value) {
      const previous = section[field];
      section[field] = value;

      axios.post(
        '/modules/settings/modules_mgmt/stg_enable_modules/stg_enable_modules_data.php?action=set_style',
        { ref: section.ref, field, value }
      ).catch(err => {
        console.error('Failed to update ' + field, err);
        section[field] = previous;
      });
    },
  },
}).mount('#enable-modules-app');
