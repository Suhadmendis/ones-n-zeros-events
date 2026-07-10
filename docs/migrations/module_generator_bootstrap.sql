-- ============================================================
-- Generate Module tool — self-registration bootstrap
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
--
-- The "Generate Module" tool (modules/settings/modules_mgmt/module_generator/)
-- registers OTHER modules into sys_tms_modules, but can't register itself —
-- chicken-and-egg. This one-time migration does that by hand, following the
-- exact insert-then-patch-ref pattern the tool itself replicates at runtime
-- (see rbac_migration.sql's registration of "role_management" for the
-- original precedent).
--
-- It also grants the Admin role write access on the new module, since
-- guardModuleWrite() (server/supabase.php) blocks ALL writes — including
-- Admin's — for any module with zero sys_role_module_permissions rows.
-- Without this grant, the tool's own first save attempt would 403.
-- ============================================================

INSERT INTO sys_tms_modules (subsection_ref, folder, name, description, sort_order, web_status, app_status, creates_journal_entry, record_status)
SELECT 'MODMGT', 'module_generator', 'Generate Module',
       'Scaffold a new module: DB registration + 6-file skeleton, with the standard toolbar and Reference No. field.',
       (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM sys_tms_modules), 1, NULL, false, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sys_tms_modules WHERE folder = 'module_generator');

UPDATE sys_tms_modules SET ref = 'MO-' || lpad(id::text, 6, '0')
WHERE folder = 'module_generator' AND (ref IS NULL OR ref = '');

INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_import, can_print)
SELECT 'ROL-000001', ref, true, true, true, false, false, false, false, false
FROM sys_tms_modules WHERE folder = 'module_generator'
ON CONFLICT (role_ref, module_ref) DO NOTHING;
