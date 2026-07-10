-- ============================================================
-- Generate Module tool — business-table DDL RPC functions
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
--
-- PostgREST can't run CREATE TABLE/DROP TABLE over the REST API, so the
-- "Generate Module" tool's field builder needs a narrowly-scoped SECURITY
-- DEFINER function to create (and, on rollback, drop) a module's business
-- table — same reasoning as register_tms_module/unregister_tms_module in
-- module_generator_rpc.sql, just for DDL instead of a locked-down insert.
--
-- Both functions are owned by postgres, which means CREATE TABLE executed
-- inside create_module_table() picks up this project's schema-wide
-- `ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public` grant —
-- confirmed live — so the new table is immediately readable/writable by
-- anon/authenticated with no separate GRANT step needed.
-- ============================================================

CREATE OR REPLACE FUNCTION public.create_module_table(
  p_table   varchar,
  p_columns jsonb
) RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  v_col      jsonb;
  v_name     text;
  v_type     text;
  v_pgtype   text;
  v_coldefs  text := '';
  v_reserved text[] := ARRAY['id', 'ref', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by'];
BEGIN
  -- Defense in depth: re-validate even though the PHP caller already does —
  -- never trust a single layer for a filesystem/catalog-writing endpoint
  -- (same stance as register_tms_module).
  IF p_table !~ '^m_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  IF to_regclass('public.' || p_table) IS NOT NULL THEN
    RAISE EXCEPTION 'table already exists: %', p_table;
  END IF;

  FOR v_col IN SELECT * FROM jsonb_array_elements(COALESCE(p_columns, '[]'::jsonb))
  LOOP
    v_name := v_col ->> 'name';
    v_type := v_col ->> 'type';

    IF v_name IS NULL OR v_name !~ '^[a-z][a-z0-9_]{1,49}$' THEN
      RAISE EXCEPTION 'invalid column name: %', v_name;
    END IF;

    IF v_name = ANY (v_reserved) THEN
      RAISE EXCEPTION 'reserved column name: %', v_name;
    END IF;

    v_pgtype := CASE v_type
      WHEN 'text'     THEN 'varchar(255)'
      WHEN 'dropdown' THEN 'varchar(100)'
      WHEN 'textarea' THEN 'text'
      WHEN 'number'   THEN 'integer'
      WHEN 'decimal'  THEN 'numeric(14,2)'
      WHEN 'date'     THEN 'date'
      WHEN 'checkbox' THEN 'boolean'
      ELSE NULL
    END;

    IF v_pgtype IS NULL THEN
      RAISE EXCEPTION 'unknown field type: %', v_type;
    END IF;

    -- %I quotes v_name safely; v_pgtype comes from the whitelist CASE above,
    -- never from raw client input, so this concatenation carries no
    -- injection risk despite not being parameterized itself.
    v_coldefs := v_coldefs || format('%I %s, ', v_name, v_pgtype);
  END LOOP;

  EXECUTE format(
    'CREATE TABLE %I (
       id bigserial PRIMARY KEY,
       ref varchar(20) UNIQUE NOT NULL,
       %s
       status varchar(20) NOT NULL DEFAULT ''active'',
       created_by text,
       updated_by text,
       created_at timestamptz NOT NULL DEFAULT now(),
       updated_at timestamptz NOT NULL DEFAULT now()
     )',
    p_table, v_coldefs
  );
END;
$$;

GRANT EXECUTE ON FUNCTION public.create_module_table(varchar, jsonb) TO anon, authenticated;

-- Rollback helper for when a later generation step (number series, permission
-- row, or file scaffolding) fails after the table was already created.
CREATE OR REPLACE FUNCTION public.drop_module_table(p_table varchar) RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF p_table !~ '^m_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  EXECUTE format('DROP TABLE IF EXISTS %I', p_table);
END;
$$;

GRANT EXECUTE ON FUNCTION public.drop_module_table(varchar) TO anon, authenticated;
