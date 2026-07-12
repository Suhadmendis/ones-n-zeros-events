-- Trial balance RPC — missing from live DB (present in database/backup.sql
-- but never migrated). Restores public.get_trial_balance(p_date_from,
-- p_date_to, p_basis) used by modules/finance/accounting/trial_balance/trial_balance_report.php.

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

GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "anon";
GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "authenticated";
GRANT ALL ON FUNCTION "public"."get_trial_balance"("p_date_from" "date", "p_date_to" "date", "p_basis" "text") TO "service_role";
