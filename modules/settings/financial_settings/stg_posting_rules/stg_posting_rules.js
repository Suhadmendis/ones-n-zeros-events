// stg_posting_rules.js — Posting Rules Vue app

const { createApp } = Vue;

const DATA_URL = '/modules/settings/financial_settings/stg_posting_rules/stg_posting_rules_data.php';
const COA_URL  = '/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php';

// Modules that have sub-variants — all others use a single null variant
const MODULE_VARIANTS = {
  cash_flow:               ['inflow', 'outflow'],
  payment_salary_disburse: ['driver', 'cleaner'],
  deduction:               ['driver', 'cleaner'],
  cash_bank:               ['deposit', 'withdrawal', 'receipt', 'payment'],
};

function ruleKey(moduleName, variant) {
  return moduleName + '|' + (variant ?? '__null__');
}

createApp({
  data() {
    return {
      loading:          true,
      saving:           false,

      modules:          [],
      rules:            {},   // keyed by ruleKey(moduleName, variant) → [{entry_type, account_code, account_name, description, variant}]

      accounts:         [],   // full COA for autocomplete
      suggestions:      [],
      activeCell:       null, // {module, variant, idx}
      activeSuggestionIdx: -1,

      expanded:         {},   // moduleName → boolean
      rulesLoaded:      {},   // moduleName → boolean
      savingModule:     {},
      savedFlag:        {},
      errors:           {},
    };
  },

  mounted() {
    this.loadModules();
    this.loadAccounts();
  },

  methods: {

    // ── Loaders ───────────────────────────────────────────────────────────────

    loadModules() {
      this.loading = true;
      axios.get(DATA_URL + '?action=list_modules')
        .then(res => {
          this.modules = res.data;
          this.modules.forEach(m => {
            this.expanded[m.system_name]    = false;
            this.rulesLoaded[m.system_name] = false;
            this.savingModule[m.system_name] = false;
            this.savedFlag[m.system_name]   = false;
            this.errors[m.system_name]      = '';
          });
        })
        .catch(() => {})
        .finally(() => { this.loading = false; });
    },

    loadAccounts() {
      axios.get('/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=list')
        .then(res => { this.accounts = res.data || []; })
        .catch(() => {});
    },

    loadRulesForModule(moduleName) {
      if (this.rulesLoaded[moduleName]) return;
      axios.get(DATA_URL + '?action=get_rules&module=' + encodeURIComponent(moduleName))
        .then(res => {
          const rows = res.data || [];
          const variants = this.getVariants(moduleName);
          // Initialise empty arrays for every variant so tables always render
          variants.forEach(v => {
            const k = ruleKey(moduleName, v);
            if (!this.rules[k]) this.rules[k] = [];
          });
          // Fill in rows from DB
          rows.forEach(row => {
            const k = ruleKey(moduleName, row.variant);
            if (!this.rules[k]) this.rules[k] = [];
            this.rules[k].push({
              variant:      row.variant,
              entry_type:   row.entry_type,
              account_code: row.account_code || '',
              account_name: row.account_name || '',
              description:  row.description  || '',
            });
          });
          // Seed two blank rows for any variant that still has no lines
          variants.forEach(v => {
            const k = ruleKey(moduleName, v);
            if (!this.rules[k] || this.rules[k].length === 0) {
              this.rules[k] = [
                { variant: v, entry_type: 'debit',  account_code: '', account_name: '', description: '' },
                { variant: v, entry_type: 'credit', account_code: '', account_name: '', description: '' },
              ];
            }
          });
          this.rulesLoaded[moduleName] = true;
        })
        .catch(() => { this.rulesLoaded[moduleName] = true; });
    },

    // ── Accordion ─────────────────────────────────────────────────────────────

    toggleExpand(moduleName) {
      this.expanded[moduleName] = !this.expanded[moduleName];
      if (this.expanded[moduleName]) {
        this.loadRulesForModule(moduleName);
      }
    },

    // ── Helpers ───────────────────────────────────────────────────────────────

    getVariants(moduleName) {
      return MODULE_VARIANTS[moduleName] ?? [null];
    },

    getLines(moduleName, variant) {
      const k = ruleKey(moduleName, variant);
      return this.rules[k] || [];
    },

    ruleCount(moduleName) {
      const variants = this.getVariants(moduleName);
      return variants.reduce((sum, v) => sum + this.getLines(moduleName, v).length, 0);
    },

    // ── Line editing ──────────────────────────────────────────────────────────

    addLine(moduleName, variant) {
      const k = ruleKey(moduleName, variant);
      if (!this.rules[k]) this.rules[k] = [];
      this.rules[k].push({ variant, entry_type: 'debit', account_code: '', account_name: '', description: '' });
    },

    removeLine(moduleName, variant, idx) {
      const k = ruleKey(moduleName, variant);
      if (this.rules[k]) this.rules[k].splice(idx, 1);
    },

    // ── COA Autocomplete ──────────────────────────────────────────────────────

    onCodeInput(moduleName, variant, idx, val) {
      this.activeCell = { module: moduleName, variant: variant ?? null, idx };
      this.activeSuggestionIdx = -1;
      const q = (val || '').trim().toLowerCase();
      if (q.length < 1) { this.suggestions = []; return; }
      this.suggestions = this.accounts
        .filter(a => a.account_code.toLowerCase().includes(q) || a.account_name.toLowerCase().includes(q))
        .slice(0, 8);
    },

    closeSuggestions() {
      setTimeout(() => { this.suggestions = []; this.activeCell = null; }, 150);
    },

    moveSuggestion(dir) {
      const max = this.suggestions.length - 1;
      this.activeSuggestionIdx = Math.min(Math.max(this.activeSuggestionIdx + dir, 0), max);
    },

    pickActive(moduleName, variant, idx) {
      if (this.activeSuggestionIdx >= 0 && this.suggestions[this.activeSuggestionIdx]) {
        this.pickSuggestion(moduleName, variant, idx, this.suggestions[this.activeSuggestionIdx]);
      }
    },

    pickSuggestion(moduleName, variant, idx, acct) {
      const k    = ruleKey(moduleName, variant);
      const line = this.rules[k] && this.rules[k][idx];
      if (line) {
        line.account_code = acct.account_code;
        line.account_name = acct.account_name;
      }
      this.suggestions         = [];
      this.activeCell          = null;
      this.activeSuggestionIdx = -1;
    },

    // ── Save ──────────────────────────────────────────────────────────────────

    validateModule(moduleName) {
      const variants = this.getVariants(moduleName);
      for (const v of variants) {
        const lines = this.getLines(moduleName, v);
        if (lines.length === 0) {
          const label = variants.length > 1 ? ' (' + v + ')' : '';
          return 'Add at least one debit and one credit line' + label + '.';
        }
        const hasDebit  = lines.some(l => l.entry_type === 'debit');
        const hasCredit = lines.some(l => l.entry_type === 'credit');
        if (!hasDebit || !hasCredit) {
          const label = variants.length > 1 ? ' (' + v + ')' : '';
          return 'Must have at least one debit and one credit line' + label + '.';
        }
        for (const l of lines) {
          if (!l.account_code.trim()) {
            const label = variants.length > 1 ? ' (' + v + ')' : '';
            return 'All lines must have an account code' + label + '.';
          }
        }
      }
      return null;
    },

    saveModule(moduleName) {
      this.errors[moduleName]    = '';
      this.savedFlag[moduleName] = false;

      const err = this.validateModule(moduleName);
      if (err) { this.errors[moduleName] = err; return; }

      // Flatten all variants into one lines array
      const variants = this.getVariants(moduleName);
      const lines    = [];
      variants.forEach(v => {
        (this.rules[ruleKey(moduleName, v)] || []).forEach(l => {
          lines.push({ ...l, variant: v });
        });
      });

      this.savingModule[moduleName] = true;
      axios.post(DATA_URL + '?action=save', { module_system_name: moduleName, lines })
        .then(() => {
          this.savedFlag[moduleName] = true;
          setTimeout(() => { this.savedFlag[moduleName] = false; }, 2500);
        })
        .catch(err => {
          this.errors[moduleName] = err.response?.data?.error || 'Failed to save. Please try again.';
        })
        .finally(() => { this.savingModule[moduleName] = false; });
    },

    saveAll() {
      this.saving = true;
      const expanded = this.modules.filter(m => this.expanded[m.system_name]);
      if (expanded.length === 0) { this.saving = false; return; }
      const tasks = expanded.map(m => {
        const err = this.validateModule(m.system_name);
        if (err) { this.errors[m.system_name] = err; return Promise.resolve(); }
        const variants = this.getVariants(m.system_name);
        const lines    = [];
        variants.forEach(v => {
          (this.rules[ruleKey(m.system_name, v)] || []).forEach(l => {
            lines.push({ ...l, variant: v });
          });
        });
        this.savingModule[m.system_name] = true;
        return axios.post(DATA_URL + '?action=save', { module_system_name: m.system_name, lines })
          .then(() => {
            this.savedFlag[m.system_name] = true;
            setTimeout(() => { this.savedFlag[m.system_name] = false; }, 2500);
          })
          .catch(e => {
            this.errors[m.system_name] = e.response?.data?.error || 'Failed to save.';
          })
          .finally(() => { this.savingModule[m.system_name] = false; });
      });
      Promise.all(tasks).finally(() => { this.saving = false; });
    },

  },
}).mount('#pr-app');
