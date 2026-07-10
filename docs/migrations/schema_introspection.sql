-- ============================================================
-- Schema Introspection (read-only)
-- Run in Supabase SQL Editor
--
-- Backs the TABLES.php admin page: lists every table in the
-- `public` schema with its column definitions, read live from
-- information_schema on every call (nothing is cached or hardcoded).
--
-- SECURITY DEFINER only to reach information_schema (which the
-- anon/authenticated roles can't see directly) — the function body
-- itself is a fixed read-only query against catalog metadata, never
-- row data from any application table, and search_path is pinned
-- to prevent search-path hijacking.
-- ============================================================

CREATE OR REPLACE FUNCTION public.get_schema_overview()
RETURNS jsonb
LANGUAGE sql
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT COALESCE(jsonb_agg(t ORDER BY t->>'table_name'), '[]'::jsonb)
  FROM (
    SELECT jsonb_build_object(
      'table_name', c.table_name,
      'columns', jsonb_agg(
        jsonb_build_object(
          'column_name',    c.column_name,
          'data_type',      c.data_type,
          'is_nullable',    c.is_nullable,
          'column_default', c.column_default
        ) ORDER BY c.ordinal_position
      )
    ) AS t
    FROM information_schema.columns c
    WHERE c.table_schema = 'public'
    GROUP BY c.table_name
  ) sub;
$$;

GRANT EXECUTE ON FUNCTION public.get_schema_overview() TO anon, authenticated;
