// user_permissions.js — Role → module permission grid Vue app

const { createApp } = Vue;

const RMP_ACTIONS = [
  { key: 'can_view',    label: 'View' },
  { key: 'can_create',  label: 'Create' },
  { key: 'can_edit',    label: 'Edit' },
  { key: 'can_delete',  label: 'Delete' },
  { key: 'can_approve', label: 'Approve' },
  { key: 'can_export',  label: 'Export' },
  { key: 'can_import',  label: 'Import' },
  { key: 'can_print',   label: 'Print' },
];

createApp({
  data() {
    return {
      systemName:     SYSTEM_NAME,
      title:          '',
      loadingModules: false,
      saving:         false,
      saved:          false,
      error:          '',
      actions:        RMP_ACTIONS,
      roles:          [],
      selectedRoleRef: '',
      modules:        [],
      permissions:    {}, // module_ref => { can_view: bool, ... }
    };
  },

  computed: {
    sections() {
      const seen = [];
      for (const m of this.modules) {
        if (!seen.includes(m.section)) seen.push(m.section);
      }
      return seen;
    },
  },

  mounted() {
    this.fetchTitle();
    this.fetchRoles();
    this.fetchModules();
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(() => { this.title = 'Role Permissions'; });
    },

    fetchRoles() {
      axios.get('/modules/company_management/user_management/user_permissions/user_permissions_data.php?action=list_roles')
        .then(res => { this.roles = res.data; })
        .catch(err => { this.error = 'Failed to load roles.'; console.error(err); });
    },

    fetchModules() {
      this.loadingModules = true;
      axios.get('/modules/company_management/user_management/user_permissions/user_permissions_data.php?action=list_modules')
        .then(res => { this.modules = res.data; })
        .catch(err => { this.error = 'Failed to load modules.'; console.error(err); })
        .finally(() => { this.loadingModules = false; });
    },

    onRoleChange() {
      this.saved = false;
      this.error = '';
      this.permissions = {};
      if (!this.selectedRoleRef) return;
      this.loadingModules = true;
      axios.get('/modules/company_management/user_management/user_permissions/user_permissions_data.php?action=get_permissions&role_ref=' + encodeURIComponent(this.selectedRoleRef))
        .then(res => { this.permissions = res.data; })
        .catch(err => { this.error = 'Failed to load permissions.'; console.error(err); })
        .finally(() => { this.loadingModules = false; });
    },

    isChecked(moduleRef, action) {
      return !!(this.permissions[moduleRef] && this.permissions[moduleRef][action]);
    },

    toggle(moduleRef, action) {
      const current = this.permissions[moduleRef] || {};
      this.permissions = {
        ...this.permissions,
        [moduleRef]: { ...current, [action]: !current[action] },
      };
    },

    isRowAllChecked(moduleRef) {
      return this.actions.every(a => this.isChecked(moduleRef, a.key));
    },

    toggleRow(moduleRef) {
      const setTo = !this.isRowAllChecked(moduleRef);
      const row = {};
      this.actions.forEach(a => { row[a.key] = setTo; });
      this.permissions = { ...this.permissions, [moduleRef]: row };
    },

    sectionModules(section) {
      return this.modules.filter(m => m.section === section);
    },

    isColumnAllChecked(section, action) {
      const mods = this.sectionModules(section);
      return mods.length > 0 && mods.every(m => this.isChecked(m.ref, action));
    },

    toggleColumn(section, action) {
      const setTo   = !this.isColumnAllChecked(section, action);
      const updated = { ...this.permissions };
      this.sectionModules(section).forEach(m => {
        updated[m.ref] = { ...(updated[m.ref] || {}), [action]: setTo };
      });
      this.permissions = updated;
    },

    isSectionAllChecked(section) {
      const mods = this.sectionModules(section);
      return mods.length > 0 && mods.every(m => this.isRowAllChecked(m.ref));
    },

    toggleSection(section) {
      const setTo   = !this.isSectionAllChecked(section);
      const row     = {};
      this.actions.forEach(a => { row[a.key] = setTo; });
      const updated = { ...this.permissions };
      this.sectionModules(section).forEach(m => {
        updated[m.ref] = { ...row };
      });
      this.permissions = updated;
    },

    sectionIcon(section)  { return this.modules.find(m => m.section === section)?.section_icon  ?? 'bi-circle'; },
    sectionColor(section) { return this.modules.find(m => m.section === section)?.section_color ?? 'secondary'; },

    // Bootstrap's bg-{color}-opacity-10 utility only exists for the 8 theme colors, not
    // the extended palette (indigo/purple/pink/orange/teal), so mix the tint inline.
    sectionHeaderStyle(section) {
      const color = this.sectionColor(section);
      return { backgroundColor: `color-mix(in srgb, var(--bs-${color}) 10%, white)` };
    },

    onSave() {
      if (!this.selectedRoleRef) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      axios.post('/modules/company_management/user_management/user_permissions/user_permissions_data.php?action=save', {
        role_ref:    this.selectedRoleRef,
        permissions: this.permissions,
      })
        .then(() => { this.saved = true; })
        .catch(err => { this.error = err.response?.data?.error || 'Failed to save permissions.'; console.error(err); })
        .finally(() => { this.saving = false; });
    },
  },
}).mount('#up-app');
