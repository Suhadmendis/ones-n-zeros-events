


SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;


COMMENT ON SCHEMA "public" IS 'standard public schema';



CREATE EXTENSION IF NOT EXISTS "pg_stat_statements" WITH SCHEMA "extensions";






CREATE EXTENSION IF NOT EXISTS "pgcrypto" WITH SCHEMA "extensions";






CREATE EXTENSION IF NOT EXISTS "supabase_vault" WITH SCHEMA "vault";






CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA "extensions";






CREATE TYPE "public"."advance_payment_recipient_type" AS ENUM (
    'driver',
    'cleaner'
);


ALTER TYPE "public"."advance_payment_recipient_type" OWNER TO "postgres";


CREATE TYPE "public"."cleaner_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."cleaner_status" OWNER TO "postgres";


CREATE TYPE "public"."driver_status" AS ENUM (
    'active',
    'inactive',
    'on_leave'
);


ALTER TYPE "public"."driver_status" OWNER TO "postgres";


CREATE TYPE "public"."fuel_type" AS ENUM (
    'petrol',
    'diesel'
);


ALTER TYPE "public"."fuel_type" OWNER TO "postgres";


CREATE TYPE "public"."general_expense_type_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."general_expense_type_status" OWNER TO "postgres";


CREATE TYPE "public"."item_category" AS ENUM (
    'fuel',
    'equipment',
    'materials',
    'cargo',
    'other'
);


ALTER TYPE "public"."item_category" OWNER TO "postgres";


CREATE TYPE "public"."item_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."item_status" OWNER TO "postgres";


CREATE TYPE "public"."location_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."location_status" OWNER TO "postgres";


CREATE TYPE "public"."user_gender" AS ENUM (
    'male',
    'female'
);


ALTER TYPE "public"."user_gender" OWNER TO "postgres";


CREATE TYPE "public"."user_role" AS ENUM (
    'admin',
    'manager',
    'operator',
    'viewer'
);


ALTER TYPE "public"."user_role" OWNER TO "postgres";


CREATE TYPE "public"."user_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."user_status" OWNER TO "postgres";


CREATE TYPE "public"."vehicle_expense_category" AS ENUM (
    'repair',
    'maintenance',
    'body_work',
    'tyres'
);


ALTER TYPE "public"."vehicle_expense_category" OWNER TO "postgres";


CREATE TYPE "public"."vehicle_status" AS ENUM (
    'active',
    'inactive'
);


ALTER TYPE "public"."vehicle_status" OWNER TO "postgres";


CREATE TYPE "public"."vehicle_type" AS ENUM (
    'lorry',
    'bowser',
    'tipper',
    'truck',
    'van',
    'bus'
);


ALTER TYPE "public"."vehicle_type" OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."create_module_line_table"("p_table" character varying, "p_parent_fk_column" character varying, "p_columns" "jsonb") RETURNS "void"
    LANGUAGE "plpgsql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $_$
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
$_$;


ALTER FUNCTION "public"."create_module_line_table"("p_table" character varying, "p_parent_fk_column" character varying, "p_columns" "jsonb") OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."create_module_table"("p_table" character varying, "p_columns" "jsonb") RETURNS "void"
    LANGUAGE "plpgsql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $_$
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
$_$;


ALTER FUNCTION "public"."create_module_table"("p_table" character varying, "p_columns" "jsonb") OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."drop_module_line_table"("p_table" character varying) RETURNS "void"
    LANGUAGE "plpgsql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $_$
BEGIN
  IF p_table !~ '^t_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  EXECUTE format('DROP TABLE IF EXISTS %I', p_table);
END;
$_$;


ALTER FUNCTION "public"."drop_module_line_table"("p_table" character varying) OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."drop_module_table"("p_table" character varying) RETURNS "void"
    LANGUAGE "plpgsql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $_$
BEGIN
  IF p_table !~ '^m_[a-z][a-z0-9_]{2,58}$' THEN
    RAISE EXCEPTION 'invalid table name format: %', p_table;
  END IF;

  EXECUTE format('DROP TABLE IF EXISTS %I', p_table);
END;
$_$;


ALTER FUNCTION "public"."drop_module_table"("p_table" character varying) OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."get_schema_overview"() RETURNS "jsonb"
    LANGUAGE "sql" SECURITY DEFINER
    SET "search_path" TO 'public'
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


ALTER FUNCTION "public"."get_schema_overview"() OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text" DEFAULT 'accrual'::"text") RETURNS TABLE("account_code" "text", "account_name" "text", "account_type" "text", "account_sub_type" "text", "ob_debit" numeric, "ob_credit" numeric, "pd_debit" numeric, "pd_credit" numeric, "cl_debit" numeric, "cl_credit" numeric)
    LANGUAGE "sql" STABLE SECURITY DEFINER
    AS $$
    WITH
    coa AS (
        SELECT account_code, account_name, account_type, account_sub_type
        FROM m_chart_of_accounts
        WHERE status = 'active'
    ),
    opening AS (
        SELECT
            account_code,
            COALESCE(SUM(debit_amount),  0) AS ob_dr,
            COALESCE(SUM(credit_amount), 0) AS ob_cr
        FROM m_general_ledger
        WHERE transaction_date < p_date_from
          AND (p_basis != 'accrual' OR status = 'posted')
        GROUP BY account_code
    ),
    period AS (
        SELECT
            account_code,
            COALESCE(SUM(debit_amount),  0) AS pd_dr,
            COALESCE(SUM(credit_amount), 0) AS pd_cr
        FROM m_general_ledger
        WHERE transaction_date BETWEEN p_date_from AND p_date_to
          AND (p_basis != 'accrual' OR status = 'posted')
        GROUP BY account_code
    ),
    combined AS (
        SELECT
            c.account_code,
            c.account_name,
            c.account_type,
            c.account_sub_type,
            COALESCE(o.ob_dr, 0) AS ob_dr,
            COALESCE(o.ob_cr, 0) AS ob_cr,
            COALESCE(p.pd_dr, 0) AS pd_dr,
            COALESCE(p.pd_cr, 0) AS pd_cr
        FROM coa c
        LEFT JOIN opening o ON o.account_code = c.account_code
        LEFT JOIN period  p ON p.account_code = c.account_code
        WHERE COALESCE(o.ob_dr, 0) + COALESCE(o.ob_cr, 0)
            + COALESCE(p.pd_dr, 0) + COALESCE(p.pd_cr, 0) > 0
    ),
    ob_net AS (
        SELECT *,
            ob_dr - ob_cr AS ob_n,
            (ob_dr - ob_cr) + pd_dr - pd_cr AS cl_n
        FROM combined
    )
    SELECT
        account_code,
        account_name,
        account_type,
        account_sub_type,
        CASE WHEN ob_n > 0 THEN  ob_n ELSE 0 END AS ob_debit,
        CASE WHEN ob_n < 0 THEN -ob_n ELSE 0 END AS ob_credit,
        pd_dr  AS pd_debit,
        pd_cr  AS pd_credit,
        CASE WHEN cl_n > 0 THEN  cl_n ELSE 0 END AS cl_debit,
        CASE WHEN cl_n < 0 THEN -cl_n ELSE 0 END AS cl_credit
    FROM ob_net
    ORDER BY account_code
$$;


ALTER FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."register_tms_module"("p_subsection_ref" character varying, "p_folder" character varying, "p_name" character varying, "p_sort_order" integer, "p_module_type" character varying DEFAULT 'entry'::character varying, "p_creates_journal_entry" boolean DEFAULT false) RETURNS TABLE("id" bigint, "ref" character varying)
    LANGUAGE "plpgsql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $_$
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
$_$;


ALTER FUNCTION "public"."register_tms_module"("p_subsection_ref" character varying, "p_folder" character varying, "p_name" character varying, "p_sort_order" integer, "p_module_type" character varying, "p_creates_journal_entry" boolean) OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."set_updated_at"() RETURNS "trigger"
    LANGUAGE "plpgsql"
    AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;


ALTER FUNCTION "public"."set_updated_at"() OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."sys_rmp_set_ref"() RETURNS "trigger"
    LANGUAGE "plpgsql"
    AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'RMP-' || lpad(nextval('sys_rmp_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$;


ALTER FUNCTION "public"."sys_rmp_set_ref"() OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."sys_roles_set_ref"() RETURNS "trigger"
    LANGUAGE "plpgsql"
    AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'ROL-' || lpad(nextval('sys_roles_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$;


ALTER FUNCTION "public"."sys_roles_set_ref"() OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."sys_user_roles_set_ref"() RETURNS "trigger"
    LANGUAGE "plpgsql"
    AS $$
BEGIN
  IF NEW.ref IS NULL THEN
    NEW.ref := 'UXR-' || lpad(nextval('sys_user_roles_ref_seq')::text, 6, '0');
  END IF;
  RETURN NEW;
END; $$;


ALTER FUNCTION "public"."sys_user_roles_set_ref"() OWNER TO "postgres";


CREATE OR REPLACE FUNCTION "public"."unregister_tms_module"("p_id" bigint) RETURNS "void"
    LANGUAGE "sql" SECURITY DEFINER
    SET "search_path" TO 'public'
    AS $$
  DELETE FROM sys_tms_modules WHERE id = p_id;
$$;


ALTER FUNCTION "public"."unregister_tms_module"("p_id" bigint) OWNER TO "postgres";

SET default_tablespace = '';

SET default_table_access_method = "heap";


CREATE TABLE IF NOT EXISTS "public"."m_advance_payments" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "recipient_type" "public"."advance_payment_recipient_type" NOT NULL,
    "date" "date" NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_advance_payments" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."advance_payments_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."advance_payments_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."advance_payments_id_seq" OWNED BY "public"."m_advance_payments"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_app_users_legacy" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "first_name" character varying(100) NOT NULL,
    "last_name" character varying(100) NOT NULL,
    "email" character varying(150) NOT NULL,
    "password" character varying(255) NOT NULL,
    "gender" "public"."user_gender" NOT NULL,
    "role" "public"."user_role" DEFAULT 'viewer'::"public"."user_role" NOT NULL,
    "status" "public"."user_status" DEFAULT 'active'::"public"."user_status" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_app_users_legacy" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."app_users_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."app_users_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."app_users_id_seq" OWNED BY "public"."sys_app_users_legacy"."id";



CREATE TABLE IF NOT EXISTS "public"."m_cash_flow_entries" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "date" "date" NOT NULL,
    "flow_type" character varying(10) NOT NULL,
    "category" character varying(100) NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "description" character varying(255),
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "cash_flow_entries_flow_type_check" CHECK ((("flow_type")::"text" = ANY ((ARRAY['inflow'::character varying, 'outflow'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_cash_flow_entries" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."cash_flow_entries_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."cash_flow_entries_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."cash_flow_entries_id_seq" OWNED BY "public"."m_cash_flow_entries"."id";



CREATE TABLE IF NOT EXISTS "public"."m_cleaner_salary_settlements" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "month" character varying(7) NOT NULL,
    "gross_earnings" numeric(12,2) DEFAULT 0 NOT NULL,
    "advances" numeric(12,2) DEFAULT 0 NOT NULL,
    "net_payable" numeric(12,2) DEFAULT 0 NOT NULL,
    "status" character varying(10) DEFAULT 'pending'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "cleaner_salary_settlements_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['pending'::character varying, 'paid'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_cleaner_salary_settlements" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."cleaner_salary_settlements_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."cleaner_salary_settlements_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."cleaner_salary_settlements_id_seq" OWNED BY "public"."m_cleaner_salary_settlements"."id";



CREATE TABLE IF NOT EXISTS "public"."m_cleaners" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "phone" character varying(20) NOT NULL,
    "status" "public"."cleaner_status" DEFAULT 'active'::"public"."cleaner_status" NOT NULL,
    "date_of_birth" "date" NOT NULL,
    "joining_date" "date" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    "employee_ref" character varying
);


ALTER TABLE "public"."m_cleaners" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."cleaners_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."cleaners_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."cleaners_id_seq" OWNED BY "public"."m_cleaners"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_company_info" (
    "id" integer NOT NULL,
    "name" character varying(200) NOT NULL,
    "address" "text",
    "phone" character varying(50),
    "email" character varying(100),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_company_info" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."company_info_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."company_info_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."company_info_id_seq" OWNED BY "public"."sys_company_info"."id";



CREATE TABLE IF NOT EXISTS "public"."m_customers" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "customer_name" character varying(255) NOT NULL,
    "phone" character varying(50),
    "email" character varying(255),
    "address" "text",
    "record_status" character varying(10) DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    "code" character varying(50),
    "customer_type_ref" character varying(20),
    "contact_person" character varying(255),
    "mobile" character varying(50),
    "city" character varying(100),
    "country" character varying(100),
    "tax_number" character varying(100),
    "credit_limit" numeric(14,2),
    "payment_terms_days" integer,
    "remarks" "text"
);


ALTER TABLE "public"."m_customers" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."customers_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."customers_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."customers_id_seq" OWNED BY "public"."m_customers"."id";



CREATE TABLE IF NOT EXISTS "public"."m_deductions" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "recipient_type" character varying(10) NOT NULL,
    "date" "date" NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "reason" character varying(255) NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "deductions_recipient_type_check" CHECK ((("recipient_type")::"text" = ANY ((ARRAY['driver'::character varying, 'cleaner'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_deductions" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."deductions_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."deductions_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."deductions_id_seq" OWNED BY "public"."m_deductions"."id";



CREATE TABLE IF NOT EXISTS "public"."m_driver_cleaner_pairings" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "start_date" "date" NOT NULL,
    "end_date" "date",
    "status" character varying(10) DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "driver_cleaner_pairings_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_driver_cleaner_pairings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."driver_cleaner_pairings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."driver_cleaner_pairings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."driver_cleaner_pairings_id_seq" OWNED BY "public"."m_driver_cleaner_pairings"."id";



CREATE TABLE IF NOT EXISTS "public"."m_driver_salary_settlements" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "month" character varying(7) NOT NULL,
    "gross_earnings" numeric(12,2) DEFAULT 0 NOT NULL,
    "advances" numeric(12,2) DEFAULT 0 NOT NULL,
    "deductions_total" numeric(12,2) DEFAULT 0 NOT NULL,
    "loan_recovery" numeric(12,2) DEFAULT 0 NOT NULL,
    "net_payable" numeric(12,2) DEFAULT 0 NOT NULL,
    "status" character varying(10) DEFAULT 'pending'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "driver_salary_settlements_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['pending'::character varying, 'paid'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_driver_salary_settlements" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."driver_salary_settlements_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."driver_salary_settlements_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."driver_salary_settlements_id_seq" OWNED BY "public"."m_driver_salary_settlements"."id";



CREATE TABLE IF NOT EXISTS "public"."m_drivers" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "phone" character varying(20) NOT NULL,
    "license_number" character varying(50) NOT NULL,
    "status" "public"."driver_status" DEFAULT 'active'::"public"."driver_status" NOT NULL,
    "date_of_birth" "date" NOT NULL,
    "joining_date" "date" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    "employee_ref" character varying
);


ALTER TABLE "public"."m_drivers" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."drivers_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."drivers_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."drivers_id_seq" OWNED BY "public"."m_drivers"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_export_records" (
    "id" bigint NOT NULL,
    "log_id" bigint,
    "user_id" integer,
    "user_ref" "text",
    "user_name" "text",
    "export_type" "text" NOT NULL,
    "report_name" "text",
    "report_system_name" "text",
    "module_section" "text",
    "date_from" "text",
    "date_to" "text",
    "filter_params" "jsonb" DEFAULT '{}'::"jsonb" NOT NULL,
    "row_count" integer,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_export_records" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."export_records_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."export_records_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."export_records_id_seq" OWNED BY "public"."sys_export_records"."id";



CREATE TABLE IF NOT EXISTS "public"."m_fuel_expenses" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "date" "date" NOT NULL,
    "liters" numeric(10,2) NOT NULL,
    "rate" numeric(10,2) NOT NULL,
    "total" numeric(12,2) NOT NULL,
    "voucher_number" character varying(20),
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "vehicle_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_fuel_expenses" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."fuel_expenses_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."fuel_expenses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."fuel_expenses_id_seq" OWNED BY "public"."m_fuel_expenses"."id";



CREATE TABLE IF NOT EXISTS "public"."m_general_expense_types" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "status" "public"."general_expense_type_status" DEFAULT 'active'::"public"."general_expense_type_status" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_general_expense_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."general_expense_types_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."general_expense_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."general_expense_types_id_seq" OWNED BY "public"."m_general_expense_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_general_expenses" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "remark" character varying(255) NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "date" "date" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "expense_type_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_general_expenses" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."general_expenses_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."general_expenses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."general_expenses_id_seq" OWNED BY "public"."m_general_expenses"."id";



CREATE TABLE IF NOT EXISTS "public"."m_items" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "category" "public"."item_category" NOT NULL,
    "unit" character varying(50) NOT NULL,
    "description" "text",
    "status" "public"."item_status" DEFAULT 'active'::"public"."item_status" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_items" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."items_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."items_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."items_id_seq" OWNED BY "public"."m_items"."id";



CREATE TABLE IF NOT EXISTS "public"."m_loans" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "recipient_type" character varying(10) NOT NULL,
    "date" "date" NOT NULL,
    "principal_amount" numeric(12,2) NOT NULL,
    "recovered_amount" numeric(12,2) DEFAULT 0 NOT NULL,
    "status" character varying(10) DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "loans_recipient_type_check" CHECK ((("recipient_type")::"text" = ANY ((ARRAY['driver'::character varying, 'cleaner'::character varying])::"text"[]))),
    CONSTRAINT "loans_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'settled'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_loans" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."loans_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."loans_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."loans_id_seq" OWNED BY "public"."m_loans"."id";



CREATE TABLE IF NOT EXISTS "public"."m_locations" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "district" character varying(100) NOT NULL,
    "province" character varying(100) NOT NULL,
    "status" "public"."location_status" DEFAULT 'active'::"public"."location_status" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_locations" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."locations_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."locations_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."locations_id_seq" OWNED BY "public"."m_locations"."id";



CREATE TABLE IF NOT EXISTS "public"."m_accounts_payable" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "supplier_name" character varying NOT NULL,
    "supplier_ref" character varying,
    "invoice_ref" character varying,
    "invoice_date" "date" NOT NULL,
    "due_date" "date" NOT NULL,
    "amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "amount_paid" numeric(15,2) DEFAULT 0 NOT NULL,
    "balance" numeric(15,2) GENERATED ALWAYS AS (("amount" - "amount_paid")) STORED,
    "description" "text",
    "status" character varying DEFAULT 'open'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_accounts_payable_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['open'::character varying, 'partial'::character varying, 'paid'::character varying, 'overdue'::character varying, 'written_off'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_accounts_payable" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_accounts_payable_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_accounts_payable_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_accounts_payable_id_seq" OWNED BY "public"."m_accounts_payable"."id";



CREATE TABLE IF NOT EXISTS "public"."m_accounts_receivable" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "customer_ref" character varying,
    "customer_name" character varying NOT NULL,
    "invoice_ref" character varying,
    "invoice_date" "date" NOT NULL,
    "due_date" "date" NOT NULL,
    "amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "amount_paid" numeric(15,2) DEFAULT 0 NOT NULL,
    "balance" numeric(15,2) GENERATED ALWAYS AS (("amount" - "amount_paid")) STORED,
    "description" "text",
    "status" character varying DEFAULT 'open'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_accounts_receivable_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['open'::character varying, 'partial'::character varying, 'paid'::character varying, 'overdue'::character varying, 'written_off'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_accounts_receivable" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_accounts_receivable_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_accounts_receivable_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_accounts_receivable_id_seq" OWNED BY "public"."m_accounts_receivable"."id";



CREATE TABLE IF NOT EXISTS "public"."m_alert_rules" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "category" character varying NOT NULL,
    "alert_name" character varying NOT NULL,
    "condition_field" character varying NOT NULL,
    "operator" character varying NOT NULL,
    "threshold_value" numeric(15,2) NOT NULL,
    "channel" character varying NOT NULL,
    "severity" character varying DEFAULT 'medium'::character varying NOT NULL,
    "is_enabled" boolean DEFAULT true NOT NULL,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_alert_rules_category_check" CHECK ((("category")::"text" = ANY (ARRAY['vehicle'::"text", 'financial'::"text"]))),
    CONSTRAINT "m_alert_rules_channel_check" CHECK ((("channel")::"text" = ANY (ARRAY['email'::"text", 'sms'::"text", 'whatsapp'::"text", 'in_app'::"text"]))),
    CONSTRAINT "m_alert_rules_operator_check" CHECK ((("operator")::"text" = ANY (ARRAY['>'::"text", '>='::"text", '<'::"text", '<='::"text", '='::"text"]))),
    CONSTRAINT "m_alert_rules_severity_check" CHECK ((("severity")::"text" = ANY (ARRAY['low'::"text", 'medium'::"text", 'high'::"text"]))),
    CONSTRAINT "m_alert_rules_status_check" CHECK ((("status")::"text" = ANY (ARRAY['active'::"text", 'inactive'::"text"])))
);


ALTER TABLE "public"."m_alert_rules" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_alert_rules_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_alert_rules_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_alert_rules_id_seq" OWNED BY "public"."m_alert_rules"."id";



CREATE TABLE IF NOT EXISTS "public"."m_approval_requests" (
    "id" integer NOT NULL,
    "workflow_ref" character varying NOT NULL,
    "record_ref" character varying NOT NULL,
    "module" character varying NOT NULL,
    "requested_by" "text",
    "requested_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "approved_by" "text",
    "approved_at" timestamp with time zone,
    "rejection_reason" "text",
    "notes" "text",
    "status" character varying DEFAULT 'pending'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    CONSTRAINT "m_approval_requests_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'withdrawn'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_approval_requests" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_approval_requests_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_approval_requests_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_approval_requests_id_seq" OWNED BY "public"."m_approval_requests"."id";



CREATE TABLE IF NOT EXISTS "public"."m_approval_workflow" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "workflow_name" character varying NOT NULL,
    "module" character varying NOT NULL,
    "approver_name" character varying NOT NULL,
    "approver_ref" character varying,
    "min_amount" numeric(15,2),
    "max_amount" numeric(15,2),
    "approval_order" integer DEFAULT 1 NOT NULL,
    "is_mandatory" boolean DEFAULT true NOT NULL,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_approval_workflow_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_approval_workflow" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_approval_workflow_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_approval_workflow_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_approval_workflow_id_seq" OWNED BY "public"."m_approval_workflow"."id";



CREATE TABLE IF NOT EXISTS "public"."m_bank_reconciliation" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "account_name" character varying NOT NULL,
    "account_number" character varying,
    "reconciliation_date" "date" NOT NULL,
    "period" character varying(7) NOT NULL,
    "bank_balance" numeric(15,2) DEFAULT 0 NOT NULL,
    "book_balance" numeric(15,2) DEFAULT 0 NOT NULL,
    "outstanding_deposits" numeric(15,2) DEFAULT 0 NOT NULL,
    "outstanding_cheques" numeric(15,2) DEFAULT 0 NOT NULL,
    "adjusted_bank_balance" numeric(15,2) GENERATED ALWAYS AS ((("bank_balance" + "outstanding_deposits") - "outstanding_cheques")) STORED,
    "difference" numeric(15,2) GENERATED ALWAYS AS (((("bank_balance" + "outstanding_deposits") - "outstanding_cheques") - "book_balance")) STORED,
    "notes" "text",
    "status" character varying DEFAULT 'draft'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_bank_reconciliation_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['draft'::character varying, 'reconciled'::character varying, 'locked'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_bank_reconciliation" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_bank_reconciliation_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_bank_reconciliation_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_bank_reconciliation_id_seq" OWNED BY "public"."m_bank_reconciliation"."id";



CREATE TABLE IF NOT EXISTS "public"."m_branches" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "branch_name" character varying(255),
    "branch_code" character varying(255),
    "address" "text",
    "city" character varying(255),
    "phone" character varying(255),
    "email" character varying(255),
    "manager_name" character varying(255),
    "is_head_office" boolean DEFAULT false NOT NULL,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_branches" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_branches_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_branches_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_branches_id_seq" OWNED BY "public"."m_branches"."id";



CREATE TABLE IF NOT EXISTS "public"."m_budgets" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "budget_name" character varying NOT NULL,
    "account_code" character varying NOT NULL,
    "period" character varying(7) NOT NULL,
    "budget_type" character varying DEFAULT 'Monthly'::character varying NOT NULL,
    "category" character varying NOT NULL,
    "budget_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "actual_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "variance" numeric(15,2) GENERATED ALWAYS AS (("budget_amount" - "actual_amount")) STORED,
    "notes" "text",
    "status" character varying DEFAULT 'draft'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_budgets_budget_type_check" CHECK ((("budget_type")::"text" = ANY ((ARRAY['Monthly'::character varying, 'Quarterly'::character varying, 'Annual'::character varying])::"text"[]))),
    CONSTRAINT "m_budgets_category_check" CHECK ((("category")::"text" = ANY ((ARRAY['Revenue'::character varying, 'Expense'::character varying])::"text"[]))),
    CONSTRAINT "m_budgets_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['draft'::character varying, 'approved'::character varying, 'closed'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_budgets" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_budgets_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_budgets_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_budgets_id_seq" OWNED BY "public"."m_budgets"."id";



CREATE TABLE IF NOT EXISTS "public"."m_cash_bank" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "account_name" character varying NOT NULL,
    "account_number" character varying,
    "transaction_type" character varying NOT NULL,
    "transaction_date" "date" NOT NULL,
    "amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "description" "text",
    "reference_doc" character varying,
    "status" character varying DEFAULT 'pending'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_cash_bank_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['pending'::character varying, 'cleared'::character varying, 'reconciled'::character varying])::"text"[]))),
    CONSTRAINT "m_cash_bank_transaction_type_check" CHECK ((("transaction_type")::"text" = ANY ((ARRAY['Deposit'::character varying, 'Withdrawal'::character varying, 'Transfer'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_cash_bank" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_cash_bank_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_cash_bank_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_cash_bank_id_seq" OWNED BY "public"."m_cash_bank"."id";



CREATE TABLE IF NOT EXISTS "public"."m_chart_of_accounts" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "account_code" character varying NOT NULL,
    "account_name" character varying NOT NULL,
    "account_type" character varying NOT NULL,
    "account_sub_type" character varying,
    "parent_code" character varying,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_chart_of_accounts_account_type_check" CHECK ((("account_type")::"text" = ANY ((ARRAY['Asset'::character varying, 'Liability'::character varying, 'Equity'::character varying, 'Revenue'::character varying, 'Expense'::character varying])::"text"[]))),
    CONSTRAINT "m_chart_of_accounts_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_chart_of_accounts" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_chart_of_accounts_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_chart_of_accounts_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_chart_of_accounts_id_seq" OWNED BY "public"."m_chart_of_accounts"."id";



CREATE TABLE IF NOT EXISTS "public"."m_cost_centers" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "cost_center_code" character varying NOT NULL,
    "cost_center_name" character varying NOT NULL,
    "department" character varying,
    "manager" character varying,
    "budget_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "actual_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "variance" numeric(15,2) GENERATED ALWAYS AS (("budget_amount" - "actual_amount")) STORED,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_cost_centers_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_cost_centers" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_cost_centers_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_cost_centers_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_cost_centers_id_seq" OWNED BY "public"."m_cost_centers"."id";



CREATE TABLE IF NOT EXISTS "public"."m_currencies" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "currency_code" character varying(10) NOT NULL,
    "currency_name" character varying NOT NULL,
    "symbol" character varying(10),
    "exchange_rate" numeric(15,6) DEFAULT 1 NOT NULL,
    "is_base_currency" boolean DEFAULT false NOT NULL,
    "effective_date" "date" NOT NULL,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_currencies_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_currencies" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_currencies_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_currencies_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_currencies_id_seq" OWNED BY "public"."m_currencies"."id";



CREATE TABLE IF NOT EXISTS "public"."m_customer_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "record_status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_customer_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_customer_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_customer_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_customer_types_id_seq" OWNED BY "public"."m_customer_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_departments" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_departments" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_departments_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_departments_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_departments_id_seq" OWNED BY "public"."m_departments"."id";



CREATE TABLE IF NOT EXISTS "public"."m_designations" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_designations" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_designations_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_designations_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_designations_id_seq" OWNED BY "public"."m_designations"."id";



CREATE TABLE IF NOT EXISTS "public"."m_employee_assignments" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "effective_from" "date",
    "effective_to" "date",
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "employment_record_ref" character varying,
    "department_ref" character varying,
    "designation_ref" character varying
);


ALTER TABLE "public"."m_employee_assignments" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_employee_assignments_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_employee_assignments_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_employee_assignments_id_seq" OWNED BY "public"."m_employee_assignments"."id";



CREATE TABLE IF NOT EXISTS "public"."m_employee_salary_structures" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "amount" numeric(14,2),
    "effective_from" "date",
    "effective_to" "date",
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "employee_ref" character varying,
    "salary_component_ref" character varying
);


ALTER TABLE "public"."m_employee_salary_structures" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_employee_salary_structures_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_employee_salary_structures_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_employee_salary_structures_id_seq" OWNED BY "public"."m_employee_salary_structures"."id";



CREATE TABLE IF NOT EXISTS "public"."m_employees" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "full_name" character varying(255),
    "date_of_birth" "date",
    "phone" character varying(255),
    "mobile" character varying(255),
    "email" character varying(255),
    "address" "text",
    "bank_name" character varying(255),
    "bank_branch" character varying(255),
    "bank_account_number" character varying(255),
    "tax_number" character varying(255),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_employees" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_employees_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_employees_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_employees_id_seq" OWNED BY "public"."m_employees"."id";



CREATE TABLE IF NOT EXISTS "public"."m_employment_records" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "joining_date" "date",
    "confirmation_date" "date",
    "termination_date" "date",
    "employment_status" character varying(100),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "employee_ref" character varying,
    "employment_type_ref" character varying,
    "payroll_profile_ref" character varying
);


ALTER TABLE "public"."m_employment_records" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_employment_records_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_employment_records_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_employment_records_id_seq" OWNED BY "public"."m_employment_records"."id";



CREATE TABLE IF NOT EXISTS "public"."m_employment_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_employment_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_employment_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_employment_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_employment_types_id_seq" OWNED BY "public"."m_employment_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_finance_audit_trail" (
    "id" integer NOT NULL,
    "event_time" timestamp with time zone DEFAULT "now"() NOT NULL,
    "user_ref" "text",
    "user_name" "text",
    "action_type" character varying NOT NULL,
    "module" character varying NOT NULL,
    "record_ref" character varying,
    "field_changed" character varying,
    "old_value" "text",
    "new_value" "text",
    "description" "text",
    "ip_address" "text",
    "created_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."m_finance_audit_trail" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_finance_audit_trail_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_finance_audit_trail_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_finance_audit_trail_id_seq" OWNED BY "public"."m_finance_audit_trail"."id";



CREATE TABLE IF NOT EXISTS "public"."m_financial_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying NOT NULL,
    "setting_label" character varying NOT NULL,
    "setting_value" "text",
    "setting_type" character varying DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "updated_by" "text",
    CONSTRAINT "m_financial_settings_setting_type_check" CHECK ((("setting_type")::"text" = ANY ((ARRAY['text'::character varying, 'number'::character varying, 'boolean'::character varying, 'select'::character varying, 'date'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_financial_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_financial_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_financial_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_financial_settings_id_seq" OWNED BY "public"."m_financial_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."m_fixed_assets" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "asset_name" character varying NOT NULL,
    "asset_category" character varying NOT NULL,
    "purchase_date" "date" NOT NULL,
    "purchase_cost" numeric(15,2) DEFAULT 0 NOT NULL,
    "useful_life_years" integer DEFAULT 1 NOT NULL,
    "depreciation_method" character varying DEFAULT 'Straight Line'::character varying NOT NULL,
    "salvage_value" numeric(15,2) DEFAULT 0 NOT NULL,
    "accumulated_depreciation" numeric(15,2) DEFAULT 0 NOT NULL,
    "net_book_value" numeric(15,2) GENERATED ALWAYS AS (("purchase_cost" - "accumulated_depreciation")) STORED,
    "location" character varying,
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_fixed_assets_depreciation_method_check" CHECK ((("depreciation_method")::"text" = ANY ((ARRAY['Straight Line'::character varying, 'Declining Balance'::character varying])::"text"[]))),
    CONSTRAINT "m_fixed_assets_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'disposed'::character varying, 'fully_depreciated'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_fixed_assets" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_fixed_assets_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_fixed_assets_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_fixed_assets_id_seq" OWNED BY "public"."m_fixed_assets"."id";



CREATE TABLE IF NOT EXISTS "public"."m_fleet_statuses" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "is_operational" boolean,
    "sort_order" integer,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_fleet_statuses" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_fleet_statuses_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_fleet_statuses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_fleet_statuses_id_seq" OWNED BY "public"."m_fleet_statuses"."id";



CREATE TABLE IF NOT EXISTS "public"."m_fuel_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_fuel_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_fuel_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_fuel_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_fuel_types_id_seq" OWNED BY "public"."m_fuel_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_general_ledger" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "account_code" character varying NOT NULL,
    "transaction_date" "date" NOT NULL,
    "period" character varying(7) NOT NULL,
    "description" "text" NOT NULL,
    "debit_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "credit_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "journal_ref" character varying,
    "status" character varying DEFAULT 'draft'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    "account_name" "text",
    "line_no" integer,
    CONSTRAINT "m_general_ledger_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['draft'::character varying, 'posted'::character varying, 'reversed'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_general_ledger" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_general_ledger_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_general_ledger_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_general_ledger_id_seq" OWNED BY "public"."m_general_ledger"."id";



CREATE TABLE IF NOT EXISTS "public"."m_gps_devices" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "serial_number" character varying(255),
    "imei" character varying(255),
    "sim_number" character varying(255),
    "manufacturer" character varying(255),
    "model" character varying(255),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_gps_devices" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_gps_devices_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_gps_devices_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_gps_devices_id_seq" OWNED BY "public"."m_gps_devices"."id";



CREATE TABLE IF NOT EXISTS "public"."m_journal_entries" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "journal_date" "date" NOT NULL,
    "period" character varying(7) NOT NULL,
    "description" "text" NOT NULL,
    "reference_doc" character varying,
    "status" character varying DEFAULT 'draft'::character varying NOT NULL,
    "total_debit" numeric(15,2) DEFAULT 0 NOT NULL,
    "total_credit" numeric(15,2) DEFAULT 0 NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    "drafted_by" "text",
    "drafted_at" timestamp with time zone,
    "posted_by" "text",
    "posted_at" timestamp with time zone,
    "source_type" "text",
    "source_ref" "text",
    CONSTRAINT "m_journal_entries_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['draft'::character varying, 'posted'::character varying, 'reversed'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_journal_entries" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_journal_entries_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_journal_entries_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_journal_entries_id_seq" OWNED BY "public"."m_journal_entries"."id";



CREATE TABLE IF NOT EXISTS "public"."m_journal_entry_lines" (
    "id" integer NOT NULL,
    "journal_ref" character varying NOT NULL,
    "line_no" integer NOT NULL,
    "account_code" character varying NOT NULL,
    "description" "text",
    "debit_amount" numeric(15,2) DEFAULT 0 NOT NULL,
    "credit_amount" numeric(15,2) DEFAULT 0 NOT NULL
);


ALTER TABLE "public"."m_journal_entry_lines" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_journal_entry_lines_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_journal_entry_lines_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_journal_entry_lines_id_seq" OWNED BY "public"."m_journal_entry_lines"."id";



CREATE TABLE IF NOT EXISTS "public"."m_maintenance_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "service_interval_km" numeric(14,2),
    "service_interval_days" integer,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_maintenance_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_maintenance_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_maintenance_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_maintenance_types_id_seq" OWNED BY "public"."m_maintenance_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_payments" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "recipient_type" character varying(10) NOT NULL,
    "date" "date" NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "payment_type" character varying(50) DEFAULT 'salary'::character varying NOT NULL,
    "remark" character varying(255),
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "driver_ref" "text",
    "cleaner_ref" "text",
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "payments_recipient_type_check" CHECK ((("recipient_type")::"text" = ANY ((ARRAY['driver'::character varying, 'cleaner'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_payments" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."m_payroll_inputs" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "quantity" numeric(14,2),
    "rate" numeric(14,2),
    "amount" numeric(14,2),
    "source_type" character varying(255),
    "source_ref" character varying(255),
    "remarks" "text",
    "approval_status" character varying(100),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "payroll_period_ref" character varying,
    "salary_component_ref" character varying,
    "employment_record_ref" character varying
);


ALTER TABLE "public"."m_payroll_inputs" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_payroll_inputs_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_payroll_inputs_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_payroll_inputs_id_seq" OWNED BY "public"."m_payroll_inputs"."id";



CREATE TABLE IF NOT EXISTS "public"."m_payroll_periods" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "start_date" "date",
    "end_date" "date",
    "payment_date" "date",
    "period_status" character varying(100),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "payroll_profile_ref" character varying
);


ALTER TABLE "public"."m_payroll_periods" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_payroll_periods_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_payroll_periods_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_payroll_periods_id_seq" OWNED BY "public"."m_payroll_periods"."id";



CREATE TABLE IF NOT EXISTS "public"."m_payroll_profiles" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "pay_frequency" character varying(100),
    "pay_day" integer,
    "proration_method" character varying(100),
    "fixed_divisor" numeric(14,2),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "currency_ref" character varying
);


ALTER TABLE "public"."m_payroll_profiles" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_payroll_profiles_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_payroll_profiles_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_payroll_profiles_id_seq" OWNED BY "public"."m_payroll_profiles"."id";



CREATE TABLE IF NOT EXISTS "public"."m_payroll_runs" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "run_date" "date",
    "description" "text",
    "payroll_status" character varying(100),
    "total_gross_amount" numeric(14,2),
    "total_deduction_amount" numeric(14,2),
    "total_net_amount" numeric(14,2),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "payroll_period_ref" character varying
);


ALTER TABLE "public"."m_payroll_runs" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_payroll_runs_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_payroll_runs_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_payroll_runs_id_seq" OWNED BY "public"."m_payroll_runs"."id";



CREATE TABLE IF NOT EXISTS "public"."m_period_closing" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "period" character varying(7) NOT NULL,
    "period_name" character varying NOT NULL,
    "closing_date" "date" NOT NULL,
    "closed_by" "text",
    "total_revenue" numeric(15,2) DEFAULT 0 NOT NULL,
    "total_expenses" numeric(15,2) DEFAULT 0 NOT NULL,
    "net_profit" numeric(15,2) GENERATED ALWAYS AS (("total_revenue" - "total_expenses")) STORED,
    "notes" "text",
    "status" character varying DEFAULT 'open'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_period_closing_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['open'::character varying, 'closed'::character varying, 'locked'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_period_closing" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_period_closing_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_period_closing_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_period_closing_id_seq" OWNED BY "public"."m_period_closing"."id";



CREATE TABLE IF NOT EXISTS "public"."m_posting_rules" (
    "id" integer NOT NULL,
    "module_system_name" "text" NOT NULL,
    "variant" "text",
    "line_no" integer NOT NULL,
    "entry_type" "text" NOT NULL,
    "account_code" "text",
    "account_name" "text",
    "description" "text",
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_posting_rules_entry_type_check" CHECK (("entry_type" = ANY (ARRAY['debit'::"text", 'credit'::"text"])))
);


ALTER TABLE "public"."m_posting_rules" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_posting_rules_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_posting_rules_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_posting_rules_id_seq" OWNED BY "public"."m_posting_rules"."id";



CREATE TABLE IF NOT EXISTS "public"."t_quotation_resources" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "quantity" integer,
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "quotation_ref" character varying(20),
    "vehicle_type_ref" character varying(20)
);


ALTER TABLE "public"."t_quotation_resources" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_quotation_resources_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_quotation_resources_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_quotation_resources_id_seq" OWNED BY "public"."t_quotation_resources"."id";



CREATE TABLE IF NOT EXISTS "public"."m_quotation_statuses" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "sort_order" integer,
    "is_default_status" boolean,
    "is_final_status" boolean,
    "can_create_work_order" boolean,
    "record_status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "display_color" character varying(20),
    "icon" character varying(50)
);


ALTER TABLE "public"."m_quotation_statuses" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_quotation_statuses_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_quotation_statuses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_quotation_statuses_id_seq" OWNED BY "public"."m_quotation_statuses"."id";



CREATE TABLE IF NOT EXISTS "public"."m_quotations" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "quotation_date" timestamp with time zone,
    "valid_until" timestamp with time zone,
    "notes" "text",
    "terms_conditions" "text",
    "subtotal" numeric(14,2),
    "discount" numeric(14,2),
    "tax" numeric(14,2),
    "total_amount" numeric(14,2),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "customer_ref" character varying(20),
    "quotation_status_ref" character varying(20),
    "revision_no" integer DEFAULT 0 NOT NULL,
    "subject" character varying(255),
    "customer_reference" character varying(100),
    "contact_person" character varying(255),
    "currency_ref" character varying(20),
    "salesperson_ref" character varying(20),
    "price_list" character varying(100),
    "payment_terms" character varying(255),
    "delivery_period" character varying(255),
    "internal_notes" "text"
);


ALTER TABLE "public"."m_quotations" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_quotations_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_quotations_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_quotations_id_seq" OWNED BY "public"."m_quotations"."id";



CREATE TABLE IF NOT EXISTS "public"."m_salary_assignment_details" (
    "id" bigint NOT NULL,
    "salary_assignment_ref" character varying NOT NULL,
    "line_no" integer DEFAULT 1 NOT NULL,
    "salary_component_ref" character varying NOT NULL,
    "value" numeric DEFAULT 0 NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_salary_assignment_details" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_salary_assignment_details_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_salary_assignment_details_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_salary_assignment_details_id_seq" OWNED BY "public"."m_salary_assignment_details"."id";



CREATE TABLE IF NOT EXISTS "public"."m_salary_assignments" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "effective_from" "date",
    "effective_to" "date",
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "employment_record_ref" character varying,
    "currency_ref" character varying
);


ALTER TABLE "public"."m_salary_assignments" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_salary_assignments_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_salary_assignments_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_salary_assignments_id_seq" OWNED BY "public"."m_salary_assignments"."id";



CREATE TABLE IF NOT EXISTS "public"."m_salary_components" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "component_type" character varying(100),
    "calculation_type" character varying(100),
    "is_taxable" boolean,
    "is_recurring" boolean DEFAULT true,
    "affects_gross_salary" boolean DEFAULT true,
    "affects_net_salary" boolean DEFAULT true,
    "sort_order" integer,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_salary_components" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_salary_components_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_salary_components_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_salary_components_id_seq" OWNED BY "public"."m_salary_components"."id";



CREATE TABLE IF NOT EXISTS "public"."m_tax_management" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "tax_name" character varying NOT NULL,
    "tax_code" character varying NOT NULL,
    "tax_type" character varying NOT NULL,
    "rate" numeric(7,4) DEFAULT 0 NOT NULL,
    "applicable_on" character varying DEFAULT 'Both'::character varying NOT NULL,
    "effective_date" "date" NOT NULL,
    "expiry_date" "date",
    "description" "text",
    "status" character varying DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text",
    CONSTRAINT "m_tax_management_applicable_on_check" CHECK ((("applicable_on")::"text" = ANY ((ARRAY['Revenue'::character varying, 'Expense'::character varying, 'Both'::character varying])::"text"[]))),
    CONSTRAINT "m_tax_management_status_check" CHECK ((("status")::"text" = ANY ((ARRAY['active'::character varying, 'inactive'::character varying])::"text"[]))),
    CONSTRAINT "m_tax_management_tax_type_check" CHECK ((("tax_type")::"text" = ANY ((ARRAY['VAT'::character varying, 'Income Tax'::character varying, 'Withholding Tax'::character varying, 'Customs Duty'::character varying, 'Excise Duty'::character varying, 'Other'::character varying])::"text"[])))
);


ALTER TABLE "public"."m_tax_management" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_tax_management_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_tax_management_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_tax_management_id_seq" OWNED BY "public"."m_tax_management"."id";



CREATE TABLE IF NOT EXISTS "public"."m_trip_expenses" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "date" "date" NOT NULL,
    "trip_id" integer,
    "category" character varying(50) DEFAULT 'other'::character varying NOT NULL,
    "amount" numeric(12,2) DEFAULT 0 NOT NULL,
    "remark" character varying(255),
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "vehicle_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_trip_expenses" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."m_trips" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "date" "date" NOT NULL,
    "opening_km" numeric(10,2) NOT NULL,
    "closing_km" numeric(10,2) NOT NULL,
    "mileage" numeric(10,2) NOT NULL,
    "item_name" character varying(100),
    "run_no" character varying(50),
    "from_loc" character varying(100) NOT NULL,
    "to_loc" character varying(100) NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "driver_salary" numeric(12,2) NOT NULL,
    "cleaner_salary" numeric(12,2) NOT NULL,
    "department" character varying(100),
    "remark" character varying(255),
    "user_id" integer,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "vehicle_ref" "text",
    "driver_ref" "text",
    "cleaner_ref" "text",
    "item_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_trips" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."m_user_preferences" (
    "id" integer NOT NULL,
    "user_ref" "text" NOT NULL,
    "check_gl_default" boolean DEFAULT false NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."m_user_preferences" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_user_preferences_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_user_preferences_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_user_preferences_id_seq" OWNED BY "public"."m_user_preferences"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicle_brands" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "country" character varying(255),
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_vehicle_brands" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_brands_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_brands_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_brands_id_seq" OWNED BY "public"."m_vehicle_brands"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicle_document_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "is_mandatory" boolean,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_vehicle_document_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_document_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_document_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_document_types_id_seq" OWNED BY "public"."m_vehicle_document_types"."id";



CREATE TABLE IF NOT EXISTS "public"."t_vehicle_documents" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "document_number" character varying(255),
    "issue_date" "date",
    "expiry_date" "date",
    "issued_by" character varying(255),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "vehicle_ref" character varying(20),
    "document_type_ref" character varying(20),
    "attachment" character varying(255)
);


ALTER TABLE "public"."t_vehicle_documents" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_documents_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_documents_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_documents_id_seq" OWNED BY "public"."t_vehicle_documents"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicle_expenses" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "category" "public"."vehicle_expense_category" NOT NULL,
    "remark" character varying(255) NOT NULL,
    "amount" numeric(12,2) NOT NULL,
    "date" "date" NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "vehicle_ref" "text",
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_vehicle_expenses" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."t_vehicle_gps" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "installed_date" "date",
    "removed_date" "date",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "vehicle_ref" character varying(20),
    "gps_device_ref" character varying(20)
);


ALTER TABLE "public"."t_vehicle_gps" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_gps_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_gps_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_gps_id_seq" OWNED BY "public"."t_vehicle_gps"."id";



CREATE TABLE IF NOT EXISTS "public"."t_vehicle_maintenance" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "service_provider" character varying(255),
    "scheduled_date" "date",
    "start_date" "date",
    "completed_date" "date",
    "odometer_km" numeric(14,2),
    "labour_cost" numeric(14,2),
    "parts_cost" numeric(14,2),
    "total_cost" numeric(14,2),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "vehicle_ref" character varying(20),
    "maintenance_type_ref" character varying(20)
);


ALTER TABLE "public"."t_vehicle_maintenance" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_maintenance_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_maintenance_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_maintenance_id_seq" OWNED BY "public"."t_vehicle_maintenance"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicle_models" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "model_name" character varying(255),
    "manufacture_start_year" integer,
    "manufacture_end_year" integer,
    "axle_count" integer,
    "seating_capacity" integer,
    "payload_capacity_kg" numeric(14,2),
    "volume_capacity_m3" numeric(14,2),
    "fuel_tank_capacity_l" numeric(14,2),
    "average_fuel_consumption" numeric(14,2),
    "description" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "brand_ref" character varying(20),
    "vehicle_type_ref" character varying(20),
    "fuel_type_ref" character varying(20)
);


ALTER TABLE "public"."m_vehicle_models" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_models_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_models_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_models_id_seq" OWNED BY "public"."m_vehicle_models"."id";



CREATE TABLE IF NOT EXISTS "public"."t_vehicle_odometer" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "reading_date" "date",
    "odometer_km" numeric(14,2),
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "vehicle_ref" character varying(20)
);


ALTER TABLE "public"."t_vehicle_odometer" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_odometer_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_odometer_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_odometer_id_seq" OWNED BY "public"."t_vehicle_odometer"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicle_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_vehicle_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_vehicle_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_vehicle_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_vehicle_types_id_seq" OWNED BY "public"."m_vehicle_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_vehicles" (
    "id" integer NOT NULL,
    "ref" character varying(20) NOT NULL,
    "plate_number" character varying(20) NOT NULL,
    "make" character varying(50) NOT NULL,
    "model" character varying(50) NOT NULL,
    "type" "public"."vehicle_type" NOT NULL,
    "fuel_type" "public"."fuel_type" NOT NULL,
    "status" "public"."vehicle_status" DEFAULT 'active'::"public"."vehicle_status" NOT NULL,
    "last_location" character varying(100) DEFAULT 'Depot'::character varying NOT NULL,
    "mileage" integer DEFAULT 0 NOT NULL,
    "year" smallint NOT NULL,
    "capacity" integer DEFAULT 0 NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "created_by" "text",
    "updated_by" "text"
);


ALTER TABLE "public"."m_vehicles" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."m_work_order_statuses" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "sort_order" integer,
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_work_order_statuses" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_work_order_statuses_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_work_order_statuses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_work_order_statuses_id_seq" OWNED BY "public"."m_work_order_statuses"."id";



CREATE TABLE IF NOT EXISTS "public"."m_work_order_types" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "code" character varying(255),
    "name" character varying(255),
    "description" "text",
    "requires_customer" boolean,
    "uses_vehicle" boolean,
    "uses_driver" boolean,
    "is_billable" boolean,
    "record_status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."m_work_order_types" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_work_order_types_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_work_order_types_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_work_order_types_id_seq" OWNED BY "public"."m_work_order_types"."id";



CREATE TABLE IF NOT EXISTS "public"."m_work_orders" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "requested_date" timestamp with time zone,
    "planned_start" timestamp with time zone,
    "planned_end" timestamp with time zone,
    "actual_start" timestamp with time zone,
    "actual_end" timestamp with time zone,
    "description" "text",
    "remarks" "text",
    "status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "customer_ref" character varying(20),
    "work_order_type_ref" character varying(20),
    "quotation_ref" character varying(20),
    "work_order_status_ref" character varying(20)
);


ALTER TABLE "public"."m_work_orders" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."m_work_orders_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."m_work_orders_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."m_work_orders_id_seq" OWNED BY "public"."m_work_orders"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."payments_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."payments_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."payments_id_seq" OWNED BY "public"."m_payments"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_backup_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_backup_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_backup_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_backup_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_backup_settings_id_seq" OWNED BY "public"."sys_backup_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_company_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_company_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_company_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_company_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_company_settings_id_seq" OWNED BY "public"."sys_company_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_developer_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT true NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_developer_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_developer_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_developer_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_developer_settings_id_seq" OWNED BY "public"."sys_developer_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_email_sms_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_email_sms_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_email_sms_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_email_sms_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_email_sms_settings_id_seq" OWNED BY "public"."sys_email_sms_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_integrations" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_integrations" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_integrations_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_integrations_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_integrations_id_seq" OWNED BY "public"."sys_integrations"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_master_data_settings" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_master_data_settings" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_master_data_settings_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_master_data_settings_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_master_data_settings_id_seq" OWNED BY "public"."sys_master_data_settings"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_notifications" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_notifications" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_notifications_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_notifications_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_notifications_id_seq" OWNED BY "public"."sys_notifications"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."sys_rmp_ref_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_rmp_ref_seq" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_role_module_permissions" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "role_ref" character varying(20) NOT NULL,
    "module_ref" character varying(20) NOT NULL,
    "can_view" boolean DEFAULT false NOT NULL,
    "can_create" boolean DEFAULT false NOT NULL,
    "can_edit" boolean DEFAULT false NOT NULL,
    "can_delete" boolean DEFAULT false NOT NULL,
    "can_approve" boolean DEFAULT false NOT NULL,
    "can_export" boolean DEFAULT false NOT NULL,
    "can_import" boolean DEFAULT false NOT NULL,
    "can_print" boolean DEFAULT false NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_role_module_permissions" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_role_module_permissions_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_role_module_permissions_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_role_module_permissions_id_seq" OWNED BY "public"."sys_role_module_permissions"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_roles" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "name" character varying(100) NOT NULL,
    "description" "text",
    "record_status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_roles" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_roles_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_roles_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_roles_id_seq" OWNED BY "public"."sys_roles"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."sys_roles_ref_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_roles_ref_seq" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_system_preferences" (
    "id" integer NOT NULL,
    "setting_key" character varying(100) NOT NULL,
    "setting_label" character varying(200) NOT NULL,
    "setting_value" "text" DEFAULT ''::"text" NOT NULL,
    "setting_type" character varying(50) DEFAULT 'text'::character varying NOT NULL,
    "setting_group" character varying(100) DEFAULT 'General'::character varying NOT NULL,
    "options" "text",
    "description" "text",
    "is_system" boolean DEFAULT false NOT NULL,
    "updated_by" character varying(50),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_system_preferences" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_system_preferences_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_system_preferences_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_system_preferences_id_seq" OWNED BY "public"."sys_system_preferences"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_tms_module_documentation" (
    "id" bigint NOT NULL,
    "ref" character varying NOT NULL,
    "module_ref" character varying NOT NULL,
    "title" character varying,
    "subtitle" character varying,
    "purpose" "text",
    "business_problem" "text",
    "business_impact" "text",
    "when_to_use" "text",
    "workflow" "text",
    "prerequisites" "text",
    "best_practices" "text",
    "notes" "text",
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_tms_module_documentation" OWNER TO "postgres";


ALTER TABLE "public"."sys_tms_module_documentation" ALTER COLUMN "id" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME "public"."sys_tms_module_documentation_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);



CREATE TABLE IF NOT EXISTS "public"."sys_tms_module_number_series" (
    "id" integer NOT NULL,
    "ref" character varying NOT NULL,
    "module_ref" character varying,
    "prefix" character varying NOT NULL,
    "suffix" character varying,
    "current_number" integer DEFAULT 0 NOT NULL,
    "padding_length" integer DEFAULT 7 NOT NULL,
    "separator" character varying DEFAULT '-'::character varying,
    "reset_period" character varying DEFAULT 'never'::character varying,
    "record_status" character varying DEFAULT 'active'::character varying,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_tms_module_number_series" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_tms_module_number_series_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_tms_module_number_series_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_tms_module_number_series_id_seq" OWNED BY "public"."sys_tms_module_number_series"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_tms_modules" (
    "id" integer NOT NULL,
    "web_status" integer DEFAULT 0,
    "app_status" integer,
    "creates_journal_entry" boolean DEFAULT false,
    "ref" character varying,
    "subsection_ref" character varying,
    "folder" character varying,
    "name" character varying,
    "description" "text",
    "icon" character varying,
    "sort_order" integer,
    "record_status" character varying DEFAULT 'active'::character varying,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"(),
    "route" character varying,
    "component" character varying,
    "module_type" character varying
);


ALTER TABLE "public"."sys_tms_modules" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_tms_sections" (
    "id" integer,
    "ref" "text" NOT NULL,
    "name" "text",
    "folder" "text",
    "sort_order" integer,
    "web_icon" "text",
    "app_icon" "text",
    "web_color" "text",
    "app_color" "text",
    "web_enable" integer DEFAULT 1,
    "app_enable" integer DEFAULT 1,
    "description" "text",
    "record_status" character varying DEFAULT 'active'::character varying,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_tms_sections" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_tms_subsections" (
    "id" integer NOT NULL,
    "ref" character varying(6) NOT NULL,
    "section_ref" character varying NOT NULL,
    "name" character varying NOT NULL,
    "folder" character varying NOT NULL,
    "description" "text",
    "icon" character varying,
    "color" character varying,
    "sort_order" integer,
    "record_status" character varying DEFAULT 'active'::character varying,
    "created_at" timestamp with time zone DEFAULT "now"(),
    "updated_at" timestamp with time zone DEFAULT "now"()
);


ALTER TABLE "public"."sys_tms_subsections" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_tms_subsections_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_tms_subsections_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_tms_subsections_id_seq" OWNED BY "public"."sys_tms_subsections"."id";



CREATE TABLE IF NOT EXISTS "public"."sys_user_activity_log" (
    "id" bigint NOT NULL,
    "user_id" integer,
    "user_ref" "text",
    "user_name" "text",
    "user_role" "text",
    "action_type" "text" NOT NULL,
    "module" "text",
    "module_section" "text",
    "module_label" "text",
    "record_ref" "text",
    "description" "text",
    "metadata" "jsonb" DEFAULT '{}'::"jsonb" NOT NULL,
    "ip_address" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_user_activity_log" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_user_permissions_legacy" (
    "id" bigint NOT NULL,
    "user_ref" "text" NOT NULL,
    "module_id" integer NOT NULL,
    "can_access" boolean DEFAULT false NOT NULL
);


ALTER TABLE "public"."sys_user_permissions_legacy" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_user_roles" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "user_ref" character varying(20) NOT NULL,
    "role_ref" character varying(20) NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_user_roles" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_user_roles_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_user_roles_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_user_roles_id_seq" OWNED BY "public"."sys_user_roles"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."sys_user_roles_ref_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_user_roles_ref_seq" OWNER TO "postgres";


CREATE TABLE IF NOT EXISTS "public"."sys_users" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "username" character varying(150) NOT NULL,
    "password_hash" character varying(255) NOT NULL,
    "full_name" character varying(200) NOT NULL,
    "email" character varying(150) NOT NULL,
    "employee_ref" character varying(20),
    "record_status" character varying(20) DEFAULT 'active'::character varying NOT NULL,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."sys_users" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."sys_users_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."sys_users_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."sys_users_id_seq" OWNED BY "public"."sys_users"."id";



CREATE TABLE IF NOT EXISTS "public"."t_employee_payroll_lines" (
    "id" bigint NOT NULL,
    "ref" character varying(40) NOT NULL,
    "employee_payroll_ref" character varying NOT NULL,
    "salary_component_ref" character varying,
    "line_no" integer NOT NULL,
    "component_name" character varying(255),
    "component_type" character varying(50),
    "quantity" numeric DEFAULT 0,
    "rate" numeric DEFAULT 0,
    "amount" numeric DEFAULT 0,
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."t_employee_payroll_lines" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."t_employee_payroll_lines_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."t_employee_payroll_lines_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."t_employee_payroll_lines_id_seq" OWNED BY "public"."t_employee_payroll_lines"."id";



CREATE TABLE IF NOT EXISTS "public"."t_employee_payrolls" (
    "id" bigint NOT NULL,
    "ref" character varying(30) NOT NULL,
    "payroll_run_ref" character varying NOT NULL,
    "line_no" integer NOT NULL,
    "basic_salary" numeric DEFAULT 0,
    "gross_earnings" numeric DEFAULT 0,
    "total_deductions" numeric DEFAULT 0,
    "net_salary" numeric DEFAULT 0,
    "payment_status" character varying(20) DEFAULT 'Pending'::character varying,
    "paid_date" "date",
    "remarks" "text",
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "employment_record_ref" character varying
);


ALTER TABLE "public"."t_employee_payrolls" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."t_employee_payrolls_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."t_employee_payrolls_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."t_employee_payrolls_id_seq" OWNED BY "public"."t_employee_payrolls"."id";



CREATE TABLE IF NOT EXISTS "public"."t_quotation_lines" (
    "id" bigint NOT NULL,
    "ref" character varying(40) NOT NULL,
    "quotation_ref" character varying(20) NOT NULL,
    "line_no" integer NOT NULL,
    "item_name" character varying(255),
    "description" "text",
    "image" character varying(500),
    "qty" numeric(14,2),
    "unit" character varying(50),
    "unit_price" numeric(14,2),
    "discount" numeric(14,2),
    "tax" numeric(14,2),
    "amount" numeric(14,2),
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL
);


ALTER TABLE "public"."t_quotation_lines" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."t_quotation_lines_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."t_quotation_lines_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."t_quotation_lines_id_seq" OWNED BY "public"."t_quotation_lines"."id";



CREATE TABLE IF NOT EXISTS "public"."t_work_order_lines" (
    "id" bigint NOT NULL,
    "ref" character varying(20) NOT NULL,
    "work_order_ref" character varying(20),
    "line_no" integer,
    "vehicle_type_ref" character varying(20),
    "planned_start" timestamp with time zone,
    "planned_end" timestamp with time zone,
    "description" "text",
    "remarks" "text",
    "created_by" "text",
    "updated_by" "text",
    "created_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "updated_at" timestamp with time zone DEFAULT "now"() NOT NULL,
    "pickup_location_ref" character varying(20),
    "delivery_location_ref" character varying(20),
    "vehicle_ref" character varying(20),
    "driver_ref" character varying(20)
);


ALTER TABLE "public"."t_work_order_lines" OWNER TO "postgres";


CREATE SEQUENCE IF NOT EXISTS "public"."t_work_order_lines_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."t_work_order_lines_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."t_work_order_lines_id_seq" OWNED BY "public"."t_work_order_lines"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."tms_modules_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."tms_modules_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."tms_modules_id_seq" OWNED BY "public"."sys_tms_modules"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."trip_expenses_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."trip_expenses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."trip_expenses_id_seq" OWNED BY "public"."m_trip_expenses"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."trips_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."trips_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."trips_id_seq" OWNED BY "public"."m_trips"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."user_activity_log_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."user_activity_log_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."user_activity_log_id_seq" OWNED BY "public"."sys_user_activity_log"."id";



ALTER TABLE "public"."sys_user_permissions_legacy" ALTER COLUMN "id" ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME "public"."user_permissions_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);



CREATE SEQUENCE IF NOT EXISTS "public"."vehicle_expenses_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."vehicle_expenses_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."vehicle_expenses_id_seq" OWNED BY "public"."m_vehicle_expenses"."id";



CREATE SEQUENCE IF NOT EXISTS "public"."vehicles_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "public"."vehicles_id_seq" OWNER TO "postgres";


ALTER SEQUENCE "public"."vehicles_id_seq" OWNED BY "public"."m_vehicles"."id";



ALTER TABLE ONLY "public"."m_accounts_payable" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_accounts_payable_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_accounts_receivable" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_accounts_receivable_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_advance_payments" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."advance_payments_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_alert_rules" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_alert_rules_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_approval_requests" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_approval_requests_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_approval_workflow" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_approval_workflow_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_bank_reconciliation" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_bank_reconciliation_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_branches" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_branches_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_budgets" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_budgets_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_cash_bank" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_cash_bank_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_cash_flow_entries" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."cash_flow_entries_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_chart_of_accounts" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_chart_of_accounts_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."cleaner_salary_settlements_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_cleaners" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."cleaners_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_cost_centers" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_cost_centers_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_currencies" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_currencies_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_customer_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_customer_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_customers" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."customers_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_deductions" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."deductions_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_departments" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_departments_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_designations" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_designations_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."driver_cleaner_pairings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_driver_salary_settlements" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."driver_salary_settlements_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_drivers" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."drivers_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_employee_assignments" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_employee_assignments_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_employee_salary_structures" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_employee_salary_structures_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_employees" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_employees_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_employment_records" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_employment_records_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_employment_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_employment_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_finance_audit_trail" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_finance_audit_trail_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_financial_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_financial_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_fixed_assets" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_fixed_assets_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_fleet_statuses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_fleet_statuses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_fuel_expenses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."fuel_expenses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_fuel_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_fuel_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_general_expense_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."general_expense_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_general_expenses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."general_expenses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_general_ledger" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_general_ledger_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_gps_devices" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_gps_devices_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_items" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."items_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_journal_entries" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_journal_entries_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_journal_entry_lines" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_journal_entry_lines_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_loans" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."loans_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_locations" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."locations_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_maintenance_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_maintenance_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_payments" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."payments_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_payroll_inputs" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_payroll_inputs_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_payroll_periods" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_payroll_periods_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_payroll_profiles" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_payroll_profiles_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_payroll_runs" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_payroll_runs_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_period_closing" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_period_closing_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_posting_rules" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_posting_rules_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_quotation_statuses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_quotation_statuses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_quotations" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_quotations_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_salary_assignment_details" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_salary_assignment_details_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_salary_assignments" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_salary_assignments_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_salary_components" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_salary_components_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_tax_management" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_tax_management_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_trip_expenses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."trip_expenses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_trips" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."trips_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_user_preferences" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_user_preferences_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicle_brands" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_brands_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicle_document_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_document_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicle_expenses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."vehicle_expenses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicle_models" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_models_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicle_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_vehicles" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."vehicles_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_work_order_statuses" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_work_order_statuses_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_work_order_types" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_work_order_types_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_work_orders" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_work_orders_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_app_users_legacy" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."app_users_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_backup_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_backup_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_company_info" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."company_info_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_company_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_company_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_developer_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_developer_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_email_sms_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_email_sms_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_export_records" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."export_records_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_integrations" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_integrations_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_master_data_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_master_data_settings_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_notifications" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_notifications_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_role_module_permissions" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_role_module_permissions_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_roles" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_roles_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_system_preferences" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_system_preferences_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_tms_module_number_series" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_tms_module_number_series_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_tms_modules" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."tms_modules_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_tms_subsections" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_tms_subsections_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_user_activity_log" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."user_activity_log_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_user_roles" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_user_roles_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."sys_users" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."sys_users_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_employee_payroll_lines" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."t_employee_payroll_lines_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_employee_payrolls" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."t_employee_payrolls_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_quotation_lines" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."t_quotation_lines_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_quotation_resources" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_quotation_resources_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_vehicle_documents" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_documents_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_vehicle_gps" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_gps_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_vehicle_maintenance" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_maintenance_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_vehicle_odometer" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."m_vehicle_odometer_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."t_work_order_lines" ALTER COLUMN "id" SET DEFAULT "nextval"('"public"."t_work_order_lines_id_seq"'::"regclass");



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_app_users_legacy"
    ADD CONSTRAINT "app_users_email_key" UNIQUE ("email");



ALTER TABLE ONLY "public"."sys_app_users_legacy"
    ADD CONSTRAINT "app_users_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_app_users_legacy"
    ADD CONSTRAINT "app_users_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_cash_flow_entries"
    ADD CONSTRAINT "cash_flow_entries_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_cash_flow_entries"
    ADD CONSTRAINT "cash_flow_entries_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements"
    ADD CONSTRAINT "cleaner_salary_settlements_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements"
    ADD CONSTRAINT "cleaner_salary_settlements_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_cleaners"
    ADD CONSTRAINT "cleaners_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_cleaners"
    ADD CONSTRAINT "cleaners_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_company_info"
    ADD CONSTRAINT "company_info_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_customers"
    ADD CONSTRAINT "customers_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_customers"
    ADD CONSTRAINT "customers_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_driver_salary_settlements"
    ADD CONSTRAINT "driver_salary_settlements_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_driver_salary_settlements"
    ADD CONSTRAINT "driver_salary_settlements_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_drivers"
    ADD CONSTRAINT "drivers_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_drivers"
    ADD CONSTRAINT "drivers_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_export_records"
    ADD CONSTRAINT "export_records_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_fuel_expenses"
    ADD CONSTRAINT "fuel_expenses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_fuel_expenses"
    ADD CONSTRAINT "fuel_expenses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_general_expense_types"
    ADD CONSTRAINT "general_expense_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_general_expense_types"
    ADD CONSTRAINT "general_expense_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_general_expenses"
    ADD CONSTRAINT "general_expenses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_general_expenses"
    ADD CONSTRAINT "general_expenses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_items"
    ADD CONSTRAINT "items_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_items"
    ADD CONSTRAINT "items_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_locations"
    ADD CONSTRAINT "locations_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_locations"
    ADD CONSTRAINT "locations_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_accounts_payable"
    ADD CONSTRAINT "m_accounts_payable_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_accounts_payable"
    ADD CONSTRAINT "m_accounts_payable_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_accounts_receivable"
    ADD CONSTRAINT "m_accounts_receivable_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_accounts_receivable"
    ADD CONSTRAINT "m_accounts_receivable_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_alert_rules"
    ADD CONSTRAINT "m_alert_rules_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_alert_rules"
    ADD CONSTRAINT "m_alert_rules_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_approval_requests"
    ADD CONSTRAINT "m_approval_requests_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_approval_workflow"
    ADD CONSTRAINT "m_approval_workflow_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_approval_workflow"
    ADD CONSTRAINT "m_approval_workflow_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_bank_reconciliation"
    ADD CONSTRAINT "m_bank_reconciliation_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_bank_reconciliation"
    ADD CONSTRAINT "m_bank_reconciliation_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_branches"
    ADD CONSTRAINT "m_branches_branch_code_key" UNIQUE ("branch_code");



ALTER TABLE ONLY "public"."m_branches"
    ADD CONSTRAINT "m_branches_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_branches"
    ADD CONSTRAINT "m_branches_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_budgets"
    ADD CONSTRAINT "m_budgets_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_budgets"
    ADD CONSTRAINT "m_budgets_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_cash_bank"
    ADD CONSTRAINT "m_cash_bank_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_cash_bank"
    ADD CONSTRAINT "m_cash_bank_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_chart_of_accounts"
    ADD CONSTRAINT "m_chart_of_accounts_account_code_key" UNIQUE ("account_code");



ALTER TABLE ONLY "public"."m_chart_of_accounts"
    ADD CONSTRAINT "m_chart_of_accounts_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_chart_of_accounts"
    ADD CONSTRAINT "m_chart_of_accounts_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_cost_centers"
    ADD CONSTRAINT "m_cost_centers_cost_center_code_key" UNIQUE ("cost_center_code");



ALTER TABLE ONLY "public"."m_cost_centers"
    ADD CONSTRAINT "m_cost_centers_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_cost_centers"
    ADD CONSTRAINT "m_cost_centers_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_currencies"
    ADD CONSTRAINT "m_currencies_currency_code_key" UNIQUE ("currency_code");



ALTER TABLE ONLY "public"."m_currencies"
    ADD CONSTRAINT "m_currencies_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_currencies"
    ADD CONSTRAINT "m_currencies_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_customer_types"
    ADD CONSTRAINT "m_customer_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_customer_types"
    ADD CONSTRAINT "m_customer_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_departments"
    ADD CONSTRAINT "m_departments_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_departments"
    ADD CONSTRAINT "m_departments_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_designations"
    ADD CONSTRAINT "m_designations_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_designations"
    ADD CONSTRAINT "m_designations_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_employee_assignments"
    ADD CONSTRAINT "m_employee_assignments_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_employee_assignments"
    ADD CONSTRAINT "m_employee_assignments_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_employee_salary_structures"
    ADD CONSTRAINT "m_employee_salary_structures_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_employee_salary_structures"
    ADD CONSTRAINT "m_employee_salary_structures_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_employees"
    ADD CONSTRAINT "m_employees_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_employees"
    ADD CONSTRAINT "m_employees_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_employment_records"
    ADD CONSTRAINT "m_employment_records_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_employment_records"
    ADD CONSTRAINT "m_employment_records_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_employment_types"
    ADD CONSTRAINT "m_employment_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_employment_types"
    ADD CONSTRAINT "m_employment_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_finance_audit_trail"
    ADD CONSTRAINT "m_finance_audit_trail_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_financial_settings"
    ADD CONSTRAINT "m_financial_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_financial_settings"
    ADD CONSTRAINT "m_financial_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."m_fixed_assets"
    ADD CONSTRAINT "m_fixed_assets_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_fixed_assets"
    ADD CONSTRAINT "m_fixed_assets_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_fleet_statuses"
    ADD CONSTRAINT "m_fleet_statuses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_fleet_statuses"
    ADD CONSTRAINT "m_fleet_statuses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_fuel_types"
    ADD CONSTRAINT "m_fuel_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_fuel_types"
    ADD CONSTRAINT "m_fuel_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_general_ledger"
    ADD CONSTRAINT "m_general_ledger_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_general_ledger"
    ADD CONSTRAINT "m_general_ledger_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_gps_devices"
    ADD CONSTRAINT "m_gps_devices_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_gps_devices"
    ADD CONSTRAINT "m_gps_devices_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_journal_entries"
    ADD CONSTRAINT "m_journal_entries_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_journal_entries"
    ADD CONSTRAINT "m_journal_entries_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_journal_entry_lines"
    ADD CONSTRAINT "m_journal_entry_lines_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_maintenance_types"
    ADD CONSTRAINT "m_maintenance_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_maintenance_types"
    ADD CONSTRAINT "m_maintenance_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_payroll_inputs"
    ADD CONSTRAINT "m_payroll_inputs_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_payroll_inputs"
    ADD CONSTRAINT "m_payroll_inputs_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_payroll_periods"
    ADD CONSTRAINT "m_payroll_periods_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_payroll_periods"
    ADD CONSTRAINT "m_payroll_periods_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_payroll_profiles"
    ADD CONSTRAINT "m_payroll_profiles_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_payroll_profiles"
    ADD CONSTRAINT "m_payroll_profiles_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_payroll_runs"
    ADD CONSTRAINT "m_payroll_runs_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_payroll_runs"
    ADD CONSTRAINT "m_payroll_runs_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_period_closing"
    ADD CONSTRAINT "m_period_closing_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_period_closing"
    ADD CONSTRAINT "m_period_closing_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_posting_rules"
    ADD CONSTRAINT "m_posting_rules_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_quotation_resources"
    ADD CONSTRAINT "m_quotation_resources_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_quotation_resources"
    ADD CONSTRAINT "m_quotation_resources_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_quotation_statuses"
    ADD CONSTRAINT "m_quotation_statuses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_quotation_statuses"
    ADD CONSTRAINT "m_quotation_statuses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_salary_assignment_details"
    ADD CONSTRAINT "m_salary_assignment_details_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_salary_assignments"
    ADD CONSTRAINT "m_salary_assignments_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_salary_assignments"
    ADD CONSTRAINT "m_salary_assignments_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_salary_components"
    ADD CONSTRAINT "m_salary_components_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_salary_components"
    ADD CONSTRAINT "m_salary_components_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_tax_management"
    ADD CONSTRAINT "m_tax_management_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_tax_management"
    ADD CONSTRAINT "m_tax_management_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_tax_management"
    ADD CONSTRAINT "m_tax_management_tax_code_key" UNIQUE ("tax_code");



ALTER TABLE ONLY "public"."m_user_preferences"
    ADD CONSTRAINT "m_user_preferences_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_user_preferences"
    ADD CONSTRAINT "m_user_preferences_user_ref_key" UNIQUE ("user_ref");



ALTER TABLE ONLY "public"."m_vehicle_brands"
    ADD CONSTRAINT "m_vehicle_brands_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicle_brands"
    ADD CONSTRAINT "m_vehicle_brands_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_vehicle_document_types"
    ADD CONSTRAINT "m_vehicle_document_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicle_document_types"
    ADD CONSTRAINT "m_vehicle_document_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_vehicle_documents"
    ADD CONSTRAINT "m_vehicle_documents_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_vehicle_documents"
    ADD CONSTRAINT "m_vehicle_documents_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_vehicle_gps"
    ADD CONSTRAINT "m_vehicle_gps_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_vehicle_gps"
    ADD CONSTRAINT "m_vehicle_gps_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_vehicle_maintenance"
    ADD CONSTRAINT "m_vehicle_maintenance_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_vehicle_maintenance"
    ADD CONSTRAINT "m_vehicle_maintenance_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_vehicle_models"
    ADD CONSTRAINT "m_vehicle_models_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicle_models"
    ADD CONSTRAINT "m_vehicle_models_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_vehicle_odometer"
    ADD CONSTRAINT "m_vehicle_odometer_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_vehicle_odometer"
    ADD CONSTRAINT "m_vehicle_odometer_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_vehicle_types"
    ADD CONSTRAINT "m_vehicle_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicle_types"
    ADD CONSTRAINT "m_vehicle_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_work_order_statuses"
    ADD CONSTRAINT "m_work_order_statuses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_work_order_statuses"
    ADD CONSTRAINT "m_work_order_statuses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_work_order_types"
    ADD CONSTRAINT "m_work_order_types_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_work_order_types"
    ADD CONSTRAINT "m_work_order_types_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_backup_settings"
    ADD CONSTRAINT "sys_backup_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_backup_settings"
    ADD CONSTRAINT "sys_backup_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_company_settings"
    ADD CONSTRAINT "sys_company_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_company_settings"
    ADD CONSTRAINT "sys_company_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_developer_settings"
    ADD CONSTRAINT "sys_developer_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_developer_settings"
    ADD CONSTRAINT "sys_developer_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_email_sms_settings"
    ADD CONSTRAINT "sys_email_sms_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_email_sms_settings"
    ADD CONSTRAINT "sys_email_sms_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_integrations"
    ADD CONSTRAINT "sys_integrations_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_integrations"
    ADD CONSTRAINT "sys_integrations_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_master_data_settings"
    ADD CONSTRAINT "sys_master_data_settings_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_master_data_settings"
    ADD CONSTRAINT "sys_master_data_settings_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_notifications"
    ADD CONSTRAINT "sys_notifications_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_notifications"
    ADD CONSTRAINT "sys_notifications_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_role_module_permissions"
    ADD CONSTRAINT "sys_role_module_permissions_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_role_module_permissions"
    ADD CONSTRAINT "sys_role_module_permissions_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_role_module_permissions"
    ADD CONSTRAINT "sys_role_module_permissions_role_ref_module_ref_key" UNIQUE ("role_ref", "module_ref");



ALTER TABLE ONLY "public"."sys_roles"
    ADD CONSTRAINT "sys_roles_name_key" UNIQUE ("name");



ALTER TABLE ONLY "public"."sys_roles"
    ADD CONSTRAINT "sys_roles_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_roles"
    ADD CONSTRAINT "sys_roles_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_system_preferences"
    ADD CONSTRAINT "sys_system_preferences_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_system_preferences"
    ADD CONSTRAINT "sys_system_preferences_setting_key_key" UNIQUE ("setting_key");



ALTER TABLE ONLY "public"."sys_tms_module_documentation"
    ADD CONSTRAINT "sys_tms_module_documentation_module_ref_key" UNIQUE ("module_ref");



ALTER TABLE ONLY "public"."sys_tms_module_documentation"
    ADD CONSTRAINT "sys_tms_module_documentation_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_tms_module_documentation"
    ADD CONSTRAINT "sys_tms_module_documentation_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_tms_module_number_series"
    ADD CONSTRAINT "sys_tms_module_number_series_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_tms_module_number_series"
    ADD CONSTRAINT "sys_tms_module_number_series_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_tms_modules"
    ADD CONSTRAINT "sys_tms_modules_ref_unique" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_tms_sections"
    ADD CONSTRAINT "sys_tms_sections_pkey" PRIMARY KEY ("ref");



ALTER TABLE ONLY "public"."sys_tms_sections"
    ADD CONSTRAINT "sys_tms_sections_ref_unique" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_tms_subsections"
    ADD CONSTRAINT "sys_tms_subsections_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_tms_subsections"
    ADD CONSTRAINT "sys_tms_subsections_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_user_roles"
    ADD CONSTRAINT "sys_user_roles_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_user_roles"
    ADD CONSTRAINT "sys_user_roles_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_user_roles"
    ADD CONSTRAINT "sys_user_roles_user_ref_role_ref_key" UNIQUE ("user_ref", "role_ref");



ALTER TABLE ONLY "public"."sys_users"
    ADD CONSTRAINT "sys_users_email_key" UNIQUE ("email");



ALTER TABLE ONLY "public"."sys_users"
    ADD CONSTRAINT "sys_users_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_users"
    ADD CONSTRAINT "sys_users_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_users"
    ADD CONSTRAINT "sys_users_username_key" UNIQUE ("username");



ALTER TABLE ONLY "public"."t_employee_payroll_lines"
    ADD CONSTRAINT "t_employee_payroll_lines_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_employee_payroll_lines"
    ADD CONSTRAINT "t_employee_payroll_lines_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_employee_payrolls"
    ADD CONSTRAINT "t_employee_payrolls_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_employee_payrolls"
    ADD CONSTRAINT "t_employee_payrolls_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_quotation_lines"
    ADD CONSTRAINT "t_quotation_lines_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_quotation_lines"
    ADD CONSTRAINT "t_quotation_lines_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_tms_modules"
    ADD CONSTRAINT "tms_modules_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."sys_user_activity_log"
    ADD CONSTRAINT "user_activity_log_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_user_permissions_legacy"
    ADD CONSTRAINT "user_permissions_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."sys_user_permissions_legacy"
    ADD CONSTRAINT "user_permissions_user_ref_module_id_key" UNIQUE ("user_ref", "module_id");



ALTER TABLE ONLY "public"."m_vehicle_expenses"
    ADD CONSTRAINT "vehicle_expenses_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicle_expenses"
    ADD CONSTRAINT "vehicle_expenses_ref_key" UNIQUE ("ref");



ALTER TABLE ONLY "public"."m_vehicles"
    ADD CONSTRAINT "vehicles_pkey" PRIMARY KEY ("id");



ALTER TABLE ONLY "public"."m_vehicles"
    ADD CONSTRAINT "vehicles_plate_number_key" UNIQUE ("plate_number");



ALTER TABLE ONLY "public"."m_vehicles"
    ADD CONSTRAINT "vehicles_ref_key" UNIQUE ("ref");



CREATE INDEX "idx_advance_payments_date" ON "public"."m_advance_payments" USING "btree" ("date");



CREATE INDEX "idx_app_users_role" ON "public"."sys_app_users_legacy" USING "btree" ("role");



CREATE INDEX "idx_app_users_status" ON "public"."sys_app_users_legacy" USING "btree" ("status");



CREATE INDEX "idx_er_created_at" ON "public"."sys_export_records" USING "btree" ("created_at" DESC);



CREATE INDEX "idx_er_type" ON "public"."sys_export_records" USING "btree" ("export_type");



CREATE INDEX "idx_er_user_id" ON "public"."sys_export_records" USING "btree" ("user_id");



CREATE INDEX "idx_fuel_expenses_date" ON "public"."m_fuel_expenses" USING "btree" ("date");



CREATE INDEX "idx_general_expense_types_status" ON "public"."m_general_expense_types" USING "btree" ("status");



CREATE INDEX "idx_general_expenses_date" ON "public"."m_general_expenses" USING "btree" ("date");



CREATE INDEX "idx_jnl_source" ON "public"."m_journal_entries" USING "btree" ("source_type", "source_ref");



CREATE INDEX "idx_locations_status" ON "public"."m_locations" USING "btree" ("status");



CREATE INDEX "idx_posting_rules_module" ON "public"."m_posting_rules" USING "btree" ("module_system_name", "variant");



CREATE INDEX "idx_salary_assignment_details_ref" ON "public"."m_salary_assignment_details" USING "btree" ("salary_assignment_ref");



CREATE INDEX "idx_sys_rmp_module_ref" ON "public"."sys_role_module_permissions" USING "btree" ("module_ref");



CREATE INDEX "idx_sys_rmp_role_ref" ON "public"."sys_role_module_permissions" USING "btree" ("role_ref");



CREATE INDEX "idx_sys_user_roles_role_ref" ON "public"."sys_user_roles" USING "btree" ("role_ref");



CREATE INDEX "idx_sys_user_roles_user_ref" ON "public"."sys_user_roles" USING "btree" ("user_ref");



CREATE INDEX "idx_ual_action_type" ON "public"."sys_user_activity_log" USING "btree" ("action_type");



CREATE INDEX "idx_ual_created_at" ON "public"."sys_user_activity_log" USING "btree" ("created_at" DESC);



CREATE INDEX "idx_ual_module" ON "public"."sys_user_activity_log" USING "btree" ("module");



CREATE INDEX "idx_ual_user_id" ON "public"."sys_user_activity_log" USING "btree" ("user_id");



CREATE INDEX "idx_vehicle_expenses_category" ON "public"."m_vehicle_expenses" USING "btree" ("category");



CREATE INDEX "idx_vehicle_expenses_date" ON "public"."m_vehicle_expenses" USING "btree" ("date");



CREATE INDEX "m_alert_rules_category_idx" ON "public"."m_alert_rules" USING "btree" ("category");



CREATE INDEX "m_finance_audit_trail_event_time_idx" ON "public"."m_finance_audit_trail" USING "btree" ("event_time" DESC);



CREATE INDEX "m_finance_audit_trail_module_idx" ON "public"."m_finance_audit_trail" USING "btree" ("module");



CREATE UNIQUE INDEX "m_period_closing_period_idx" ON "public"."m_period_closing" USING "btree" ("period");



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_advance_payments" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_cash_flow_entries" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_cleaner_salary_settlements" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_cleaners" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_customers" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_deductions" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_driver_cleaner_pairings" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_driver_salary_settlements" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_drivers" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_fuel_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_general_expense_types" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_general_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_items" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_loans" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_locations" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_payments" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_trip_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_trips" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_vehicle_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."m_vehicles" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "set_updated_at" BEFORE UPDATE ON "public"."t_work_order_lines" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_advance_payments_updated_at" BEFORE UPDATE ON "public"."m_advance_payments" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_app_users_updated_at" BEFORE UPDATE ON "public"."sys_app_users_legacy" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_cleaners_updated_at" BEFORE UPDATE ON "public"."m_cleaners" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_drivers_updated_at" BEFORE UPDATE ON "public"."m_drivers" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_fuel_expenses_updated_at" BEFORE UPDATE ON "public"."m_fuel_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_general_expense_types_updated_at" BEFORE UPDATE ON "public"."m_general_expense_types" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_general_expenses_updated_at" BEFORE UPDATE ON "public"."m_general_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_items_updated_at" BEFORE UPDATE ON "public"."m_items" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_locations_updated_at" BEFORE UPDATE ON "public"."m_locations" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_sys_rmp_ref" BEFORE INSERT ON "public"."sys_role_module_permissions" FOR EACH ROW EXECUTE FUNCTION "public"."sys_rmp_set_ref"();



CREATE OR REPLACE TRIGGER "trg_sys_roles_ref" BEFORE INSERT ON "public"."sys_roles" FOR EACH ROW EXECUTE FUNCTION "public"."sys_roles_set_ref"();



CREATE OR REPLACE TRIGGER "trg_sys_user_roles_ref" BEFORE INSERT ON "public"."sys_user_roles" FOR EACH ROW EXECUTE FUNCTION "public"."sys_user_roles_set_ref"();



CREATE OR REPLACE TRIGGER "trg_vehicle_expenses_updated_at" BEFORE UPDATE ON "public"."m_vehicle_expenses" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



CREATE OR REPLACE TRIGGER "trg_vehicles_updated_at" BEFORE UPDATE ON "public"."m_vehicles" FOR EACH ROW EXECUTE FUNCTION "public"."set_updated_at"();



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_advance_payments"
    ADD CONSTRAINT "advance_payments_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cash_flow_entries"
    ADD CONSTRAINT "cash_flow_entries_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cash_flow_entries"
    ADD CONSTRAINT "cash_flow_entries_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements"
    ADD CONSTRAINT "cleaner_salary_settlements_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements"
    ADD CONSTRAINT "cleaner_salary_settlements_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cleaner_salary_settlements"
    ADD CONSTRAINT "cleaner_salary_settlements_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cleaners"
    ADD CONSTRAINT "cleaners_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_cleaners"
    ADD CONSTRAINT "cleaners_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_customers"
    ADD CONSTRAINT "customers_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_customers"
    ADD CONSTRAINT "customers_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_deductions"
    ADD CONSTRAINT "deductions_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_driver_cleaner_pairings"
    ADD CONSTRAINT "driver_cleaner_pairings_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_driver_salary_settlements"
    ADD CONSTRAINT "driver_salary_settlements_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_driver_salary_settlements"
    ADD CONSTRAINT "driver_salary_settlements_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_driver_salary_settlements"
    ADD CONSTRAINT "driver_salary_settlements_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_drivers"
    ADD CONSTRAINT "drivers_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_drivers"
    ADD CONSTRAINT "drivers_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."sys_export_records"
    ADD CONSTRAINT "export_records_log_id_fkey" FOREIGN KEY ("log_id") REFERENCES "public"."sys_user_activity_log"("id") ON DELETE SET NULL;



ALTER TABLE ONLY "public"."m_fuel_expenses"
    ADD CONSTRAINT "fuel_expenses_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_fuel_expenses"
    ADD CONSTRAINT "fuel_expenses_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_fuel_expenses"
    ADD CONSTRAINT "fuel_expenses_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."m_general_expense_types"
    ADD CONSTRAINT "general_expense_types_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_general_expense_types"
    ADD CONSTRAINT "general_expense_types_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_general_expenses"
    ADD CONSTRAINT "general_expenses_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_general_expenses"
    ADD CONSTRAINT "general_expenses_expense_type_ref_fkey" FOREIGN KEY ("expense_type_ref") REFERENCES "public"."m_general_expense_types"("ref");



ALTER TABLE ONLY "public"."m_general_expenses"
    ADD CONSTRAINT "general_expenses_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_items"
    ADD CONSTRAINT "items_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_items"
    ADD CONSTRAINT "items_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_loans"
    ADD CONSTRAINT "loans_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_locations"
    ADD CONSTRAINT "locations_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_locations"
    ADD CONSTRAINT "locations_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_approval_requests"
    ADD CONSTRAINT "m_approval_requests_workflow_ref_fkey" FOREIGN KEY ("workflow_ref") REFERENCES "public"."m_approval_workflow"("ref") ON DELETE RESTRICT;



ALTER TABLE ONLY "public"."m_cleaners"
    ADD CONSTRAINT "m_cleaners_employee_ref_fkey" FOREIGN KEY ("employee_ref") REFERENCES "public"."m_employees"("ref");



ALTER TABLE ONLY "public"."m_customers"
    ADD CONSTRAINT "m_customers_customer_type_ref_fkey" FOREIGN KEY ("customer_type_ref") REFERENCES "public"."m_customer_types"("ref");



ALTER TABLE ONLY "public"."m_drivers"
    ADD CONSTRAINT "m_drivers_employee_ref_fkey" FOREIGN KEY ("employee_ref") REFERENCES "public"."m_employees"("ref");



ALTER TABLE ONLY "public"."m_employee_assignments"
    ADD CONSTRAINT "m_employee_assignments_department_ref_fkey" FOREIGN KEY ("department_ref") REFERENCES "public"."m_departments"("ref");



ALTER TABLE ONLY "public"."m_employee_assignments"
    ADD CONSTRAINT "m_employee_assignments_designation_ref_fkey" FOREIGN KEY ("designation_ref") REFERENCES "public"."m_designations"("ref");



ALTER TABLE ONLY "public"."m_employee_assignments"
    ADD CONSTRAINT "m_employee_assignments_employment_record_ref_fkey" FOREIGN KEY ("employment_record_ref") REFERENCES "public"."m_employment_records"("ref");



ALTER TABLE ONLY "public"."m_employee_salary_structures"
    ADD CONSTRAINT "m_employee_salary_structures_employee_ref_fkey" FOREIGN KEY ("employee_ref") REFERENCES "public"."m_employees"("ref");



ALTER TABLE ONLY "public"."m_employee_salary_structures"
    ADD CONSTRAINT "m_employee_salary_structures_salary_component_ref_fkey" FOREIGN KEY ("salary_component_ref") REFERENCES "public"."m_salary_components"("ref");



ALTER TABLE ONLY "public"."m_employment_records"
    ADD CONSTRAINT "m_employment_records_employee_ref_fkey" FOREIGN KEY ("employee_ref") REFERENCES "public"."m_employees"("ref");



ALTER TABLE ONLY "public"."m_employment_records"
    ADD CONSTRAINT "m_employment_records_employment_type_ref_fkey" FOREIGN KEY ("employment_type_ref") REFERENCES "public"."m_employment_types"("ref");



ALTER TABLE ONLY "public"."m_employment_records"
    ADD CONSTRAINT "m_employment_records_payroll_profile_ref_fkey" FOREIGN KEY ("payroll_profile_ref") REFERENCES "public"."m_payroll_profiles"("ref");



ALTER TABLE ONLY "public"."m_journal_entry_lines"
    ADD CONSTRAINT "m_journal_entry_lines_journal_ref_fkey" FOREIGN KEY ("journal_ref") REFERENCES "public"."m_journal_entries"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."m_payroll_inputs"
    ADD CONSTRAINT "m_payroll_inputs_employment_record_ref_fkey" FOREIGN KEY ("employment_record_ref") REFERENCES "public"."m_employment_records"("ref");



ALTER TABLE ONLY "public"."m_payroll_inputs"
    ADD CONSTRAINT "m_payroll_inputs_payroll_period_ref_fkey" FOREIGN KEY ("payroll_period_ref") REFERENCES "public"."m_payroll_periods"("ref");



ALTER TABLE ONLY "public"."m_payroll_inputs"
    ADD CONSTRAINT "m_payroll_inputs_salary_component_ref_fkey" FOREIGN KEY ("salary_component_ref") REFERENCES "public"."m_salary_components"("ref");



ALTER TABLE ONLY "public"."m_payroll_periods"
    ADD CONSTRAINT "m_payroll_periods_payroll_profile_ref_fkey" FOREIGN KEY ("payroll_profile_ref") REFERENCES "public"."m_payroll_profiles"("ref");



ALTER TABLE ONLY "public"."m_payroll_profiles"
    ADD CONSTRAINT "m_payroll_profiles_currency_ref_fkey" FOREIGN KEY ("currency_ref") REFERENCES "public"."m_currencies"("ref");



ALTER TABLE ONLY "public"."m_payroll_runs"
    ADD CONSTRAINT "m_payroll_runs_payroll_period_ref_fkey" FOREIGN KEY ("payroll_period_ref") REFERENCES "public"."m_payroll_periods"("ref");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_currency_ref_fkey" FOREIGN KEY ("currency_ref") REFERENCES "public"."m_currencies"("ref");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_customer_ref_fkey" FOREIGN KEY ("customer_ref") REFERENCES "public"."m_customers"("ref");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_quotation_status_ref_fkey" FOREIGN KEY ("quotation_status_ref") REFERENCES "public"."m_quotation_statuses"("ref");



ALTER TABLE ONLY "public"."m_quotations"
    ADD CONSTRAINT "m_quotations_salesperson_ref_fkey" FOREIGN KEY ("salesperson_ref") REFERENCES "public"."m_employees"("ref");



ALTER TABLE ONLY "public"."m_salary_assignment_details"
    ADD CONSTRAINT "m_salary_assignment_details_salary_assignment_ref_fkey" FOREIGN KEY ("salary_assignment_ref") REFERENCES "public"."m_salary_assignments"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."m_salary_assignment_details"
    ADD CONSTRAINT "m_salary_assignment_details_salary_component_ref_fkey" FOREIGN KEY ("salary_component_ref") REFERENCES "public"."m_salary_components"("ref");



ALTER TABLE ONLY "public"."m_salary_assignments"
    ADD CONSTRAINT "m_salary_assignments_currency_ref_fkey" FOREIGN KEY ("currency_ref") REFERENCES "public"."m_currencies"("ref");



ALTER TABLE ONLY "public"."m_salary_assignments"
    ADD CONSTRAINT "m_salary_assignments_employment_record_ref_fkey" FOREIGN KEY ("employment_record_ref") REFERENCES "public"."m_employment_records"("ref");



ALTER TABLE ONLY "public"."m_vehicle_models"
    ADD CONSTRAINT "m_vehicle_models_brand_ref_fkey" FOREIGN KEY ("brand_ref") REFERENCES "public"."m_vehicle_brands"("ref");



ALTER TABLE ONLY "public"."m_vehicle_models"
    ADD CONSTRAINT "m_vehicle_models_fuel_type_ref_fkey" FOREIGN KEY ("fuel_type_ref") REFERENCES "public"."m_fuel_types"("ref");



ALTER TABLE ONLY "public"."m_vehicle_models"
    ADD CONSTRAINT "m_vehicle_models_vehicle_type_ref_fkey" FOREIGN KEY ("vehicle_type_ref") REFERENCES "public"."m_vehicle_types"("ref");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_customer_ref_fkey" FOREIGN KEY ("customer_ref") REFERENCES "public"."m_customers"("ref");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_quotation_ref_fkey" FOREIGN KEY ("quotation_ref") REFERENCES "public"."m_quotations"("ref");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_work_order_status_ref_fkey" FOREIGN KEY ("work_order_status_ref") REFERENCES "public"."m_work_order_statuses"("ref");



ALTER TABLE ONLY "public"."m_work_orders"
    ADD CONSTRAINT "m_work_orders_work_order_type_ref_fkey" FOREIGN KEY ("work_order_type_ref") REFERENCES "public"."m_work_order_types"("ref");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_payments"
    ADD CONSTRAINT "payments_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."sys_role_module_permissions"
    ADD CONSTRAINT "sys_role_module_permissions_module_ref_fkey" FOREIGN KEY ("module_ref") REFERENCES "public"."sys_tms_modules"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."sys_role_module_permissions"
    ADD CONSTRAINT "sys_role_module_permissions_role_ref_fkey" FOREIGN KEY ("role_ref") REFERENCES "public"."sys_roles"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."sys_tms_module_documentation"
    ADD CONSTRAINT "sys_tms_module_documentation_module_ref_fkey" FOREIGN KEY ("module_ref") REFERENCES "public"."sys_tms_modules"("ref");



ALTER TABLE ONLY "public"."sys_tms_module_number_series"
    ADD CONSTRAINT "sys_tms_module_number_series_module_ref_fkey" FOREIGN KEY ("module_ref") REFERENCES "public"."sys_tms_modules"("ref");



ALTER TABLE ONLY "public"."sys_tms_modules"
    ADD CONSTRAINT "sys_tms_modules_subsection_ref_fkey" FOREIGN KEY ("subsection_ref") REFERENCES "public"."sys_tms_subsections"("ref");



ALTER TABLE ONLY "public"."sys_tms_subsections"
    ADD CONSTRAINT "sys_tms_subsections_section_ref_fkey" FOREIGN KEY ("section_ref") REFERENCES "public"."sys_tms_sections"("ref");



ALTER TABLE ONLY "public"."sys_user_roles"
    ADD CONSTRAINT "sys_user_roles_role_ref_fkey" FOREIGN KEY ("role_ref") REFERENCES "public"."sys_roles"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."sys_user_roles"
    ADD CONSTRAINT "sys_user_roles_user_ref_fkey" FOREIGN KEY ("user_ref") REFERENCES "public"."sys_users"("ref") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."t_employee_payroll_lines"
    ADD CONSTRAINT "t_employee_payroll_lines_employee_payroll_ref_fkey" FOREIGN KEY ("employee_payroll_ref") REFERENCES "public"."t_employee_payrolls"("ref");



ALTER TABLE ONLY "public"."t_employee_payroll_lines"
    ADD CONSTRAINT "t_employee_payroll_lines_salary_component_ref_fkey" FOREIGN KEY ("salary_component_ref") REFERENCES "public"."m_salary_components"("ref");



ALTER TABLE ONLY "public"."t_employee_payrolls"
    ADD CONSTRAINT "t_employee_payrolls_employment_record_ref_fkey" FOREIGN KEY ("employment_record_ref") REFERENCES "public"."m_employment_records"("ref");



ALTER TABLE ONLY "public"."t_employee_payrolls"
    ADD CONSTRAINT "t_employee_payrolls_payroll_run_ref_fkey" FOREIGN KEY ("payroll_run_ref") REFERENCES "public"."m_payroll_runs"("ref");



ALTER TABLE ONLY "public"."t_quotation_lines"
    ADD CONSTRAINT "t_quotation_lines_quotation_ref_fkey" FOREIGN KEY ("quotation_ref") REFERENCES "public"."m_quotations"("ref");



ALTER TABLE ONLY "public"."t_quotation_resources"
    ADD CONSTRAINT "t_quotation_resources_quotation_ref_fkey" FOREIGN KEY ("quotation_ref") REFERENCES "public"."m_quotations"("ref");



ALTER TABLE ONLY "public"."t_quotation_resources"
    ADD CONSTRAINT "t_quotation_resources_vehicle_type_ref_fkey" FOREIGN KEY ("vehicle_type_ref") REFERENCES "public"."m_vehicle_types"("ref");



ALTER TABLE ONLY "public"."t_vehicle_documents"
    ADD CONSTRAINT "t_vehicle_documents_document_type_ref_fkey" FOREIGN KEY ("document_type_ref") REFERENCES "public"."m_vehicle_document_types"("ref");



ALTER TABLE ONLY "public"."t_vehicle_documents"
    ADD CONSTRAINT "t_vehicle_documents_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."t_vehicle_gps"
    ADD CONSTRAINT "t_vehicle_gps_gps_device_ref_fkey" FOREIGN KEY ("gps_device_ref") REFERENCES "public"."m_gps_devices"("ref");



ALTER TABLE ONLY "public"."t_vehicle_gps"
    ADD CONSTRAINT "t_vehicle_gps_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."t_vehicle_maintenance"
    ADD CONSTRAINT "t_vehicle_maintenance_maintenance_type_ref_fkey" FOREIGN KEY ("maintenance_type_ref") REFERENCES "public"."m_maintenance_types"("ref");



ALTER TABLE ONLY "public"."t_vehicle_maintenance"
    ADD CONSTRAINT "t_vehicle_maintenance_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."t_vehicle_odometer"
    ADD CONSTRAINT "t_vehicle_odometer_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_delivery_location_ref_fkey" FOREIGN KEY ("delivery_location_ref") REFERENCES "public"."m_locations"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_pickup_location_ref_fkey" FOREIGN KEY ("pickup_location_ref") REFERENCES "public"."m_locations"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_vehicle_type_ref_fkey" FOREIGN KEY ("vehicle_type_ref") REFERENCES "public"."m_vehicle_types"("ref");



ALTER TABLE ONLY "public"."t_work_order_lines"
    ADD CONSTRAINT "t_work_order_lines_work_order_ref_fkey" FOREIGN KEY ("work_order_ref") REFERENCES "public"."m_work_orders"("ref");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_trip_id_fkey" FOREIGN KEY ("trip_id") REFERENCES "public"."m_trips"("id");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_trip_expenses"
    ADD CONSTRAINT "trip_expenses_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_cleaner_ref_fkey" FOREIGN KEY ("cleaner_ref") REFERENCES "public"."m_cleaners"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_driver_ref_fkey" FOREIGN KEY ("driver_ref") REFERENCES "public"."m_drivers"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_item_ref_fkey" FOREIGN KEY ("item_ref") REFERENCES "public"."m_items"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "public"."sys_app_users_legacy"("id") ON DELETE SET NULL;



ALTER TABLE ONLY "public"."m_trips"
    ADD CONSTRAINT "trips_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."sys_user_permissions_legacy"
    ADD CONSTRAINT "user_permissions_module_id_fkey" FOREIGN KEY ("module_id") REFERENCES "public"."sys_tms_modules"("id") ON DELETE CASCADE;



ALTER TABLE ONLY "public"."m_vehicle_expenses"
    ADD CONSTRAINT "vehicle_expenses_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_vehicle_expenses"
    ADD CONSTRAINT "vehicle_expenses_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_vehicle_expenses"
    ADD CONSTRAINT "vehicle_expenses_vehicle_ref_fkey" FOREIGN KEY ("vehicle_ref") REFERENCES "public"."m_vehicles"("ref");



ALTER TABLE ONLY "public"."m_vehicles"
    ADD CONSTRAINT "vehicles_created_by_fkey" FOREIGN KEY ("created_by") REFERENCES "public"."sys_app_users_legacy"("ref");



ALTER TABLE ONLY "public"."m_vehicles"
    ADD CONSTRAINT "vehicles_updated_by_fkey" FOREIGN KEY ("updated_by") REFERENCES "public"."sys_app_users_legacy"("ref");



CREATE POLICY "anon can read tms_modules" ON "public"."sys_tms_modules" FOR SELECT TO "anon" USING (true);



ALTER TABLE "public"."sys_tms_modules" ENABLE ROW LEVEL SECURITY;




ALTER PUBLICATION "supabase_realtime" OWNER TO "postgres";


GRANT USAGE ON SCHEMA "public" TO "postgres";
GRANT USAGE ON SCHEMA "public" TO "anon";
GRANT USAGE ON SCHEMA "public" TO "authenticated";
GRANT USAGE ON SCHEMA "public" TO "service_role";






















































































































































GRANT ALL ON FUNCTION "public"."create_module_line_table"("p_table" character varying, "p_parent_fk_column" character varying, "p_columns" "jsonb") TO "anon";
GRANT ALL ON FUNCTION "public"."create_module_line_table"("p_table" character varying, "p_parent_fk_column" character varying, "p_columns" "jsonb") TO "authenticated";
GRANT ALL ON FUNCTION "public"."create_module_line_table"("p_table" character varying, "p_parent_fk_column" character varying, "p_columns" "jsonb") TO "service_role";



GRANT ALL ON FUNCTION "public"."create_module_table"("p_table" character varying, "p_columns" "jsonb") TO "anon";
GRANT ALL ON FUNCTION "public"."create_module_table"("p_table" character varying, "p_columns" "jsonb") TO "authenticated";
GRANT ALL ON FUNCTION "public"."create_module_table"("p_table" character varying, "p_columns" "jsonb") TO "service_role";



GRANT ALL ON FUNCTION "public"."drop_module_line_table"("p_table" character varying) TO "anon";
GRANT ALL ON FUNCTION "public"."drop_module_line_table"("p_table" character varying) TO "authenticated";
GRANT ALL ON FUNCTION "public"."drop_module_line_table"("p_table" character varying) TO "service_role";



GRANT ALL ON FUNCTION "public"."drop_module_table"("p_table" character varying) TO "anon";
GRANT ALL ON FUNCTION "public"."drop_module_table"("p_table" character varying) TO "authenticated";
GRANT ALL ON FUNCTION "public"."drop_module_table"("p_table" character varying) TO "service_role";



GRANT ALL ON FUNCTION "public"."get_schema_overview"() TO "anon";
GRANT ALL ON FUNCTION "public"."get_schema_overview"() TO "authenticated";
GRANT ALL ON FUNCTION "public"."get_schema_overview"() TO "service_role";



GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "anon";
GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "authenticated";
GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "service_role";



GRANT ALL ON FUNCTION "public"."register_tms_module"("p_subsection_ref" character varying, "p_folder" character varying, "p_name" character varying, "p_sort_order" integer, "p_module_type" character varying, "p_creates_journal_entry" boolean) TO "anon";
GRANT ALL ON FUNCTION "public"."register_tms_module"("p_subsection_ref" character varying, "p_folder" character varying, "p_name" character varying, "p_sort_order" integer, "p_module_type" character varying, "p_creates_journal_entry" boolean) TO "authenticated";
GRANT ALL ON FUNCTION "public"."register_tms_module"("p_subsection_ref" character varying, "p_folder" character varying, "p_name" character varying, "p_sort_order" integer, "p_module_type" character varying, "p_creates_journal_entry" boolean) TO "service_role";



GRANT ALL ON FUNCTION "public"."set_updated_at"() TO "anon";
GRANT ALL ON FUNCTION "public"."set_updated_at"() TO "authenticated";
GRANT ALL ON FUNCTION "public"."set_updated_at"() TO "service_role";



GRANT ALL ON FUNCTION "public"."sys_rmp_set_ref"() TO "anon";
GRANT ALL ON FUNCTION "public"."sys_rmp_set_ref"() TO "authenticated";
GRANT ALL ON FUNCTION "public"."sys_rmp_set_ref"() TO "service_role";



GRANT ALL ON FUNCTION "public"."sys_roles_set_ref"() TO "anon";
GRANT ALL ON FUNCTION "public"."sys_roles_set_ref"() TO "authenticated";
GRANT ALL ON FUNCTION "public"."sys_roles_set_ref"() TO "service_role";



GRANT ALL ON FUNCTION "public"."sys_user_roles_set_ref"() TO "anon";
GRANT ALL ON FUNCTION "public"."sys_user_roles_set_ref"() TO "authenticated";
GRANT ALL ON FUNCTION "public"."sys_user_roles_set_ref"() TO "service_role";



GRANT ALL ON FUNCTION "public"."unregister_tms_module"("p_id" bigint) TO "anon";
GRANT ALL ON FUNCTION "public"."unregister_tms_module"("p_id" bigint) TO "authenticated";
GRANT ALL ON FUNCTION "public"."unregister_tms_module"("p_id" bigint) TO "service_role";


















GRANT ALL ON TABLE "public"."m_advance_payments" TO "anon";
GRANT ALL ON TABLE "public"."m_advance_payments" TO "authenticated";
GRANT ALL ON TABLE "public"."m_advance_payments" TO "service_role";



GRANT ALL ON SEQUENCE "public"."advance_payments_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."advance_payments_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."advance_payments_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_app_users_legacy" TO "anon";
GRANT ALL ON TABLE "public"."sys_app_users_legacy" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_app_users_legacy" TO "service_role";



GRANT ALL ON SEQUENCE "public"."app_users_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."app_users_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."app_users_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_cash_flow_entries" TO "anon";
GRANT ALL ON TABLE "public"."m_cash_flow_entries" TO "authenticated";
GRANT ALL ON TABLE "public"."m_cash_flow_entries" TO "service_role";



GRANT ALL ON SEQUENCE "public"."cash_flow_entries_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."cash_flow_entries_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."cash_flow_entries_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_cleaner_salary_settlements" TO "anon";
GRANT ALL ON TABLE "public"."m_cleaner_salary_settlements" TO "authenticated";
GRANT ALL ON TABLE "public"."m_cleaner_salary_settlements" TO "service_role";



GRANT ALL ON SEQUENCE "public"."cleaner_salary_settlements_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."cleaner_salary_settlements_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."cleaner_salary_settlements_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_cleaners" TO "anon";
GRANT ALL ON TABLE "public"."m_cleaners" TO "authenticated";
GRANT ALL ON TABLE "public"."m_cleaners" TO "service_role";



GRANT ALL ON SEQUENCE "public"."cleaners_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."cleaners_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."cleaners_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_company_info" TO "anon";
GRANT ALL ON TABLE "public"."sys_company_info" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_company_info" TO "service_role";



GRANT ALL ON SEQUENCE "public"."company_info_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."company_info_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."company_info_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_customers" TO "anon";
GRANT ALL ON TABLE "public"."m_customers" TO "authenticated";
GRANT ALL ON TABLE "public"."m_customers" TO "service_role";



GRANT ALL ON SEQUENCE "public"."customers_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."customers_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."customers_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_deductions" TO "anon";
GRANT ALL ON TABLE "public"."m_deductions" TO "authenticated";
GRANT ALL ON TABLE "public"."m_deductions" TO "service_role";



GRANT ALL ON SEQUENCE "public"."deductions_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."deductions_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."deductions_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_driver_cleaner_pairings" TO "anon";
GRANT ALL ON TABLE "public"."m_driver_cleaner_pairings" TO "authenticated";
GRANT ALL ON TABLE "public"."m_driver_cleaner_pairings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."driver_cleaner_pairings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."driver_cleaner_pairings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."driver_cleaner_pairings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_driver_salary_settlements" TO "anon";
GRANT ALL ON TABLE "public"."m_driver_salary_settlements" TO "authenticated";
GRANT ALL ON TABLE "public"."m_driver_salary_settlements" TO "service_role";



GRANT ALL ON SEQUENCE "public"."driver_salary_settlements_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."driver_salary_settlements_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."driver_salary_settlements_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_drivers" TO "anon";
GRANT ALL ON TABLE "public"."m_drivers" TO "authenticated";
GRANT ALL ON TABLE "public"."m_drivers" TO "service_role";



GRANT ALL ON SEQUENCE "public"."drivers_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."drivers_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."drivers_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_export_records" TO "anon";
GRANT ALL ON TABLE "public"."sys_export_records" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_export_records" TO "service_role";



GRANT ALL ON SEQUENCE "public"."export_records_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."export_records_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."export_records_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_fuel_expenses" TO "anon";
GRANT ALL ON TABLE "public"."m_fuel_expenses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_fuel_expenses" TO "service_role";



GRANT ALL ON SEQUENCE "public"."fuel_expenses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."fuel_expenses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."fuel_expenses_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_general_expense_types" TO "anon";
GRANT ALL ON TABLE "public"."m_general_expense_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_general_expense_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."general_expense_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."general_expense_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."general_expense_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_general_expenses" TO "anon";
GRANT ALL ON TABLE "public"."m_general_expenses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_general_expenses" TO "service_role";



GRANT ALL ON SEQUENCE "public"."general_expenses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."general_expenses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."general_expenses_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_items" TO "anon";
GRANT ALL ON TABLE "public"."m_items" TO "authenticated";
GRANT ALL ON TABLE "public"."m_items" TO "service_role";



GRANT ALL ON SEQUENCE "public"."items_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."items_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."items_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_loans" TO "anon";
GRANT ALL ON TABLE "public"."m_loans" TO "authenticated";
GRANT ALL ON TABLE "public"."m_loans" TO "service_role";



GRANT ALL ON SEQUENCE "public"."loans_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."loans_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."loans_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_locations" TO "anon";
GRANT ALL ON TABLE "public"."m_locations" TO "authenticated";
GRANT ALL ON TABLE "public"."m_locations" TO "service_role";



GRANT ALL ON SEQUENCE "public"."locations_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."locations_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."locations_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_accounts_payable" TO "anon";
GRANT ALL ON TABLE "public"."m_accounts_payable" TO "authenticated";
GRANT ALL ON TABLE "public"."m_accounts_payable" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_accounts_payable_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_accounts_payable_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_accounts_payable_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_accounts_receivable" TO "anon";
GRANT ALL ON TABLE "public"."m_accounts_receivable" TO "authenticated";
GRANT ALL ON TABLE "public"."m_accounts_receivable" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_accounts_receivable_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_accounts_receivable_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_accounts_receivable_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_alert_rules" TO "anon";
GRANT ALL ON TABLE "public"."m_alert_rules" TO "authenticated";
GRANT ALL ON TABLE "public"."m_alert_rules" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_alert_rules_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_alert_rules_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_alert_rules_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_approval_requests" TO "anon";
GRANT ALL ON TABLE "public"."m_approval_requests" TO "authenticated";
GRANT ALL ON TABLE "public"."m_approval_requests" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_approval_requests_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_approval_requests_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_approval_requests_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_approval_workflow" TO "anon";
GRANT ALL ON TABLE "public"."m_approval_workflow" TO "authenticated";
GRANT ALL ON TABLE "public"."m_approval_workflow" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_approval_workflow_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_approval_workflow_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_approval_workflow_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_bank_reconciliation" TO "anon";
GRANT ALL ON TABLE "public"."m_bank_reconciliation" TO "authenticated";
GRANT ALL ON TABLE "public"."m_bank_reconciliation" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_bank_reconciliation_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_bank_reconciliation_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_bank_reconciliation_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_branches" TO "anon";
GRANT ALL ON TABLE "public"."m_branches" TO "authenticated";
GRANT ALL ON TABLE "public"."m_branches" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_branches_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_branches_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_branches_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_budgets" TO "anon";
GRANT ALL ON TABLE "public"."m_budgets" TO "authenticated";
GRANT ALL ON TABLE "public"."m_budgets" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_budgets_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_budgets_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_budgets_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_cash_bank" TO "anon";
GRANT ALL ON TABLE "public"."m_cash_bank" TO "authenticated";
GRANT ALL ON TABLE "public"."m_cash_bank" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_cash_bank_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_cash_bank_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_cash_bank_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_chart_of_accounts" TO "anon";
GRANT ALL ON TABLE "public"."m_chart_of_accounts" TO "authenticated";
GRANT ALL ON TABLE "public"."m_chart_of_accounts" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_chart_of_accounts_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_chart_of_accounts_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_chart_of_accounts_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_cost_centers" TO "anon";
GRANT ALL ON TABLE "public"."m_cost_centers" TO "authenticated";
GRANT ALL ON TABLE "public"."m_cost_centers" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_cost_centers_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_cost_centers_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_cost_centers_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_currencies" TO "anon";
GRANT ALL ON TABLE "public"."m_currencies" TO "authenticated";
GRANT ALL ON TABLE "public"."m_currencies" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_currencies_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_currencies_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_currencies_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_customer_types" TO "anon";
GRANT ALL ON TABLE "public"."m_customer_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_customer_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_customer_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_customer_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_customer_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_departments" TO "anon";
GRANT ALL ON TABLE "public"."m_departments" TO "authenticated";
GRANT ALL ON TABLE "public"."m_departments" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_departments_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_departments_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_departments_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_designations" TO "anon";
GRANT ALL ON TABLE "public"."m_designations" TO "authenticated";
GRANT ALL ON TABLE "public"."m_designations" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_designations_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_designations_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_designations_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_employee_assignments" TO "anon";
GRANT ALL ON TABLE "public"."m_employee_assignments" TO "authenticated";
GRANT ALL ON TABLE "public"."m_employee_assignments" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_employee_assignments_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_employee_assignments_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_employee_assignments_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_employee_salary_structures" TO "anon";
GRANT ALL ON TABLE "public"."m_employee_salary_structures" TO "authenticated";
GRANT ALL ON TABLE "public"."m_employee_salary_structures" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_employee_salary_structures_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_employee_salary_structures_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_employee_salary_structures_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_employees" TO "anon";
GRANT ALL ON TABLE "public"."m_employees" TO "authenticated";
GRANT ALL ON TABLE "public"."m_employees" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_employees_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_employees_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_employees_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_employment_records" TO "anon";
GRANT ALL ON TABLE "public"."m_employment_records" TO "authenticated";
GRANT ALL ON TABLE "public"."m_employment_records" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_employment_records_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_employment_records_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_employment_records_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_employment_types" TO "anon";
GRANT ALL ON TABLE "public"."m_employment_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_employment_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_employment_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_employment_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_employment_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_finance_audit_trail" TO "anon";
GRANT ALL ON TABLE "public"."m_finance_audit_trail" TO "authenticated";
GRANT ALL ON TABLE "public"."m_finance_audit_trail" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_finance_audit_trail_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_finance_audit_trail_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_finance_audit_trail_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_financial_settings" TO "anon";
GRANT ALL ON TABLE "public"."m_financial_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."m_financial_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_financial_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_financial_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_financial_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_fixed_assets" TO "anon";
GRANT ALL ON TABLE "public"."m_fixed_assets" TO "authenticated";
GRANT ALL ON TABLE "public"."m_fixed_assets" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_fixed_assets_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_fixed_assets_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_fixed_assets_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_fleet_statuses" TO "anon";
GRANT ALL ON TABLE "public"."m_fleet_statuses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_fleet_statuses" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_fleet_statuses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_fleet_statuses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_fleet_statuses_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_fuel_types" TO "anon";
GRANT ALL ON TABLE "public"."m_fuel_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_fuel_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_fuel_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_fuel_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_fuel_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_general_ledger" TO "anon";
GRANT ALL ON TABLE "public"."m_general_ledger" TO "authenticated";
GRANT ALL ON TABLE "public"."m_general_ledger" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_general_ledger_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_general_ledger_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_general_ledger_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_gps_devices" TO "anon";
GRANT ALL ON TABLE "public"."m_gps_devices" TO "authenticated";
GRANT ALL ON TABLE "public"."m_gps_devices" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_gps_devices_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_gps_devices_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_gps_devices_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_journal_entries" TO "anon";
GRANT ALL ON TABLE "public"."m_journal_entries" TO "authenticated";
GRANT ALL ON TABLE "public"."m_journal_entries" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_journal_entries_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_journal_entries_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_journal_entries_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_journal_entry_lines" TO "anon";
GRANT ALL ON TABLE "public"."m_journal_entry_lines" TO "authenticated";
GRANT ALL ON TABLE "public"."m_journal_entry_lines" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_journal_entry_lines_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_journal_entry_lines_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_journal_entry_lines_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_maintenance_types" TO "anon";
GRANT ALL ON TABLE "public"."m_maintenance_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_maintenance_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_maintenance_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_maintenance_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_maintenance_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_payments" TO "anon";
GRANT ALL ON TABLE "public"."m_payments" TO "authenticated";
GRANT ALL ON TABLE "public"."m_payments" TO "service_role";



GRANT ALL ON TABLE "public"."m_payroll_inputs" TO "anon";
GRANT ALL ON TABLE "public"."m_payroll_inputs" TO "authenticated";
GRANT ALL ON TABLE "public"."m_payroll_inputs" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_payroll_inputs_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_payroll_inputs_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_payroll_inputs_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_payroll_periods" TO "anon";
GRANT ALL ON TABLE "public"."m_payroll_periods" TO "authenticated";
GRANT ALL ON TABLE "public"."m_payroll_periods" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_payroll_periods_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_payroll_periods_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_payroll_periods_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_payroll_profiles" TO "anon";
GRANT ALL ON TABLE "public"."m_payroll_profiles" TO "authenticated";
GRANT ALL ON TABLE "public"."m_payroll_profiles" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_payroll_profiles_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_payroll_profiles_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_payroll_profiles_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_payroll_runs" TO "anon";
GRANT ALL ON TABLE "public"."m_payroll_runs" TO "authenticated";
GRANT ALL ON TABLE "public"."m_payroll_runs" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_payroll_runs_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_payroll_runs_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_payroll_runs_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_period_closing" TO "anon";
GRANT ALL ON TABLE "public"."m_period_closing" TO "authenticated";
GRANT ALL ON TABLE "public"."m_period_closing" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_period_closing_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_period_closing_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_period_closing_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_posting_rules" TO "anon";
GRANT ALL ON TABLE "public"."m_posting_rules" TO "authenticated";
GRANT ALL ON TABLE "public"."m_posting_rules" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_posting_rules_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_posting_rules_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_posting_rules_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_quotation_resources" TO "anon";
GRANT ALL ON TABLE "public"."t_quotation_resources" TO "authenticated";
GRANT ALL ON TABLE "public"."t_quotation_resources" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_quotation_resources_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_quotation_resources_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_quotation_resources_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_quotation_statuses" TO "anon";
GRANT ALL ON TABLE "public"."m_quotation_statuses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_quotation_statuses" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_quotation_statuses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_quotation_statuses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_quotation_statuses_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_quotations" TO "anon";
GRANT ALL ON TABLE "public"."m_quotations" TO "authenticated";
GRANT ALL ON TABLE "public"."m_quotations" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_quotations_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_quotations_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_quotations_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_salary_assignment_details" TO "anon";
GRANT ALL ON TABLE "public"."m_salary_assignment_details" TO "authenticated";
GRANT ALL ON TABLE "public"."m_salary_assignment_details" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_salary_assignment_details_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_salary_assignment_details_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_salary_assignment_details_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_salary_assignments" TO "anon";
GRANT ALL ON TABLE "public"."m_salary_assignments" TO "authenticated";
GRANT ALL ON TABLE "public"."m_salary_assignments" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_salary_assignments_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_salary_assignments_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_salary_assignments_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_salary_components" TO "anon";
GRANT ALL ON TABLE "public"."m_salary_components" TO "authenticated";
GRANT ALL ON TABLE "public"."m_salary_components" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_salary_components_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_salary_components_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_salary_components_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_tax_management" TO "anon";
GRANT ALL ON TABLE "public"."m_tax_management" TO "authenticated";
GRANT ALL ON TABLE "public"."m_tax_management" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_tax_management_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_tax_management_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_tax_management_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_trip_expenses" TO "anon";
GRANT ALL ON TABLE "public"."m_trip_expenses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_trip_expenses" TO "service_role";



GRANT ALL ON TABLE "public"."m_trips" TO "anon";
GRANT ALL ON TABLE "public"."m_trips" TO "authenticated";
GRANT ALL ON TABLE "public"."m_trips" TO "service_role";



GRANT ALL ON TABLE "public"."m_user_preferences" TO "anon";
GRANT ALL ON TABLE "public"."m_user_preferences" TO "authenticated";
GRANT ALL ON TABLE "public"."m_user_preferences" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_user_preferences_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_user_preferences_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_user_preferences_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicle_brands" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicle_brands" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicle_brands" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_brands_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_brands_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_brands_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicle_document_types" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicle_document_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicle_document_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_document_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_document_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_document_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_vehicle_documents" TO "anon";
GRANT ALL ON TABLE "public"."t_vehicle_documents" TO "authenticated";
GRANT ALL ON TABLE "public"."t_vehicle_documents" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_documents_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_documents_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_documents_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicle_expenses" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicle_expenses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicle_expenses" TO "service_role";



GRANT ALL ON TABLE "public"."t_vehicle_gps" TO "anon";
GRANT ALL ON TABLE "public"."t_vehicle_gps" TO "authenticated";
GRANT ALL ON TABLE "public"."t_vehicle_gps" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_gps_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_gps_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_gps_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_vehicle_maintenance" TO "anon";
GRANT ALL ON TABLE "public"."t_vehicle_maintenance" TO "authenticated";
GRANT ALL ON TABLE "public"."t_vehicle_maintenance" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_maintenance_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_maintenance_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_maintenance_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicle_models" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicle_models" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicle_models" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_models_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_models_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_models_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_vehicle_odometer" TO "anon";
GRANT ALL ON TABLE "public"."t_vehicle_odometer" TO "authenticated";
GRANT ALL ON TABLE "public"."t_vehicle_odometer" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_odometer_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_odometer_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_odometer_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicle_types" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicle_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicle_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_vehicle_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_vehicle_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_vehicle_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_vehicles" TO "anon";
GRANT ALL ON TABLE "public"."m_vehicles" TO "authenticated";
GRANT ALL ON TABLE "public"."m_vehicles" TO "service_role";



GRANT ALL ON TABLE "public"."m_work_order_statuses" TO "anon";
GRANT ALL ON TABLE "public"."m_work_order_statuses" TO "authenticated";
GRANT ALL ON TABLE "public"."m_work_order_statuses" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_work_order_statuses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_work_order_statuses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_work_order_statuses_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_work_order_types" TO "anon";
GRANT ALL ON TABLE "public"."m_work_order_types" TO "authenticated";
GRANT ALL ON TABLE "public"."m_work_order_types" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_work_order_types_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_work_order_types_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_work_order_types_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."m_work_orders" TO "anon";
GRANT ALL ON TABLE "public"."m_work_orders" TO "authenticated";
GRANT ALL ON TABLE "public"."m_work_orders" TO "service_role";



GRANT ALL ON SEQUENCE "public"."m_work_orders_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."m_work_orders_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."m_work_orders_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."payments_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."payments_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."payments_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_backup_settings" TO "anon";
GRANT ALL ON TABLE "public"."sys_backup_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_backup_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_backup_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_backup_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_backup_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_company_settings" TO "anon";
GRANT ALL ON TABLE "public"."sys_company_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_company_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_company_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_company_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_company_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_developer_settings" TO "anon";
GRANT ALL ON TABLE "public"."sys_developer_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_developer_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_developer_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_developer_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_developer_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_email_sms_settings" TO "anon";
GRANT ALL ON TABLE "public"."sys_email_sms_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_email_sms_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_email_sms_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_email_sms_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_email_sms_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_integrations" TO "anon";
GRANT ALL ON TABLE "public"."sys_integrations" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_integrations" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_integrations_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_integrations_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_integrations_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_master_data_settings" TO "anon";
GRANT ALL ON TABLE "public"."sys_master_data_settings" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_master_data_settings" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_master_data_settings_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_master_data_settings_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_master_data_settings_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_notifications" TO "anon";
GRANT ALL ON TABLE "public"."sys_notifications" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_notifications" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_notifications_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_notifications_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_notifications_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_rmp_ref_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_rmp_ref_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_rmp_ref_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_role_module_permissions" TO "anon";
GRANT ALL ON TABLE "public"."sys_role_module_permissions" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_role_module_permissions" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_role_module_permissions_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_role_module_permissions_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_role_module_permissions_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_roles" TO "anon";
GRANT ALL ON TABLE "public"."sys_roles" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_roles" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_roles_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_roles_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_roles_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_roles_ref_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_roles_ref_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_roles_ref_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_system_preferences" TO "anon";
GRANT ALL ON TABLE "public"."sys_system_preferences" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_system_preferences" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_system_preferences_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_system_preferences_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_system_preferences_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_tms_module_documentation" TO "anon";
GRANT ALL ON TABLE "public"."sys_tms_module_documentation" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_tms_module_documentation" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_tms_module_documentation_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_tms_module_documentation_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_tms_module_documentation_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_tms_module_number_series" TO "anon";
GRANT ALL ON TABLE "public"."sys_tms_module_number_series" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_tms_module_number_series" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_tms_module_number_series_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_tms_module_number_series_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_tms_module_number_series_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_tms_modules" TO "anon";
GRANT ALL ON TABLE "public"."sys_tms_modules" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_tms_modules" TO "service_role";



GRANT ALL ON TABLE "public"."sys_tms_sections" TO "anon";
GRANT ALL ON TABLE "public"."sys_tms_sections" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_tms_sections" TO "service_role";



GRANT ALL ON TABLE "public"."sys_tms_subsections" TO "anon";
GRANT ALL ON TABLE "public"."sys_tms_subsections" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_tms_subsections" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_tms_subsections_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_tms_subsections_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_tms_subsections_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_user_activity_log" TO "anon";
GRANT ALL ON TABLE "public"."sys_user_activity_log" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_user_activity_log" TO "service_role";



GRANT ALL ON TABLE "public"."sys_user_permissions_legacy" TO "anon";
GRANT ALL ON TABLE "public"."sys_user_permissions_legacy" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_user_permissions_legacy" TO "service_role";



GRANT ALL ON TABLE "public"."sys_user_roles" TO "anon";
GRANT ALL ON TABLE "public"."sys_user_roles" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_user_roles" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_user_roles_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_user_roles_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_user_roles_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_user_roles_ref_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_user_roles_ref_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_user_roles_ref_seq" TO "service_role";



GRANT ALL ON TABLE "public"."sys_users" TO "anon";
GRANT ALL ON TABLE "public"."sys_users" TO "authenticated";
GRANT ALL ON TABLE "public"."sys_users" TO "service_role";



GRANT ALL ON SEQUENCE "public"."sys_users_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."sys_users_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."sys_users_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_employee_payroll_lines" TO "anon";
GRANT ALL ON TABLE "public"."t_employee_payroll_lines" TO "authenticated";
GRANT ALL ON TABLE "public"."t_employee_payroll_lines" TO "service_role";



GRANT ALL ON SEQUENCE "public"."t_employee_payroll_lines_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."t_employee_payroll_lines_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."t_employee_payroll_lines_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_employee_payrolls" TO "anon";
GRANT ALL ON TABLE "public"."t_employee_payrolls" TO "authenticated";
GRANT ALL ON TABLE "public"."t_employee_payrolls" TO "service_role";



GRANT ALL ON SEQUENCE "public"."t_employee_payrolls_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."t_employee_payrolls_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."t_employee_payrolls_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_quotation_lines" TO "anon";
GRANT ALL ON TABLE "public"."t_quotation_lines" TO "authenticated";
GRANT ALL ON TABLE "public"."t_quotation_lines" TO "service_role";



GRANT ALL ON SEQUENCE "public"."t_quotation_lines_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."t_quotation_lines_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."t_quotation_lines_id_seq" TO "service_role";



GRANT ALL ON TABLE "public"."t_work_order_lines" TO "anon";
GRANT ALL ON TABLE "public"."t_work_order_lines" TO "authenticated";
GRANT ALL ON TABLE "public"."t_work_order_lines" TO "service_role";



GRANT ALL ON SEQUENCE "public"."t_work_order_lines_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."t_work_order_lines_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."t_work_order_lines_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."tms_modules_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."tms_modules_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."tms_modules_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."trip_expenses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."trip_expenses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."trip_expenses_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."trips_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."trips_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."trips_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."user_activity_log_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."user_activity_log_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."user_activity_log_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."user_permissions_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."user_permissions_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."user_permissions_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."vehicle_expenses_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."vehicle_expenses_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."vehicle_expenses_id_seq" TO "service_role";



GRANT ALL ON SEQUENCE "public"."vehicles_id_seq" TO "anon";
GRANT ALL ON SEQUENCE "public"."vehicles_id_seq" TO "authenticated";
GRANT ALL ON SEQUENCE "public"."vehicles_id_seq" TO "service_role";









ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON SEQUENCES TO "postgres";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON SEQUENCES TO "anon";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON SEQUENCES TO "authenticated";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON SEQUENCES TO "service_role";






ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON FUNCTIONS TO "postgres";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON FUNCTIONS TO "anon";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON FUNCTIONS TO "authenticated";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON FUNCTIONS TO "service_role";






ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON TABLES TO "postgres";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON TABLES TO "anon";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON TABLES TO "authenticated";
ALTER DEFAULT PRIVILEGES FOR ROLE "postgres" IN SCHEMA "public" GRANT ALL ON TABLES TO "service_role";































