// integrations.js — Integrations Settings Vue app

(function () {
  const { createApp } = Vue;

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

        const r = await fetch('/modules/settings/integrations/integrations_data.php?action=update', {
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
        const r    = await fetch('/modules/settings/integrations/integrations_data.php?action=list');
        const rows = await r.json();
        this.settings = rows;
      } finally {
        this.loading = false;
      }
    },
  }).mount('#itg-app');
})();
