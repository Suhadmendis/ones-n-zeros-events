-- ============================================================
-- Settings Module Migration
-- Run in Supabase SQL Editor
-- ============================================================

-- 1. sys_tms_modules — register all 9 new Settings modules
-- ============================================================

INSERT INTO sys_tms_modules (id, section, module, system_name, reference, flag, reference_mapping, web_status, section_name, folder_path) VALUES
(101, 'Settings', 'Company Settings',     'company_settings',     'COS', 0, NULL,                    1, 'settings', 'modules/settings/company_settings'),
(102, 'Settings', 'Number Series',        'number_series',        'NSR', 1, 'number_series_ref_seq', 1, 'settings', 'modules/settings/number_series'),
(103, 'Settings', 'Notifications',        'notifications',        'NTF', 0, NULL,                    1, 'settings', 'modules/settings/notifications'),
(104, 'Settings', 'Email & SMS',          'email_sms',            'EMS', 0, NULL,                    1, 'settings', 'modules/settings/email_sms'),
(105, 'Settings', 'Integrations',         'integrations',         'ITG', 0, NULL,                    1, 'settings', 'modules/settings/integrations'),
(106, 'Settings', 'Backup & Restore',     'backup_restore',       'BKP', 0, NULL,                    1, 'settings', 'modules/settings/backup_restore'),
(107, 'Settings', 'System Preferences',   'system_preferences',   'SPF', 0, NULL,                    1, 'settings', 'modules/settings/system_preferences'),
(108, 'Settings', 'Master Data Settings', 'master_data_settings', 'MDS', 0, NULL,                    1, 'settings', 'modules/settings/master_data_settings'),
(109, 'Settings', 'Developer Settings',   'developer_settings',   'DVS', 0, NULL,                    1, 'settings', 'modules/settings/developer_settings');


-- 2. sys_company_info — add number_series_ref_seq column
-- ============================================================

ALTER TABLE sys_company_info ADD COLUMN IF NOT EXISTS number_series_ref_seq integer DEFAULT 0;


-- 3. sys_number_series — Number Series master table
-- ============================================================

CREATE TABLE IF NOT EXISTS sys_number_series (
    id          bigserial PRIMARY KEY,
    ref         text        UNIQUE,
    module_name text        NOT NULL,
    prefix      text        NOT NULL DEFAULT '',
    suffix      text        NOT NULL DEFAULT '',
    padding     integer     NOT NULL DEFAULT 4,
    next_number integer     NOT NULL DEFAULT 1,
    is_active   boolean     NOT NULL DEFAULT true,
    created_by  text,
    updated_by  text,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now()
);


-- 4. Settings key/value tables (shared schema: same as m_financial_settings)
-- ============================================================

CREATE TABLE IF NOT EXISTS sys_company_settings (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'General',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_notifications (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'boolean',
    setting_group text        NOT NULL DEFAULT 'Email Alerts',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_email_sms_settings (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'Email (SMTP)',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_integrations (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'API Access',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_backup_settings (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'Backup Schedule',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_system_preferences (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'Display',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_master_data_settings (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'text',
    setting_group text        NOT NULL DEFAULT 'Auto-Numbering',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT false,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS sys_developer_settings (
    id            bigserial   PRIMARY KEY,
    setting_key   text        UNIQUE NOT NULL,
    setting_label text        NOT NULL,
    setting_value text        NOT NULL DEFAULT '',
    setting_type  text        NOT NULL DEFAULT 'boolean',
    setting_group text        NOT NULL DEFAULT 'Debug',
    options       text,
    description   text,
    is_system     boolean     NOT NULL DEFAULT true,
    updated_by    text,
    updated_at    timestamptz NOT NULL DEFAULT now()
);


-- 5. Seed data
-- ============================================================

-- Company Settings
INSERT INTO sys_company_settings (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('company_name',      'Company Name',         '',           'text',   'General',    'Legal name of the company'),
('company_address',   'Address',              '',           'text',   'General',    'Registered address'),
('company_phone',     'Phone',                '',           'text',   'General',    'Primary contact number'),
('company_email',     'Email',                '',           'text',   'General',    'Primary contact email'),
('timezone',          'Timezone',             'Asia/Colombo','text',  'Regional',   'System timezone'),
('date_format',       'Date Format',          'DD/MM/YYYY', 'select', 'Regional',   'How dates are displayed'),
('fiscal_year_month', 'Fiscal Year Start',    '1',          'select', 'Fiscal',     'Month the fiscal year begins'),
('currency_symbol',   'Currency Symbol',      'Rs.',        'text',   'Fiscal',     'Symbol shown in amounts'),
('currency_code',     'Currency Code',        'LKR',        'text',   'Fiscal',     'ISO 4217 currency code')
ON CONFLICT (setting_key) DO NOTHING;

-- Notifications
INSERT INTO sys_notifications (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('email_on_new_trip',      'Email on New Trip',        'false', 'boolean', 'Email Alerts', 'Send email when a new trip is created'),
('email_on_trip_complete', 'Email on Trip Completed',  'false', 'boolean', 'Email Alerts', 'Send email when a trip is marked complete'),
('email_on_payment',       'Email on Payment',         'false', 'boolean', 'Email Alerts', 'Send email when a payment is recorded'),
('email_on_low_fuel',      'Email on Low Fuel Alert',  'false', 'boolean', 'Email Alerts', 'Alert when fuel stock drops below threshold'),
('sms_on_new_trip',        'SMS on New Trip',          'false', 'boolean', 'SMS Alerts',   'Send SMS when a new trip is created'),
('sms_on_payment',         'SMS on Payment',           'false', 'boolean', 'SMS Alerts',   'Send SMS when a payment is recorded'),
('in_app_on_low_fuel',     'In-App Low Fuel Alert',    'true',  'boolean', 'In-App Alerts','Show in-app alert on low fuel'),
('in_app_on_pending_approval','In-App Pending Approval','true', 'boolean', 'In-App Alerts','Show alert when approval is needed')
ON CONFLICT (setting_key) DO NOTHING;

-- Email & SMS
INSERT INTO sys_email_sms_settings (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('smtp_host',       'SMTP Host',        '',       'text',     'Email (SMTP)', 'Mail server hostname'),
('smtp_port',       'SMTP Port',        '587',    'number',   'Email (SMTP)', 'Mail server port'),
('smtp_user',       'SMTP Username',    '',       'text',     'Email (SMTP)', 'Authentication username'),
('smtp_pass',       'SMTP Password',    '',       'password', 'Email (SMTP)', 'Authentication password'),
('smtp_from',       'From Address',     '',       'text',     'Email (SMTP)', 'Sender email address'),
('smtp_from_name',  'From Name',        '',       'text',     'Email (SMTP)', 'Sender display name'),
('smtp_encryption', 'Encryption',       'tls',    'select',   'Email (SMTP)', 'Connection encryption type'),
('sms_provider',    'SMS Provider',     'twilio', 'select',   'SMS Gateway',  'SMS gateway service'),
('sms_api_key',     'SMS API Key',      '',       'password', 'SMS Gateway',  'API key for SMS gateway'),
('sms_sender_id',   'Sender ID',        '',       'text',     'SMS Gateway',  'Alphanumeric sender ID')
ON CONFLICT (setting_key) DO NOTHING;

-- Integrations
INSERT INTO sys_integrations (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('api_enabled',      'API Access Enabled',  'false', 'boolean', 'API Access',   'Enable external API access'),
('api_key',          'API Key',             '',      'text',     'API Access',   'Generated API key for external access'),
('webhook_url',      'Webhook URL',         '',      'text',     'Webhooks',     'URL to POST event notifications to'),
('webhook_secret',   'Webhook Secret',      '',      'password', 'Webhooks',     'HMAC secret for webhook signature'),
('google_maps_key',  'Google Maps API Key', '',      'text',     'Third-Party',  'API key for Google Maps'),
('payment_gateway',  'Payment Gateway',     '',      'text',     'Third-Party',  'Payment gateway identifier')
ON CONFLICT (setting_key) DO NOTHING;

-- Backup & Restore
INSERT INTO sys_backup_settings (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('auto_backup_enabled',    'Auto Backup Enabled',  'false',   'boolean', 'Backup Schedule', 'Enable scheduled automatic backups'),
('backup_frequency',       'Backup Frequency',     'weekly',  'select',  'Backup Schedule', 'How often automatic backups run'),
('backup_retention_days',  'Retention Period',     '30',      'number',  'Backup Schedule', 'Days to keep backup files'),
('backup_storage_path',    'Storage Path',         '/backups','text',    'Storage',         'Filesystem path for backup files'),
('last_backup_at',         'Last Backup',          '',        'text',    'Storage',         'Timestamp of the last completed backup')
ON CONFLICT (setting_key) DO NOTHING;

-- System Preferences
INSERT INTO sys_system_preferences (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('theme',                'Theme',               'light',      'select', 'Display',  'UI color theme'),
('sidebar_collapsed',    'Sidebar Collapsed',   'false',      'boolean','Display',  'Start with sidebar collapsed by default'),
('rows_per_page',        'Rows Per Page',       '25',         'select', 'Display',  'Default table page size'),
('date_display_format',  'Date Display Format', 'DD/MM/YYYY', 'select', 'Display',  'How dates appear in tables and forms'),
('language',             'Language',            'en',         'select', 'Locale',   'Interface language'),
('first_day_of_week',    'First Day of Week',   '1',          'select', 'Locale',   'First day shown in calendar/date pickers')
ON CONFLICT (setting_key) DO NOTHING;

-- Master Data Settings
INSERT INTO sys_master_data_settings (setting_key, setting_label, setting_value, setting_type, setting_group, description) VALUES
('auto_ref_enabled',      'Auto Reference Numbers', 'true',     'boolean', 'Auto-Numbering', 'Automatically generate reference numbers'),
('ref_prefix_driver',     'Driver Ref Prefix',      'DR',       'text',    'Auto-Numbering', 'Prefix used for driver reference numbers'),
('ref_prefix_vehicle',    'Vehicle Ref Prefix',     'VH',       'text',    'Auto-Numbering', 'Prefix used for vehicle reference numbers'),
('ref_prefix_cleaner',    'Cleaner Ref Prefix',     'CL',       'text',    'Auto-Numbering', 'Prefix used for cleaner reference numbers'),
('default_driver_status', 'Default Driver Status',  'active',   'select',  'Defaults',       'Status assigned to newly created drivers'),
('default_vehicle_status','Default Vehicle Status', 'active',   'select',  'Defaults',       'Status assigned to newly created vehicles'),
('default_cleaner_status','Default Cleaner Status', 'active',   'select',  'Defaults',       'Status assigned to newly created cleaners'),
('require_license_no',    'Require License No.',    'false',    'boolean', 'Validation',     'Enforce license number on driver creation')
ON CONFLICT (setting_key) DO NOTHING;

-- Developer Settings
INSERT INTO sys_developer_settings (setting_key, setting_label, setting_value, setting_type, setting_group, description, is_system) VALUES
('debug_mode',        'Debug Mode',          'false', 'boolean', 'Debug',   'Enable verbose error output',                    true),
('log_queries',       'Log DB Queries',      'false', 'boolean', 'Logging', 'Log all Supabase queries to activity log',       true),
('log_level',         'Log Level',           'error', 'select',  'Logging', 'Minimum severity to log',                       true),
('show_sql_errors',   'Show SQL Errors',     'false', 'boolean', 'Debug',   'Display raw database errors in the UI',          true),
('api_rate_limit',    'API Rate Limit',      '60',    'select',  'API',     'Max API requests per minute per user',           true),
('maintenance_mode',  'Maintenance Mode',    'false', 'boolean', 'Debug',   'Redirect all users to a maintenance page',       true)
ON CONFLICT (setting_key) DO NOTHING;
