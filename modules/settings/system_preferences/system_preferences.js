// system_preferences.js — System Preferences Vue app

(function () {
  const { createApp } = Vue;

  const SELECT_OPTIONS = {
    theme: [
      { value: 'light', label: 'Light' },
      { value: 'dark',  label: 'Dark' },
      { value: 'auto',  label: 'Follow system' },
    ],
    rows_per_page: [
      { value: '10',  label: '10 rows' },
      { value: '25',  label: '25 rows' },
      { value: '50',  label: '50 rows' },
      { value: '100', label: '100 rows' },
    ],
    date_display_format: [
      { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
      { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
      { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
    ],
    language: [
      { value: 'en', label: 'English' },
      { value: 'si', label: 'Sinhala' },
      { value: 'ta', label: 'Tamil' },
    ],
    first_day_of_week: [
      { value: '0', label: 'Sunday' },
      { value: '1', label: 'Monday' },
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

        const r = await fetch('/modules/settings/system_preferences/system_preferences_data.php?action=update', {
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
        const r    = await fetch('/modules/settings/system_preferences/system_preferences_data.php?action=list');
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
  }).mount('#spf-app');
})();
