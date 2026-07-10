-- ============================================================
-- Fix: sys_tms_module_documentation.id is a GENERATED ALWAYS AS
-- IDENTITY column whose backing sequence is stuck at 1, even
-- though the table already has rows up to id=44. Any INSERT that
-- lets the identity default fire (i.e. every normal save through
-- the Module Documentation editor, or via the REST API) fails with:
--   duplicate key value violates unique constraint
--   "sys_tms_module_documentation_pkey"
-- because Postgres tries to reuse id=1, which already exists.
--
-- This resyncs the identity sequence to continue after the current
-- max id, the standard fix for a sequence that fell behind (e.g.
-- after a bulk/manual import that inserted explicit id values).
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
-- ============================================================

SELECT setval(
    pg_get_serial_sequence('public.sys_tms_module_documentation', 'id'),
    COALESCE((SELECT MAX(id) FROM public.sys_tms_module_documentation), 0) + 1,
    false
);
