// stg_user_preferences.js

const DATA_URL = '/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php';

const { createApp } = Vue;

createApp({
  data() {
    return {
      loading: true,
      saving:  false,
      saved:   false,
      error:   '',
      prefs: {
        check_gl_default: false,
      },
    };
  },

  mounted() {
    axios.get(DATA_URL + '?action=get')
      .then(res => { this.prefs.check_gl_default = !!res.data.check_gl_default; })
      .catch(() => { this.error = 'Failed to load preferences.'; })
      .finally(() => { this.loading = false; });
  },

  methods: {
    save() {
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post(DATA_URL + '?action=save', this.prefs)
        .then(() => {
          this.saved = true;
          setTimeout(() => { this.saved = false; }, 2500);
        })
        .catch(() => { this.error = 'Failed to save preferences.'; })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#uprefs-app');
