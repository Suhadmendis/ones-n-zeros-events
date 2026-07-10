// index.js — Dashboard Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      loading:          true,
      grouped:          {},   // { section: { subsection: [modules] } }
      view:             'overview',
      activeSection:    null,
      activeSubsection: null,

      sectionStyles: window.__SECTION_STYLES__ || {},
    };
  },

  computed: {
    currentModules() {
      return this.grouped[this.activeSection]?.[this.activeSubsection] ?? [];
    },
  },

  mounted() {
    this.fetchModules();
  },

  methods: {
    fetchModules() {
      axios.get('/server/general/module_system.php')
        .then(res => {
          const g = {};
          for (const m of res.data) {
            const sec = m.section;
            const sub = m.subsection || '';
            if (!g[sec]) g[sec] = {};
            if (!g[sec][sub]) g[sec][sub] = [];
            g[sec][sub].push(m);
          }
          this.grouped = g;
        })
        .catch(err => console.error('fetchModules failed:', err))
        .finally(() => { this.loading = false; });
    },

    drillInto(section, sub) {
      this.activeSection    = section;
      this.activeSubsection = sub;
      this.view             = 'modules';
    },

    goBack() {
      this.view             = 'overview';
      this.activeSection    = null;
      this.activeSubsection = null;
    },

    styleFor(key) {
      return this.sectionStyles[key] ?? {
        boxStyle: { backgroundColor: 'var(--bs-secondary)', color: '#fff' },
        link: 'link-light',
        icon: '',
      };
    },

    hasSubsections(section) {
      const keys = Object.keys(this.grouped[section] ?? {});
      return !(keys.length === 1 && keys[0] === '');
    },

    moduleCount(section, sub) {
      return (this.grouped[section]?.[sub] ?? []).length;
    },
  },
}).mount('#dashboard-app');
