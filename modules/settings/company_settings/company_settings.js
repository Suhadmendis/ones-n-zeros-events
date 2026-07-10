// company_settings.js — Company Settings Vue app

(function () {
  const { createApp } = Vue;

  const SELECT_OPTIONS = {
    date_format:       [{ value: 'DD/MM/YYYY', label: 'DD/MM/YYYY' }, { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY' }, { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD' }],
    fiscal_year_month: [
      { value: '1', label: 'January' }, { value: '2', label: 'February' }, { value: '3', label: 'March' },
      { value: '4', label: 'April' },   { value: '5', label: 'May' },      { value: '6', label: 'June' },
      { value: '7', label: 'July' },    { value: '8', label: 'August' },   { value: '9', label: 'September' },
      { value: '10', label: 'October' }, { value: '11', label: 'November' }, { value: '12', label: 'December' },
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

        const r = await fetch('/modules/settings/company_settings/company_settings_data.php?action=update', {
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
        const r    = await fetch('/modules/settings/company_settings/company_settings_data.php?action=list');
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
  }).mount('#cos-app');
})();
