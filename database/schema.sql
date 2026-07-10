-- Ones n Zeros ERP — Database Schema
-- Supabase Project: meidzuvuiszwajpnnqwy
-- Generated: 2026-06-11

-- ============================================================
-- ENUM TYPES
-- ============================================================

CREATE TYPE public.advance_payment_recipient_type AS ENUM ('driver', 'cleaner');
CREATE TYPE public.cleaner_status AS ENUM ('active', 'inactive');
CREATE TYPE public.driver_status AS ENUM ('active', 'inactive', 'on_leave');
CREATE TYPE public.fuel_type AS ENUM ('petrol', 'diesel');
CREATE TYPE public.general_expense_type_status AS ENUM ('active', 'inactive');
CREATE TYPE public.item_category AS ENUM ('fuel', 'equipment', 'materials', 'cargo', 'other');
CREATE TYPE public.item_status AS ENUM ('active', 'inactive');
CREATE TYPE public.location_status AS ENUM ('active', 'inactive');
CREATE TYPE public.user_gender AS ENUM ('male', 'female');
CREATE TYPE public.user_role AS ENUM ('admin', 'manager', 'operator', 'viewer');
CREATE TYPE public.user_status AS ENUM ('active', 'inactive');
CREATE TYPE public.vehicle_expense_category AS ENUM ('repair', 'maintenance', 'body_work', 'tyres');
CREATE TYPE public.vehicle_status AS ENUM ('active', 'inactive');
CREATE TYPE public.vehicle_type AS ENUM ('lorry', 'bowser', 'tipper', 'truck', 'van', 'bus');

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE public.app_users (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    first_name  VARCHAR(100) NOT NULL,
    last_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    gender      public.user_gender NOT NULL,
    role        public.user_role NOT NULL DEFAULT 'viewer',
    status      public.user_status NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE public.company_info (
    id                           SERIAL PRIMARY KEY,
    name                         VARCHAR(200) NOT NULL,
    address                      TEXT,
    phone                        VARCHAR(50),
    email                        VARCHAR(100),
    driver_ref_seq               INTEGER NOT NULL DEFAULT 0,
    vehicle_ref_seq              INTEGER NOT NULL DEFAULT 0,
    cleaner_ref_seq              INTEGER NOT NULL DEFAULT 0,
    item_ref_seq                 INTEGER NOT NULL DEFAULT 0,
    fuel_expense_ref_seq         INTEGER NOT NULL DEFAULT 0,
    location_ref_seq             INTEGER NOT NULL DEFAULT 0,
    advance_payment_ref_seq      INTEGER NOT NULL DEFAULT 0,
    vehicle_expense_ref_seq      INTEGER NOT NULL DEFAULT 0,
    general_expense_type_ref_seq INTEGER NOT NULL DEFAULT 0,
    general_expense_ref_seq      INTEGER NOT NULL DEFAULT 0,
    user_ref_seq                 INTEGER NOT NULL DEFAULT 0,
    trip_ref_seq                 INTEGER DEFAULT 0,
    customer_ref_seq             INTEGER NOT NULL DEFAULT 0,
    trip_expense_ref_seq         INTEGER NOT NULL DEFAULT 0,
    deduction_ref_seq            INTEGER NOT NULL DEFAULT 0,
    loan_ref_seq                 INTEGER NOT NULL DEFAULT 0,
    payment_ref_seq              INTEGER NOT NULL DEFAULT 0,
    driver_settlement_ref_seq    INTEGER NOT NULL DEFAULT 0,
    cleaner_settlement_ref_seq   INTEGER NOT NULL DEFAULT 0,
    cash_flow_ref_seq            INTEGER NOT NULL DEFAULT 0,
    pairing_ref_seq              INTEGER NOT NULL DEFAULT 0,
    updated_at                   TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE public.customers (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    phone       VARCHAR(50),
    email       VARCHAR(255),
    address     TEXT,
    status      VARCHAR(10) NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.drivers (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    name            VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    license_number  VARCHAR(50) NOT NULL,
    status          public.driver_status NOT NULL DEFAULT 'active',
    date_of_birth   DATE NOT NULL,
    joining_date    DATE NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.cleaners (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    name            VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    status          public.cleaner_status NOT NULL DEFAULT 'active',
    date_of_birth   DATE NOT NULL,
    joining_date    DATE NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.vehicles (
    id            SERIAL PRIMARY KEY,
    ref           VARCHAR(20) NOT NULL,
    plate_number  VARCHAR(20) NOT NULL,
    make          VARCHAR(50) NOT NULL,
    model         VARCHAR(50) NOT NULL,
    type          public.vehicle_type NOT NULL,
    fuel_type     public.fuel_type NOT NULL,
    status        public.vehicle_status NOT NULL DEFAULT 'active',
    last_location VARCHAR(100) NOT NULL DEFAULT 'Depot',
    mileage       INTEGER NOT NULL DEFAULT 0,
    year          SMALLINT NOT NULL,
    capacity      INTEGER NOT NULL DEFAULT 0,
    created_at    TIMESTAMPTZ DEFAULT now(),
    updated_at    TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.items (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    category    public.item_category NOT NULL,
    unit        VARCHAR(50) NOT NULL,
    description TEXT,
    status      public.item_status NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.locations (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    district    VARCHAR(100) NOT NULL,
    province    VARCHAR(100) NOT NULL,
    status      public.location_status NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.general_expense_types (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    status      public.general_expense_type_status NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.tms_modules (
    id                SERIAL PRIMARY KEY,
    section           VARCHAR(50) NOT NULL,
    module            VARCHAR(100) NOT NULL,
    system_name       VARCHAR(100) NOT NULL,
    reference         CHAR(3) NOT NULL,
    flag              SMALLINT NOT NULL DEFAULT 0,
    reference_mapping VARCHAR,
    web_status        INTEGER DEFAULT 0,
    app_status        INTEGER
);

CREATE TABLE public.trips (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    vehicle_id  INTEGER NOT NULL REFERENCES public.vehicles(id),
    date        DATE NOT NULL,
    opening_km  NUMERIC NOT NULL,
    closing_km  NUMERIC NOT NULL,
    mileage     NUMERIC NOT NULL,
    driver_id   INTEGER NOT NULL REFERENCES public.drivers(id),
    cleaner_id  INTEGER REFERENCES public.cleaners(id),
    item_id     INTEGER REFERENCES public.items(id),
    item_name   VARCHAR(100),
    run_no      VARCHAR(50),
    from_loc    VARCHAR(100) NOT NULL,
    to_loc      VARCHAR(100) NOT NULL,
    amount      NUMERIC NOT NULL,
    damount     NUMERIC NOT NULL,
    camount     NUMERIC NOT NULL,
    day_pay     NUMERIC,
    department  VARCHAR(100),
    remark      VARCHAR(255),
    user_id     INTEGER REFERENCES public.app_users(id),
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.trip_expenses (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    date        DATE NOT NULL,
    vehicle_id  INTEGER REFERENCES public.vehicles(id),
    trip_id     INTEGER REFERENCES public.trips(id),
    category    VARCHAR(50) NOT NULL DEFAULT 'other',
    amount      NUMERIC NOT NULL DEFAULT 0,
    remark      VARCHAR(255),
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.fuel_expenses (
    id             SERIAL PRIMARY KEY,
    ref            VARCHAR(20) NOT NULL,
    vehicle_id     INTEGER NOT NULL REFERENCES public.vehicles(id),
    date           DATE NOT NULL,
    liters         NUMERIC NOT NULL,
    rate           NUMERIC NOT NULL,
    total          NUMERIC NOT NULL,
    voucher_number VARCHAR(20),
    created_at     TIMESTAMPTZ DEFAULT now(),
    updated_at     TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.vehicle_expenses (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    vehicle_id  INTEGER NOT NULL REFERENCES public.vehicles(id),
    category    public.vehicle_expense_category NOT NULL,
    remark      VARCHAR(255) NOT NULL,
    amount      NUMERIC NOT NULL,
    date        DATE NOT NULL,
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.general_expenses (
    id               SERIAL PRIMARY KEY,
    ref              VARCHAR(20) NOT NULL,
    expense_type_id  INTEGER NOT NULL REFERENCES public.general_expense_types(id),
    remark           VARCHAR(255) NOT NULL,
    amount           NUMERIC NOT NULL,
    date             DATE NOT NULL,
    created_at       TIMESTAMPTZ DEFAULT now(),
    updated_at       TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.advance_payments (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    recipient_type  public.advance_payment_recipient_type NOT NULL,
    driver_id       INTEGER REFERENCES public.drivers(id),
    cleaner_id      INTEGER REFERENCES public.cleaners(id),
    date            DATE NOT NULL,
    amount          NUMERIC NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.deductions (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    recipient_type  VARCHAR(10) NOT NULL,
    driver_id       INTEGER REFERENCES public.drivers(id),
    cleaner_id      INTEGER REFERENCES public.cleaners(id),
    date            DATE NOT NULL,
    amount          NUMERIC NOT NULL,
    reason          VARCHAR(255) NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.loans (
    id                SERIAL PRIMARY KEY,
    ref               VARCHAR(20) NOT NULL,
    recipient_type    VARCHAR(10) NOT NULL,
    driver_id         INTEGER REFERENCES public.drivers(id),
    cleaner_id        INTEGER REFERENCES public.cleaners(id),
    date              DATE NOT NULL,
    principal_amount  NUMERIC NOT NULL,
    recovered_amount  NUMERIC NOT NULL DEFAULT 0,
    status            VARCHAR(10) NOT NULL DEFAULT 'active',
    created_at        TIMESTAMPTZ DEFAULT now(),
    updated_at        TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.payments (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    recipient_type  VARCHAR(10) NOT NULL,
    driver_id       INTEGER REFERENCES public.drivers(id),
    cleaner_id      INTEGER REFERENCES public.cleaners(id),
    date            DATE NOT NULL,
    amount          NUMERIC NOT NULL,
    payment_type    VARCHAR(50) NOT NULL DEFAULT 'salary',
    remark          VARCHAR(255),
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.driver_salary_settlements (
    id               SERIAL PRIMARY KEY,
    ref              VARCHAR(20) NOT NULL,
    driver_id        INTEGER NOT NULL REFERENCES public.drivers(id),
    month            VARCHAR(7) NOT NULL,
    gross_earnings   NUMERIC NOT NULL DEFAULT 0,
    advances         NUMERIC NOT NULL DEFAULT 0,
    deductions_total NUMERIC NOT NULL DEFAULT 0,
    loan_recovery    NUMERIC NOT NULL DEFAULT 0,
    net_payable      NUMERIC NOT NULL DEFAULT 0,
    status           VARCHAR(10) NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMPTZ DEFAULT now(),
    updated_at       TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.cleaner_salary_settlements (
    id              SERIAL PRIMARY KEY,
    ref             VARCHAR(20) NOT NULL,
    cleaner_id      INTEGER NOT NULL REFERENCES public.cleaners(id),
    month           VARCHAR(7) NOT NULL,
    gross_earnings  NUMERIC NOT NULL DEFAULT 0,
    advances        NUMERIC NOT NULL DEFAULT 0,
    net_payable     NUMERIC NOT NULL DEFAULT 0,
    status          VARCHAR(10) NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.cash_flow_entries (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    date        DATE NOT NULL,
    flow_type   VARCHAR(10) NOT NULL,
    category    VARCHAR(100) NOT NULL,
    amount      NUMERIC NOT NULL,
    description VARCHAR(255),
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.driver_cleaner_pairings (
    id          SERIAL PRIMARY KEY,
    ref         VARCHAR(20) NOT NULL,
    driver_id   INTEGER NOT NULL REFERENCES public.drivers(id),
    cleaner_id  INTEGER NOT NULL REFERENCES public.cleaners(id),
    start_date  DATE NOT NULL,
    end_date    DATE,
    status      VARCHAR(10) NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now(),
    created_by  TEXT REFERENCES public.app_users(ref),
    updated_by  TEXT REFERENCES public.app_users(ref)
);

CREATE TABLE public.user_permissions (
    id         BIGINT NOT NULL PRIMARY KEY,
    user_ref   TEXT NOT NULL,
    module_id  INTEGER NOT NULL REFERENCES public.tms_modules(id)
);

-- ============================================================
-- TRIGGERS — auto-update updated_at on every row UPDATE
-- (created_at/updated_at are DB-managed; app code never sets them)
-- ============================================================

CREATE OR REPLACE FUNCTION public.set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.advance_payments FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.cash_flow_entries FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.cleaner_salary_settlements FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.cleaners FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.customers FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.deductions FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.driver_cleaner_pairings FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.driver_salary_settlements FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.drivers FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.fuel_expenses FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.general_expense_types FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.general_expenses FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.items FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.loans FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.locations FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.payments FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.trip_expenses FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.trips FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.vehicle_expenses FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
CREATE TRIGGER set_updated_at BEFORE UPDATE ON public.vehicles FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();

-- ============================================================
-- tms_modules SEED DATA
-- ============================================================

INSERT INTO public.tms_modules (id, section, module, system_name, reference, flag, reference_mapping, web_status, app_status) VALUES
(1,  'Company Management',  'Company profile',               'company_profile',             'COM', 0, NULL,                          1, NULL),
(2,  'Company Management',  'Create user',                   'create_user',                 'USR', 1, 'user_ref_seq',                1, NULL),
(3,  'Company Management',  'User permissions',              'user_permissions',            'PRM', 0, NULL,                          1, NULL),
(4,  'Company Management',  'Password change',               'password_change',             'PWD', 0, NULL,                          1, NULL),
(5,  'Company Management',  'Edit / deactivate user',        'edit_deactivate_user',        'EDU', 0, NULL,                          1, NULL),
(6,  'Master Files',        'Driver master file',            'driver_master_file',          'DRV', 1, 'driver_ref_seq',              1, NULL),
(7,  'Master Files',        'Vehicle master file',           'vehicle_master_file',         'VEH', 1, 'vehicle_ref_seq',             1, NULL),
(8,  'Master Files',        'Cleaner master file',           'cleaner_master_file',         'CLN', 1, 'cleaner_ref_seq',             1, NULL),
(9,  'Master Files',        'Customer master file',          'customer_master_file',        'CUS', 1, 'customer_ref_seq',            1, NULL),
(10, 'Master Files',        'Item master file',              'item_master_file',            'ITM', 1, 'item_ref_seq',                1, NULL),
(11, 'Operations',          'Trip / running chart',          'trip_running_chart',          'TRP', 1, 'trip_ref_seq',                1, NULL),
(12, 'Operations',          'Trip expense entry',            'trip_expense_entry',          'TEX', 1, 'trip_expense_ref_seq',        1, NULL),
(13, 'Operations',          'Fuel entry',                    'fuel_entry',                  'FUL', 1, 'fuel_expense_ref_seq',        1, NULL),
(14, 'Operations',          'Vehicle expense entry',         'vehicle_expense_entry',       'VEX', 1, 'vehicle_expense_ref_seq',     1, NULL),
(15, 'Operations',          'Driver advance',                'driver_advance',              'ADV', 1, 'advance_payment_ref_seq',     1, NULL),
(16, 'Operations',          'Deduction',                     'deduction',                   'DED', 1, 'deduction_ref_seq',           1, NULL),
(17, 'Operations',          'Loan',                          'loan',                        'LON', 1, 'loan_ref_seq',                1, NULL),
(18, 'Operations',          'Payment / salary disburse',     'payment_salary_disburse',     'PAY', 1, 'payment_ref_seq',             1, NULL),
(19, 'Operations',          'Driver salary settlement',      'driver_salary_settlement',    'DSS', 1, 'driver_settlement_ref_seq',   1, NULL),
(20, 'Operations',          'Cleaner salary settlement',     'cleaner_salary_settlement',   'CSS', 1, 'cleaner_settlement_ref_seq',  1, NULL),
(21, 'Operations',          'Cash flow',                     'cash_flow',                   'CSF', 1, 'cash_flow_ref_seq',           1, NULL),
(22, 'Operations',          'Driver-cleaner pairing',        'driver_cleaner_pairing',      'DCP', 1, 'pairing_ref_seq',             1, NULL),
(23, 'Reports',             'Monthly income summary',        'monthly_income_summary',      'MIS', 0, NULL,                          1, NULL),
(24, 'Reports',             'Vehicle income report',         'vehicle_income_report',       'VIR', 0, NULL,                          1, NULL),
(25, 'Reports',             'Driver income report',          'driver_income_report',        'DIR', 0, NULL,                          1, NULL),
(26, 'Reports',             'Vehicle profitability report',  'vehicle_profitability_report','VPR', 0, NULL,                          1, NULL),
(27, 'Reports',             'Trip expense report',           'trip_expense_report',         'TER', 0, NULL,                          1, NULL),
(28, 'Reports',             'Running chart (full ledger)',   'running_chart_full_ledger',   'RCL', 0, NULL,                          1, NULL),
(29, 'Reports',             'Lorry vs trips report',         'lorry_vs_trips_report',       'LVT', 0, NULL,                          1, NULL),
(30, 'Reports',             'Fuel usage report',             'fuel_usage_report',           'FUR', 0, NULL,                          1, NULL),
(31, 'Reports',             'All fuel list',                 'all_fuel_list',               'AFL', 0, NULL,                          1, NULL),
(32, 'Reports',             'Vehicle expenses report',       'vehicle_expenses_report',     'VER', 0, NULL,                          1, NULL),
(33, 'Reports',             'General expenses report',       'general_expenses_report',     'GER', 0, NULL,                          1, NULL),
(34, 'Reports',             'Driver salary summary',         'driver_salary_summary',       'DSR', 0, NULL,                          1, NULL),
(35, 'Reports',             'Driver salary list',            'driver_salary_list',          'DSL', 0, NULL,                          1, NULL),
(36, 'Reports',             'Cleaner salary list',           'cleaner_salary_list',         'CSL', 0, NULL,                          1, NULL),
(37, 'Reports',             'Cash flow print',               'cash_flow_print',             'CFP', 0, NULL,                          1, NULL),
(38, 'Reports',             'Business summary dashboard',    'business_summary_dashboard',  'BSD', 0, NULL,                          1, NULL),
(39, 'Master Files',        'Location master file',          'location_master_file',        'LOC', 1, 'location_ref_seq',            1, NULL),
(40, 'Master Files',        'General expense type',          'general_expense_type',        'GET', 1, 'general_expense_type_ref_seq',1, NULL),
(41, 'Operations',          'General expense entry',         'general_expense_entry',       'GEX', 1, 'general_expense_ref_seq',     1, NULL),
(42, 'Executive Reports',   'Business performance summary',  'business_performance_summary','BPS', 0, NULL,                          1, NULL),
(43, 'Executive Reports',   'Daily business snapshot',       'daily_business_snapshot',     'DBS', 0, NULL,                          1, NULL),
(44, 'Fleet Reports',       'Vehicle performance report',    'vehicle_performance',         'VPF', 0, NULL,                          1, NULL),
(45, 'Fleet Reports',       'Vehicle utilization report',    'vehicle_utilization',         'VUT', 0, NULL,                          1, NULL),
(46, 'Fleet Reports',       'Mileage report',                'mileage_report',              'MLR', 0, NULL,                          1, NULL),
(47, 'Fleet Reports',       'Fuel efficiency report',        'fuel_efficiency',             'FEF', 0, NULL,                          1, NULL),
(48, 'Fleet Reports',       'Fuel cost trend report',        'fuel_cost_trend',             'FCT', 0, NULL,                          1, NULL),
(49, 'Fleet Reports',       'Vehicle expense analysis',      'vehicle_expense_analysis',    'VEA', 0, NULL,                          1, NULL),
(50, 'Fleet Reports',       'Most expensive vehicle',        'most_expensive_vehicle',      'MEV', 0, NULL,                          1, NULL),
(51, 'Operations Reports',  'Trip revenue report',           'trip_revenue',                'TRV', 0, NULL,                          1, NULL),
(52, 'Operations Reports',  'Route profitability report',    'route_profitability',         'RPR', 0, NULL,                          1, NULL),
(53, 'Operations Reports',  'Trip frequency report',         'trip_frequency',              'TFQ', 0, NULL,                          1, NULL),
(54, 'Operations Reports',  'Department-wise revenue report','department_revenue',          'DPR', 0, NULL,                          1, NULL),
(55, 'Operations Reports',  'Item type revenue report',      'item_type_revenue',           'ITR', 0, NULL,                          1, NULL),
(56, 'Staff Reports',       'Driver performance report',     'driver_performance',          'DPF', 0, NULL,                          1, NULL),
(57, 'Staff Reports',       'Driver earnings report',        'driver_earnings',             'DER', 0, NULL,                          1, NULL),
(58, 'Staff Reports',       'Driver advance report',         'driver_advance_report',       'DAR', 0, NULL,                          1, NULL),
(59, 'Staff Reports',       'Driver loan report',            'driver_loan_report',          'DLR', 0, NULL,                          1, NULL),
(60, 'Staff Reports',       'Driver deduction report',       'driver_deduction_report',     'DDR', 0, NULL,                          1, NULL),
(61, 'Staff Reports',       'Driver salary sheet',           'driver_salary_sheet',         'DSH', 0, NULL,                          1, NULL),
(62, 'Staff Reports',       'Cleaner performance report',    'cleaner_performance',         'CPF', 0, NULL,                          1, NULL),
(63, 'Staff Reports',       'Cleaner salary report',         'cleaner_salary_report',       'CSR', 0, NULL,                          1, NULL),
(64, 'Staff Reports',       'Cleaner loan report',           'cleaner_loan_report',         'CLR', 0, NULL,                          1, NULL),
(65, 'Staff Reports',       'Cleaner advance report',        'cleaner_advance_report',      'CAR', 0, NULL,                          1, NULL),
(66, 'Financial Reports',   'Cash flow statement',           'cash_flow_statement',         'CFS', 0, NULL,                          1, NULL),
(67, 'Financial Reports',   'Income vs expense report',      'income_vs_expense',           'IVE', 0, NULL,                          1, NULL),
(68, 'Financial Reports',   'Expense summary report',        'expense_summary',             'EXS', 0, NULL,                          1, NULL),
(69, 'Financial Reports',   'Monthly profit report',         'monthly_profit',              'MNP', 0, NULL,                          1, NULL),
(70, 'Financial Reports',   'Revenue trend report',          'revenue_trend',               'RVT', 0, NULL,                          1, NULL),
(71, 'Financial Reports',   'Expense trend report',          'expense_trend',               'EXT', 0, NULL,                          1, NULL),
(72, 'Payroll Reports',     'My trips',                      'my_trips',                    'MYT', 0, NULL,                          1, NULL),
(73, 'Payroll Reports',     'My earnings',                   'my_earnings',                 'MYE', 0, NULL,                          1, NULL),
(74, 'Payroll Reports',     'My salary statement',           'my_salary_statement',         'MSS', 0, NULL,                          1, NULL),
(75, 'Payroll Reports',     'My advances',                   'my_advances',                 'MYA', 0, NULL,                          1, NULL),
(76, 'Payroll Reports',     'My loan status',                'my_loan_status',              'MLS', 0, NULL,                          1, NULL),
(77, 'Analytics Reports',   'Top performing vehicle',        'top_performing_vehicle',      'TPV', 0, NULL,                          1, NULL),
(78, 'Analytics Reports',   'Top performing driver',         'top_performing_driver',       'TPD', 0, NULL,                          1, NULL),
(79, 'Analytics Reports',   'Lowest fuel efficiency',        'lowest_fuel_efficiency',      'LFE', 0, NULL,                          1, NULL),
(80, 'Analytics Reports',   'Highest expense vehicle',       'highest_expense_vehicle',     'HEV', 0, NULL,                          1, NULL),
(81, 'Analytics Reports',   'Profit per KM report',          'profit_per_km',               'PPK', 0, NULL,                          1, NULL);
