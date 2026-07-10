// backup_restore.js — Backup & Restore Settings Vue app

(function () {
  const { createApp } = Vue;

  const SELECT_OPTIONS = {
    backup_frequency: [
      { value: 'daily',   label: 'Daily' },
      { value: 'weekly',  label: 'Weekly' },
      { value: 'monthly', label: 'Monthly' },
      { value: 'manual',  label: 'Manual only' },
    ],
  };

  createApp({
    data() {
      return {
        settings:     [],
        loading:      true,
        saved:        {},
        backupRunning: false,
        backupMsg:    null,
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

        const r = await fetch('/modules/settings/backup_restore/backup_restore_data.php?action=update', {
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

      async triggerBackup() {
        this.backupRunning = true;
        this.backupMsg = null;
        try {
          const r    = await fetch('/modules/settings/backup_restore/backup_restore_data.php?action=run_backup');
          const data = await r.json();
          this.backupMsg = { ok: data.ok, text: data.message ?? (data.ok ? 'Backup complete.' : 'Backup failed.') };
        } catch {
          this.backupMsg = { ok: false, text: 'Failed to connect to backup service.' };
        } finally {
          this.backupRunning = false;
          setTimeout(() => { this.backupMsg = null; }, 5000);
        }
      },
    },

    async mounted() {
      try {
        const r    = await fetch('/modules/settings/backup_restore/backup_restore_data.php?action=list');
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
  }).mount('#bkp-app');
})();
