// developer_settings.js — Developer Settings Vue app

(function () {
  const { createApp } = Vue;

  const SELECT_OPTIONS = {
    log_level: [
      { value: 'error',   label: 'Error only' },
      { value: 'warning', label: 'Warning' },
      { value: 'info',    label: 'Info' },
      { value: 'debug',   label: 'Debug (verbose)' },
    ],
    api_rate_limit: [
      { value: '60',  label: '60 req/min' },
      { value: '120', label: '120 req/min' },
      { value: '300', label: '300 req/min' },
      { value: '0',   label: 'Unlimited' },
    ],
  };

  createApp({
    data() {
      return {
        settings: [],
        loading:  true,
        saved:    {},
      };
    },

    computed: {
      grouped() {
        return this.settings.reduce((acc, s) => {
          (acc[s.setting_group] ??= []).push(s);
          return acc;
        }, {});
      },
    },

    methods: {
      parseOptions(opts) {
        if (!opts) return [];
        try { return JSON.parse(opts); } catch { return []; }
      },

      async update(setting, value) {
        const old = setting.setting_value;
        if (value === old) return;
        setting.setting_value = value;

        const r = await fetch('/modules/settings/developer_settings/developer_settings_data.php?action=update', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ setting_key: setting.setting_key, setting_value: value }),
        });

        if (r.ok) {
          this.saved[setting.setting_key] = true;
          setTimeout(() => { this.saved[setting.setting_key] = false; }, 2000);
        } else {
          setting.setting_value = old;
        }
      },
    },

    async mounted() {
      try {
        const r    = await fetch('/modules/settings/developer_settings/developer_settings_data.php?action=list');
        const rows = await r.json();
        this.settings = (Array.isArray(rows) ? rows : []).map((s) => {
          if (s.setting_type === 'select' && !s.options && SELECT_OPTIONS[s.setting_key]) {
            s.options = JSON.stringify(SELECT_OPTIONS[s.setting_key]);
          }
          return s;
        });
      } finally {
        this.loading = false;
      }
    },
  }).mount('#dvs-app');
})();
