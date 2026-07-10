-- ============================================================
-- Admin permissions backfill
-- Run once in the Supabase SQL Editor, then delete this file.
--
-- guardModuleWrite() (server/supabase.php) blocks ALL writes — including
-- Admin's — for any module with zero sys_role_module_permissions rows for
-- the acting user's role(s). As of 2026-07-02, sys_role_module_permissions
-- has rows for Manager/Operator/Viewer on every module (seeded same-day,
-- view-only) but ZERO rows for Admin on ANY of the 148 modules — meaning
-- the Admin role currently has no write access anywhere in the app.
-- module_generator_bootstrap.sql hit this same bug for a single module
-- and documented the root cause; this backfills the same grant for every
-- other module that's missing it.
-- ============================================================

INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_import, can_print)
SELECT (SELECT ref FROM sys_roles WHERE name = 'Admin'), m.ref, true, true, true, true, true, true, true, true
FROM sys_tms_modules m
ON CONFLICT (role_ref, module_ref) DO NOTHING;
