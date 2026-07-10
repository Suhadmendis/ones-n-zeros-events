-- ============================================================
-- Fix: sys_role_module_permissions.ref is NOT NULL + UNIQUE with no
-- default, but both the Generate Module tool's Admin-permission-grant
-- step (module_generator_lib.php) and the existing Role -> Module
-- Permissions screen (user_permissions_data.php savePermissions()) insert
-- rows without ever supplying ref, so both insert paths fail with
-- "null value in column ref violates not-null constraint."
--
-- Fix at the DB level with a BEFORE INSERT trigger rather than app-code
-- changes in either caller, since both are affected. id's own
-- nextval() default is already populated by the time a BEFORE INSERT
-- trigger runs, so NEW.id is safe to read here (no double sequence
-- consumption, ref always matches id).
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
-- ============================================================

CREATE OR REPLACE FUNCTION public.sys_role_module_permissions_set_ref()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'RMP-' || lpad(NEW.id::text, 7, '0');
  END IF;
  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_sys_role_module_permissions_set_ref ON public.sys_role_module_permissions;

CREATE TRIGGER trg_sys_role_module_permissions_set_ref
  BEFORE INSERT ON public.sys_role_module_permissions
  FOR EACH ROW
  EXECUTE FUNCTION public.sys_role_module_permissions_set_ref();
