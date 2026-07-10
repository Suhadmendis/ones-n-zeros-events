-- ============================================================
-- RBAC Migration — flat role/permission model → normalized RBAC
-- Run once in the Supabase SQL Editor, then delete this file.
-- Plan: /Users/akilamendis/.claude/plans/eventual-mixing-hippo.md
-- ============================================================

-- 1. New tables: sys_users, sys_roles, sys_user_roles, sys_role_module_permissions
-- ============================================================

CREATE TABLE IF NOT EXISTS sys_users (
    id            bigserial PRIMARY KEY,
    ref           varchar(20)  NOT NULL UNIQUE,
    username      varchar(150) NOT NULL UNIQUE,
    password_hash varchar(255) NOT NULL,
    full_name     varchar(200) NOT NULL,
    email         varchar(150) NOT NULL UNIQUE,
    employee_ref  varchar(20),                 -- reserved for a future HR/staff link; unused, no FK today
    record_status varchar(20)  NOT NULL DEFAULT 'active',
    created_at    timestamptz  NOT NULL DEFAULT now(),
    updated_at    timestamptz  NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_roles (
    id            bigserial PRIMARY KEY,
    ref           varchar(20)  NOT NULL UNIQUE,
    name          varchar(100) NOT NULL UNIQUE,
    description   text,
    record_status varchar(20)  NOT NULL DEFAULT 'active',
    created_at    timestamptz  NOT NULL DEFAULT now(),
    updated_at    timestamptz  NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_user_roles (
    id         bigserial PRIMARY KEY,
    ref        varchar(20) NOT NULL UNIQUE,
    user_ref   varchar(20) NOT NULL REFERENCES sys_users(ref) ON DELETE CASCADE,
    role_ref   varchar(20) NOT NULL REFERENCES sys_roles(ref) ON DELETE CASCADE,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (user_ref, role_ref)
);

CREATE TABLE IF NOT EXISTS sys_role_module_permissions (
    id          bigserial PRIMARY KEY,
    ref         varchar(20) NOT NULL UNIQUE,
    role_ref    varchar(20) NOT NULL REFERENCES sys_roles(ref) ON DELETE CASCADE,
    module_ref  varchar(20) NOT NULL REFERENCES sys_tms_modules(ref) ON DELETE CASCADE,
    can_view    boolean NOT NULL DEFAULT false,
    can_create  boolean NOT NULL DEFAULT false,
    can_edit    boolean NOT NULL DEFAULT false,
    can_delete  boolean NOT NULL DEFAULT false,
    can_approve boolean NOT NULL DEFAULT false,
    can_export  boolean NOT NULL DEFAULT false,
    can_import  boolean NOT NULL DEFAULT false,
    can_print   boolean NOT NULL DEFAULT false,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    UNIQUE (role_ref, module_ref)
);

CREATE INDEX IF NOT EXISTS idx_sys_user_roles_user_ref ON sys_user_roles(user_ref);
CREATE INDEX IF NOT EXISTS idx_sys_user_roles_role_ref ON sys_user_roles(role_ref);
CREATE INDEX IF NOT EXISTS idx_sys_rmp_role_ref   ON sys_role_module_permissions(role_ref);
CREATE INDEX IF NOT EXISTS idx_sys_rmp_module_ref ON sys_role_module_permissions(module_ref);


-- 2. Ref-generation triggers (mirrors sections/subsections/modules' sequence-backed refs)
-- ============================================================

CREATE SEQUENCE IF NOT EXISTS sys_roles_ref_seq;
CREATE OR REPLACE FUNCTION sys_roles_set_ref() RETURNS trigger AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'ROL-' || lpad(nextval('sys_roles_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$ LANGUAGE plpgsql;
DROP TRIGGER IF EXISTS trg_sys_roles_ref ON sys_roles;
CREATE TRIGGER trg_sys_roles_ref BEFORE INSERT ON sys_roles
  FOR EACH ROW EXECUTE FUNCTION sys_roles_set_ref();

CREATE SEQUENCE IF NOT EXISTS sys_user_roles_ref_seq;
CREATE OR REPLACE FUNCTION sys_user_roles_set_ref() RETURNS trigger AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'UXR-' || lpad(nextval('sys_user_roles_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$ LANGUAGE plpgsql;
DROP TRIGGER IF EXISTS trg_sys_user_roles_ref ON sys_user_roles;
CREATE TRIGGER trg_sys_user_roles_ref BEFORE INSERT ON sys_user_roles
  FOR EACH ROW EXECUTE FUNCTION sys_user_roles_set_ref();

CREATE SEQUENCE IF NOT EXISTS sys_rmp_ref_seq;
CREATE OR REPLACE FUNCTION sys_rmp_set_ref() RETURNS trigger AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'RMP-' || lpad(nextval('sys_rmp_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$ LANGUAGE plpgsql;
DROP TRIGGER IF EXISTS trg_sys_rmp_ref ON sys_role_module_permissions;
CREATE TRIGGER trg_sys_rmp_ref BEFORE INSERT ON sys_role_module_permissions
  FOR EACH ROW EXECUTE FUNCTION sys_rmp_set_ref();

-- sys_users.ref is generated app-side via consumeNextReference('create_user') — no trigger needed.


-- 3. Register the new "Role management" module (User Management subsection)
-- ============================================================

-- sys_tms_modules.ref has no auto-generating trigger (unlike the RBAC tables
-- above) — it must be set explicitly here, derived from the row's own id.
INSERT INTO sys_tms_modules (subsection_ref, folder, name, description, sort_order, web_status, app_status, record_status)
SELECT 'USRMGT', 'role_management', 'Role management', 'Create, rename, and deactivate roles.',
       (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM sys_tms_modules), 1, 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sys_tms_modules WHERE folder = 'role_management');

UPDATE sys_tms_modules
SET ref = 'MO-' || lpad(id::text, 6, '0')
WHERE folder = 'role_management' AND (ref IS NULL OR ref = '');


-- 4. Seed the 4 default roles
-- ============================================================

INSERT INTO sys_roles (name, description) VALUES
 ('Admin',    'Full access to every module and action, system-wide.'),
 ('Manager',  'View everywhere; create/edit/approve/export/print on Operations & Finance.'),
 ('Operator', 'View everywhere; create/edit on Operations only.'),
 ('Viewer',   'Read-only access to all modules.')
ON CONFLICT (name) DO NOTHING;


-- 5. Seed default per-role, per-module permissions
--    Starting points only — tunable afterward via the Role Management grid UI.
-- ============================================================

-- Admin: every action, every module
INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view, can_create, can_edit, can_delete, can_approve, can_export, can_import, can_print)
SELECT (SELECT ref FROM sys_roles WHERE name = 'Admin'), m.ref, true, true, true, true, true, true, true, true
FROM sys_tms_modules m
ON CONFLICT (role_ref, module_ref) DO NOTHING;

-- Viewer: view-only, every module
INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view)
SELECT (SELECT ref FROM sys_roles WHERE name = 'Viewer'), m.ref, true
FROM sys_tms_modules m
ON CONFLICT (role_ref, module_ref) DO NOTHING;

-- Manager: view everywhere; broader rights on Operations (OPS) & Finance (FIN)
INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view, can_create, can_edit, can_approve, can_export, can_print)
SELECT (SELECT ref FROM sys_roles WHERE name = 'Manager'), m.ref, true,
       s.ref IN ('OPS', 'FIN'), s.ref IN ('OPS', 'FIN'), s.ref IN ('OPS', 'FIN'),
       s.ref IN ('OPS', 'FIN'), s.ref IN ('OPS', 'FIN')
FROM sys_tms_modules m
JOIN sys_tms_subsections sub ON sub.ref = m.subsection_ref
JOIN sys_tms_sections s ON s.ref = sub.section_ref
ON CONFLICT (role_ref, module_ref) DO NOTHING;

-- Operator: view everywhere; create/edit only on Operations (OPS)
INSERT INTO sys_role_module_permissions (role_ref, module_ref, can_view, can_create, can_edit)
SELECT (SELECT ref FROM sys_roles WHERE name = 'Operator'), m.ref, true, s.ref = 'OPS', s.ref = 'OPS'
FROM sys_tms_modules m
JOIN sys_tms_subsections sub ON sub.ref = m.subsection_ref
JOIN sys_tms_sections s ON s.ref = sub.section_ref
ON CONFLICT (role_ref, module_ref) DO NOTHING;


-- 6. Migrate sys_app_users → sys_users
--    (username seeded = email; password_hash copied as-is, including the one
--    known plaintext value — rehashed separately by scripts/rehash_plaintext_passwords.php)
-- ============================================================

INSERT INTO sys_users (ref, username, password_hash, full_name, email, record_status, created_at, updated_at)
SELECT ref, email, password, trim(first_name || ' ' || last_name), email,
       CASE WHEN status = 'active' THEN 'active' ELSE 'inactive' END,
       created_at, updated_at
FROM sys_app_users
ON CONFLICT (ref) DO NOTHING;

-- sys_app_users.role (free text) → sys_user_roles
INSERT INTO sys_user_roles (user_ref, role_ref)
SELECT u.ref, r.ref
FROM sys_app_users u
JOIN sys_roles r ON lower(r.name) = lower(u.role::text)
ON CONFLICT (user_ref, role_ref) DO NOTHING;


-- 7. Retire old tables (kept as a rollback safety net, not dropped yet)
-- ============================================================

ALTER TABLE IF EXISTS sys_app_users RENAME TO sys_app_users_legacy;
ALTER TABLE IF EXISTS sys_user_permissions RENAME TO sys_user_permissions_legacy;

-- Follow-up (separate migration, after a verification window):
--   DROP TABLE sys_app_users_legacy, sys_user_permissions_legacy;
