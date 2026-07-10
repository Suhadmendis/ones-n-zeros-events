-- ============================================================
-- Add flora_notes to t_quotation_lines
--
-- The quotation print layout was restyled to match the client's
-- original document exactly, which has a 4th line-item column
-- ("Fresh varieties and Foliage") listing the flora used per
-- arrangement. That column has no backing field in the schema —
-- this adds one as a plain multi-line text column (one variety per
-- line, rendered as separate lines on print).
-- Run in Supabase SQL Editor (or via psql "$DATABASE_URL")
-- ============================================================

ALTER TABLE public.t_quotation_lines
    ADD COLUMN IF NOT EXISTS flora_notes text;
