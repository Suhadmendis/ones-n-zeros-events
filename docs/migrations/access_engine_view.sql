-- ============================================================
-- Access Engine views — effective_access = section_enabled AND rbac_permitted
-- Run once in the Supabase SQL Editor, then delete this file.
-- Plan: /Users/akilamendis/.claude/plans/sharded-giggling-adleman.md
-- ============================================================

-- 1. sys_effective_module_access — role x module base layer
--    Exposes section-enable flags + the 8 RBAC action booleans for every
--    (role, module) pair, defaulting to false when a role has no explicit
--    sys_role_module_permissions row for a module (common for non-Admin
--    roles on newer modules — see MODULE_GUIDE.md, only Admin is
--    auto-granted at module-generation time).
-- ============================================================

CREATE OR REPLACE VIEW sys_effective_module_access AS
SELECT
  r.ref                             AS role_ref,
  r.name                            AS role_name,
  m.id                              AS module_id,
  m.ref                             AS module_ref,
  m.name                            AS module_name,
  m.folder                          AS module_folder,
  m.sort_order,
  m.creates_journal_entry,
  m.subsection_ref,
  sub.name                          AS subsection_name,
  sub.folder                        AS subsection_folder,
  sub.icon                          AS subsection_icon,
  sub.color                         AS subsection_color,
  sec.ref                           AS section_ref,
  sec.name                          AS section_name,
  sec.folder                        AS section_folder,
  sec.web_icon, sec.app_icon, sec.web_color, sec.app_color,
  (sec.web_enable = 1)              AS section_web_enabled,
  (sec.app_enable = 1)              AS section_app_enabled,
  COALESCE(rmp.can_view,    false)  AS can_view,
  COALESCE(rmp.can_create,  false)  AS can_create,
  COALESCE(rmp.can_edit,    false)  AS can_edit,
  COALESCE(rmp.can_delete,  false)  AS can_delete,
  COALESCE(rmp.can_approve, false)  AS can_approve,
  COALESCE(rmp.can_export,  false)  AS can_export,
  COALESCE(rmp.can_import,  false)  AS can_import,
  COALESCE(rmp.can_print,   false)  AS can_print
FROM sys_roles r
CROSS JOIN sys_tms_modules m
JOIN sys_tms_subsections sub ON sub.ref = m.subsection_ref
JOIN sys_tms_sections    sec ON sec.ref = sub.section_ref
LEFT JOIN sys_role_module_permissions rmp
       ON rmp.role_ref = r.ref AND rmp.module_ref = m.ref;


-- 2. sys_user_effective_module_access — user x module aggregated layer
--    The layer the engine actually queries. Unions (OR) a user's roles'
--    permissions via bool_or(), and ANDs every action with the section's
--    web_enable kill-switch — a disabled section is inert for every action,
--    not just can_view, even if a role explicitly grants it.
-- ============================================================

CREATE OR REPLACE VIEW sys_user_effective_module_access AS
SELECT
  ur.user_ref,
  ema.module_id, ema.module_ref, ema.module_name, ema.module_folder, ema.sort_order, ema.creates_journal_entry,
  ema.subsection_ref, ema.subsection_name, ema.subsection_folder, ema.subsection_icon, ema.subsection_color,
  ema.section_ref, ema.section_name, ema.section_folder,
  ema.web_icon, ema.app_icon, ema.web_color, ema.app_color,
  bool_and(ema.section_web_enabled)                                 AS section_web_enabled,
  bool_and(ema.section_app_enabled)                                 AS section_app_enabled,
  bool_or(ema.can_view)                                             AS can_view,
  bool_or(ema.can_create)                                           AS can_create,
  bool_or(ema.can_edit)                                             AS can_edit,
  bool_or(ema.can_delete)                                           AS can_delete,
  bool_or(ema.can_approve)                                          AS can_approve,
  bool_or(ema.can_export)                                           AS can_export,
  bool_or(ema.can_import)                                           AS can_import,
  bool_or(ema.can_print)                                            AS can_print,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_view))     AS effective_can_view,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_create))   AS effective_can_create,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_edit))     AS effective_can_edit,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_delete))   AS effective_can_delete,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_approve))  AS effective_can_approve,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_export))   AS effective_can_export,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_import))   AS effective_can_import,
  (bool_and(ema.section_web_enabled) AND bool_or(ema.can_print))    AS effective_can_print
FROM sys_user_roles ur
JOIN sys_effective_module_access ema ON ema.role_ref = ur.role_ref
GROUP BY ur.user_ref, ema.module_id, ema.module_ref, ema.module_name, ema.module_folder, ema.sort_order, ema.creates_journal_entry,
         ema.subsection_ref, ema.subsection_name, ema.subsection_folder, ema.subsection_icon, ema.subsection_color,
         ema.section_ref, ema.section_name, ema.section_folder,
         ema.web_icon, ema.app_icon, ema.web_color, ema.app_color;


-- 3. Expose both views to PostgREST's anon role (same role every other
--    table read in this app already goes through via SUPABASE_ANON_KEY).
-- ============================================================

GRANT SELECT ON sys_effective_module_access      TO anon;
GRANT SELECT ON sys_user_effective_module_access TO anon;
