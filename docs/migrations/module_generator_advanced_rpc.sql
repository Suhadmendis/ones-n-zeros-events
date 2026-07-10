-- ============================================================
-- Generate Module tool — advanced module types (report / header+lines) and
-- GL-posting toggle support.
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
--
-- Extends register_tms_module() to record module_type and
-- creates_journal_entry at registration time (both columns already exist on
-- sys_tms_modules — confirmed live, previously always NULL/false — this is
-- the first thing that actually sets them), and adds a line-table DDL RPC
-- for the header+line-items module type, mirroring create_module_table /
-- drop_module_table in module_generator_table_rpc.sql but for t_* child
-- tables (schema modeled exactly on the live t_work_order_lines table:
-- id, ref, <parent fk>, line_no, <fields>, created_by, updated_by,
-- created_at, updated_at — no status column, ref is app-generated as
-- '{parent_ref}-L{n}' rather than drawn from a number series).
-- ============================================================

-- Postgres treats a changed parameter list as a new overload rather than a
-- replacement of the 4-arg original from module_generator_rpc.sql — drop it
-- first so PostgREST never has two register_tms_module candidates to choose
-- between.
DROP FUNCTION IF EXISTS public.register_tms_module(varchar, varchar, varchar, integer);

CREATE OR REPLACE FUNCTION public.register_tms_module(
  p_subsection_ref        varchar,
  p_folder                varchar,
  p_name                  varchar,
  p_sort_order            integer,
  p_module_type           varchar DEFAULT 'entry',
  p_creates_journal_entry boolean DEFAULT false
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

  IF p_module_type NOT IN ('entry', 'report', 'header_detail') THEN
    RAISE EXCEPTION 'invalid module_type: %', p_module_type;
  END IF;

  IF EXISTS (SELECT 1 FROM sys_tms_modules m WHERE m.folder = p_folder) THEN
    RAISE EXCEPTION 'folder already exists: %', p_folder;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM sys_tms_subsections s WHERE s.ref = p_subsection_ref) THEN
    RAISE EXCEPTION 'unknown subsection: %', p_subsection_ref;
  END IF;

  INSERT INTO sys_tms_modules (subsection_ref, folder, name, sort_order, web_status, app_status, creates_journal_entry, record_status, module_type)
  VALUES (p_subsection_ref, p_folder, p_name, p_sort_order, 1, NULL, p_creates_journal_entry, 'active', p_module_type)
  RETURNING sys_tms_modules.id INTO v_id;

  v_ref := 'MO-' || lpad(v_id::text, 6, '0');
  UPDATE sys_tms_modules SET ref = v_ref WHERE sys_tms_modules.id = v_id;

  RETURN QUERY SELECT v_id, v_ref;
END;
$$;

GRANT EXECUTE ON FUNCTION public.register_tms_module(varchar, varchar, varchar, integer, varchar, boolean) TO anon, authenticated;

-- ------------------------------------------------------------------------
-- Line (child) table DDL, for the header+line-items module type.
-- ------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION public.create_module_line_table(
  p_table            varchar,
  p_parent_fk_column varchar,
  p_columns          jsonb
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
  v_reserved text[] := ARRAY['id', 'ref', 'line_no', 'created_at', 'updated_at', 'created_by', 'updated_by'];
BEGIN
  IF p_table !~ '^t_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  IF p_parent_fk_column !~ '^[a-z][a-z0-9_]{1,49}$' THEN
    RAISE EXCEPTION 'invalid parent fk column format: %', p_parent_fk_column;
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

    IF v_name = ANY (v_reserved) OR v_name = p_parent_fk_column THEN
      RAISE EXCEPTION 'reserved or duplicate column name: %', v_name;
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

    v_coldefs := v_coldefs || format('%I %s, ', v_name, v_pgtype);
  END LOOP;

  EXECUTE format(
    'CREATE TABLE %I (
       id bigserial PRIMARY KEY,
       ref varchar(40) UNIQUE NOT NULL,
       %I varchar(20) NOT NULL,
       line_no integer NOT NULL,
       %s
       created_by text,
       updated_by text,
       created_at timestamptz NOT NULL DEFAULT now(),
       updated_at timestamptz NOT NULL DEFAULT now()
     )',
    p_table, p_parent_fk_column, v_coldefs
  );
END;
$$;

GRANT EXECUTE ON FUNCTION public.create_module_line_table(varchar, varchar, jsonb) TO anon, authenticated;

-- Rollback helper for when a later generation step fails after the line
-- table was already created.
CREATE OR REPLACE FUNCTION public.drop_module_line_table(p_table varchar) RETURNS void
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF p_table !~ '^t_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  EXECUTE format('DROP TABLE IF EXISTS %I', p_table);
END;
$$;

GRANT EXECUTE ON FUNCTION public.drop_module_line_table(varchar) TO anon, authenticated;
