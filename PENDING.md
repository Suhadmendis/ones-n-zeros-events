# Pending Work

Running list of known outstanding work, most recent first. When an item is finished, delete it from here rather than marking it done — this file is a to-do list, not a changelog (see `PROGRESS.md` for history).

## RBAC migration (2026-07-02)

Full plan/history: `/Users/akilamendis/.claude/plans/eventual-mixing-hippo.md`

The flat single-role permission model (`sys_app_users`/`sys_user_permissions`) was replaced with a normalized RBAC system (`sys_users`, `sys_roles`, `sys_user_roles`, `sys_role_module_permissions` — multi-role per user, 8 granular actions per module: view/create/edit/delete/approve/export/import/print). The DB migration has been run against the live Supabase DB and the app code updated to match. Still outstanding:

- **Nothing from this work is committed to git yet.** Review the diff and commit when ready.
- **Only a coarse permission check is live.** `server/supabase.php`'s `guardModuleWrite()` blocks a write if the user has *zero* write-type permission on the target module, but can't tell *which* action (create vs. edit vs. delete vs. approve...) is being attempted. Precise checks need `requireModulePermission('can_X')` (see `server/general/rbac.php`) added to each `_data.php`'s individual action branches and each `_print.php`'s top — roughly 108 `_data.php` + 35 `_print.php` files. Roll out section-by-section (Company Management → Finance → Operations → Master Files → Reports → Settings), smoke-testing each batch against the 4 seeded roles (Admin/Manager/Operator/Viewer) before moving to the next section. Action-name → permission mapping: `list/get/report/summary/...` → `can_view`, `save` → `can_create`, `update/toggle` → `can_edit`, `post` → `can_approve` (only `journal_entries` uses this today), `can_delete`/`can_import` are forward-looking (no module implements delete/import yet).
- **`MODULE_GUIDE.md` doesn't document the permission check yet** — bake `requireModulePermission()` into the standard module-scaffolding template so new modules don't regrow this gap.
- **Cleanup, low priority, do once the new system's been running fine for a while:**
  - `docs/migrations/rbac_migration.sql` and `scripts/rehash_plaintext_passwords.php` have already been run — safe to delete.
  - `sys_app_users`/`sys_user_permissions` were renamed to `_legacy`, not dropped — drop them once confident nothing references them.

## Test suite

- **Playwright suite is currently broken and running 0 tests** — every file under `tests/modules/**/*.spec.js` imports `'../../../helpers/db.js'` with a wrong relative depth (the real file is `tests/helpers/db.js`). Fix the import path across all spec files. This predates the RBAC work, unrelated.
- No automated tests exist yet for: Operations → Expenses (fuel_entry, general_expense_entry, vehicle_expense_entry, cash_flow), Operations → Staff & Payroll (employee_advance, deduction, loan, cleaner_salary_settlement, driver_salary_settlement, payment_salary_disburse), Operations → Trip Management (trip_running_chart), Finance → Receivables & Payables (accounts_payable, accounts_receivable, cash_bank), and Settings modules generally.
- No validation-path tests anywhere (empty-form rejection, server-side required-field checks) — existing specs only cover the happy path.
- Any future tests for `create_user`/`edit_deactivate_user`/`password_change` need to target the new `sys_users` schema (`full_name` not `first_name`/`last_name`, `record_status` not `status`, `role_refs` array not a single `role` string) — those forms changed as part of the RBAC work above.

## Settings modules — still placeholders

Roughly 50 modules under `modules/settings/*` (`stg_*` folders — Company Settings, Financial Settings, Number Series config, Approval Workflow, Notifications, Backup, Audit, API Settings) are registered in the DB and scaffolded in the folder structure, but their `.php` files are literal `<?php /* Under construction */ ?>` stubs with no `.js`/`_data.php` behind them. This is real unbuilt functionality, not a bug.
