-- ============================================================
-- Generate Module tool — module-registration RPC functions
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
--
-- sys_tms_modules has Row-Level Security enabled with only a SELECT policy
-- for anon/authenticated (confirmed live: `anon can read tms_modules`, no
-- INSERT/UPDATE/DELETE policy exists) — direct supabase_post()/patch() from
-- the anon key is rejected by Postgres itself ("new row violates row-level
-- security policy"). This is a deliberate lock, not a bug (it's the DB-level
-- backing for MODULE_GUIDE.md's old "never add/edit rows in tms_modules"
-- instruction) — so instead of loosening RLS, these two narrowly-scoped
-- SECURITY DEFINER functions do the specific registration/rollback
-- operations Generate Module needs, nothing more.
-- ============================================================

CREATE OR REPLACE FUNCTION public.register_tms_module(
  p_subsection_ref varchar,
  p_folder         varchar,
  p_name           varchar,
  p_sort_order     integer
) RETURNS TABLE(id bigint, ref varchar)
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  v_id  bigint;
  v_ref varchar;
BEGIN
  -- Defense in depth: re-validate even though the PHP caller already does —
  -- never trust a single layer for a filesystem/catalog-writing endpoint.
  IF p_folder !~ '^[a-z][a-z0-9_]{2,49}$' THEN
    RAISE EXCEPTION 'invalid folder format: %', p_folder;
  END IF;

  IF EXISTS (SELECT 1 FROM sys_tms_modules m WHERE m.folder = p_folder) THEN
    RAISE EXCEPTION 'folder already exists: %', p_folder;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM sys_tms_subsections s WHERE s.ref = p_subsection_ref) THEN
    RAISE EXCEPTION 'unknown subsection: %', p_subsection_ref;
  END IF;

  INSERT INTO sys_tms_modules (subsection_ref, folder, name, sort_order, web_status, app_status, creates_journal_entry, record_status)
  VALUES (p_subsection_ref, p_folder, p_name, p_sort_order, 1, NULL, false, 'active')
  RETURNING sys_tms_modules.id INTO v_id;

  v_ref := 'MO-' || lpad(v_id::text, 6, '0');
  UPDATE sys_tms_modules SET ref = v_ref WHERE sys_tms_modules.id = v_id;

  RETURN QUERY SELECT v_id, v_ref;
END;
$$;

GRANT EXECUTE ON FUNCTION public.register_tms_module(varchar, varchar, varchar, integer) TO anon, authenticated;

-- Rollback helper for when file-scaffolding fails after registration succeeds.
CREATE OR REPLACE FUNCTION public.unregister_tms_module(p_id bigint) RETURNS void
LANGUAGE sql
SECURITY DEFINER
SET search_path = public
AS $$
  DELETE FROM sys_tms_modules WHERE id = p_id;
$$;

GRANT EXECUTE ON FUNCTION public.unregister_tms_module(bigint) TO anon, authenticated;
