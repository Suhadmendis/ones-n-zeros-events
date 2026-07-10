# Module Creation Guide

How to create a new ERP module. **Use the Generate Module tool — don't hand-write the 6 files.**
It registers the module, creates its business table(s), seeds a Number Series row, grants Admin
access, and writes a fully-wired file scaffold for the fields you define. It can be driven
entirely from the command line — no browser needed.

---

## 1. Module types

Set `module_type` in the generate payload (default `entry` if omitted):

- **`entry`** — a flat data-entry form: a Reference No. plus the fields you define, one
  business table (`m_*`). The original/simplest shape.
- **`header_detail`** — a document header plus an optional repeatable **line-items** table
  (e.g. a quotation or invoice), modeled on
  `modules/operations/work_orders/work_order_entries/`. Pass `line_fields` (same shape as
  `fields`) for the child table's columns.
  - **`line_fields: []` (empty) generates identically to `entry`** — no child table, no
    lines UI. Only pass line fields when the module actually needs a lines table.
  - When non-empty, a second table `t_{table_name_without_m_}_lines` is created with a
    `{folder}_ref` FK column back to the header, plus `line_no` and your fields — no
    `status` column, no number series of its own (line refs are derived as
    `{header_ref}-L{n}`), same convention as `t_work_order_lines`.
  - Update replaces the line set wholesale (delete all existing lines for the header ref,
    reinsert the current set) rather than diffing — same as `work_order_entries_data.php`.
- **`report`** — a date-range report screen: Date From/To, "Run Report", then
  `ReportUtils.exportExcel()` / `ReportUtils.printReport()` (global helpers already loaded
  via `partials/head.php`), modeled on
  `modules/reports/general_reports/fuel_usage_report/`. **Scaffold only** — no business
  table, no Reference No./number series, and the generated `_data.php`'s `action=run`
  handler is a `TODO` stub returning `[]`. You still have to write the real aggregation
  query by hand afterward (see `docs/report_concepts.md` and the fuel_usage_report example)
  — reports vary too much across tables/joins/groupings to template safely. Only 3 files
  are written (`{slug}.php`, `{slug}.js`, `{slug}_data.php`), and the Admin permission
  grant is view/print only (no create/edit — there's nothing to save on a report screen).

## 2. GL posting toggle (`entry` / `header_detail` only, not `report`)

Pass `creates_journal_entry: true` to make every save auto-post a balanced journal entry,
the same "Check GL" + Journal Entry Preview pattern used by hand-built modules like
`modules/operations/expenses/fuel_entry/` — but sourced dynamically from `m_posting_rules`
instead of hardcoded account codes, so accounts stay editable afterward from
**Settings → Posting Rules** without a code change:

- `gl_amount_field` — the column name of one of your `number`/`decimal` fields; its value
  drives the posted amount.
- `gl_lines` — 2+ posting lines, `{entry_type: 'debit'|'credit', account_code,
  account_name?, description?}`, at least one debit and one credit, every line needs an
  `account_code`. Seeded into `m_posting_rules` (`module_system_name = <folder>`,
  `variant = null`) at generation time.
- At save time the generated code calls `jnl_create_from_posting_rules()`
  (`server/accounting/journal_engine.php`) — every configured line gets the full amount on
  its configured debit/credit side (a no-op if the amount is `<= 0`).
- For `header_detail`, the amount always comes from a **header** field — per-line GL
  splitting isn't supported (same spirit as the FK-picker limitation below).

## 3. Drive it from the CLI

Preferred (no dev server needed): `bin/generate_module.php` calls the exact same
`generateModule()` function the browser UI uses, in-process — no HTTP, no session cookie.

```sh
php bin/generate_module.php module.json
php bin/generate_module.php module.json --actor=USR-0000001   # attribute to a specific user
cat module.json | php bin/generate_module.php -               # read from stdin
```

Without `--actor`, it attributes `created_by`/`updated_by`/posting-rule rows to the first
active user holding the Admin role.

Example `module.json` for an `entry` module with GL posting:

```json
{
  "section_ref": "MSTR",
  "subsection_ref": "REFDAT",
  "module_name": "Vehicle Insurance Claims",
  "module_type": "entry",
  "table_name": "m_vehicle_insurance_claims",
  "ref_prefix": "VIC",
  "fields": [
    {"label": "Policy Number", "column": "policy_number", "type": "text", "required": true, "options": []},
    {"label": "Claim Amount",  "column": "claim_amount",  "type": "decimal", "required": false, "options": []},
    {"label": "Claim Date",    "column": "claim_date",    "type": "date", "required": false, "options": []},
    {"label": "Status",        "column": "claim_status",  "type": "dropdown", "required": false, "options": ["Open", "Approved", "Rejected"]}
  ],
  "creates_journal_entry": true,
  "gl_amount_field": "claim_amount",
  "gl_lines": [
    {"entry_type": "debit",  "account_code": "5300", "account_name": "Insurance Claims Expense"},
    {"entry_type": "credit", "account_code": "1100", "account_name": "Cash in Hand"}
  ]
}
```

A `header_detail` module adds `"module_type": "header_detail"` and a `line_fields` array
(same shape as `fields`); a `report` module only needs `section_ref`/`subsection_ref`/
`module_name`/`"module_type": "report"` — no `table_name`, `ref_prefix`, or `fields`.

Field rules (all module types):
- `fields[].type` — one of `text | textarea | number | decimal | date | dropdown | checkbox`.
- `fields[].column` — must match `^[a-z][a-z0-9_]{1,49}$`; not one of the reserved names
  `id, ref, status, created_at, updated_at, created_by, updated_by`.
- `fields[].options` — required (≥2) only when `type` is `dropdown`.
- `table_name` — must match `^m_[a-z][a-z0-9_]{2,58}$`; conventionally `m_` + the
  module-name slug. Check availability first with the HTTP endpoint's
  `?action=check_table&table=...` (below), or just let the CLI surface the conflict.
- `ref_prefix` — 2–6 letters/numbers (e.g. `VIC`), becomes reference numbers like
  `VIC-0000001`.

A successful run prints the module's ref/folder/table(s)/URL and exits 0; any failure at
any step (bad field, duplicate table, disk write error, posting-rule seed failure, ...)
rolls back everything already done — table(s), number series, permission grant, posting
rules, module registration — so a run either fully succeeds or leaves nothing behind. Exits
1 with the error on stderr.

### Verify

Schema files checked into the repo are stale — always check the live DB:

```sh
psql "$DATABASE_URL" -c "\d m_vehicle_insurance_claims"
psql "$DATABASE_URL" -c "SELECT ref, prefix FROM sys_tms_module_number_series WHERE module_ref = '<ref from response>';"
psql "$DATABASE_URL" -c "SELECT can_view, can_create, can_edit, can_print FROM sys_role_module_permissions WHERE module_ref = '<ref from response>';"
psql "$DATABASE_URL" -c "SELECT * FROM m_posting_rules WHERE module_system_name = '<folder>';"  # only if GL was enabled
```

Then exercise the generated module's own endpoint directly:

```sh
DATA="http://localhost:8000/modules/{section_folder}/{subsection_folder}/{slug}/{slug}_data.php"
curl -s -b "PHPSESSID=$SID" -X POST -H "Content-Type: application/json" "$DATA?action=save" --data '{"policy_number":"POL-1","claim_amount":500}'
curl -s -b "PHPSESSID=$SID" "$DATA?action=list"
```

(`php -S localhost:8000` needs to be running for this HTTP-level check — the CLI generation
step above doesn't. See `CLAUDE.md` for the dev server command.)

## 4. Driving the HTTP endpoint directly (alternative to the CLI)

The tool is also a PHP endpoint:
`modules/settings/modules_mgmt/module_generator/module_generator_data.php`, served by the
local dev server. It requires a logged-in session (`require_login()`), which normally means
a browser login — for local dev/agent use you can mint a session file directly and pass it
as a cookie, since the CLI `php` binary and `php -S` share the same default session storage:

```sh
set -a && source .env && set +a
psql "$DATABASE_URL" -c "SELECT ref, username, full_name FROM sys_users WHERE record_status='active' LIMIT 1;"

SID=$(php -r '
session_start();
$_SESSION["user"] = ["id"=>1,"ref"=>"USR-0000001","username"=>"admin@onesnzeros.local","full_name"=>"Agent","email"=>"admin@onesnzeros.local","roles"=>["Admin"]];
session_write_close();
echo session_id();
')

BASE="http://localhost:8000/modules/settings/modules_mgmt/module_generator/module_generator_data.php"
curl -s -b "PHPSESSID=$SID" "$BASE?action=list_sections"
curl -s -b "PHPSESSID=$SID" "$BASE?action=list_subsections"
curl -s -b "PHPSESSID=$SID" -X POST -H "Content-Type: application/json" "$BASE?action=generate" --data @module.json
```

Reach for this only when the check specifically needs to hit the live HTTP server (e.g.
testing the browser UI's requests) — otherwise the CLI script above is simpler and doesn't
need `php -S` running at all.

---

## What Generate Module does automatically

- Registers the module in `sys_tms_modules` (via the `register_tms_module` RPC — that table
  has RLS enabled, direct inserts are rejected by Postgres), recording `module_type` and
  `creates_journal_entry`
- Creates the business table via the `create_module_table` RPC
  (`docs/migrations/module_generator_table_rpc.sql`) — standard columns `id, ref, <your
  fields>, status, created_by, updated_by, created_at, updated_at` (skipped for `report`)
- For `header_detail` with non-empty `line_fields`, also creates a child line table via the
  `create_module_line_table` RPC (`docs/migrations/module_generator_advanced_rpc.sql`)
- Seeds a `sys_tms_module_number_series` row with your chosen prefix (skipped for `report`),
  so `consumeNextReference()` (`server/general/number_series.php`) works immediately
- Grants the Admin role permissions in `sys_role_module_permissions` (view/create/edit/print
  for `entry`/`header_detail`; view/print only for `report`)
- If `creates_journal_entry`, seeds `m_posting_rules` with your `gl_lines`
- Writes the file scaffold under `modules/{section_folder}/{subsection_folder}/{slug}/`,
  fully wired to the fields, module type, and GL configuration you defined

## When to hand-edit instead

The generator covers the standard cases (text/number/decimal/date/dropdown/checkbox fields
on a flat table, or a simple header+lines document, or a report shell). Hand-edit the
generated files afterward for anything it doesn't support yet:

- **Foreign-key/lookup fields** (e.g. picking a Customer or Location) on either header or
  line fields — not supported by the generator in any module type. Use an existing
  built-out module as a reference — e.g. `modules/master_files/references/general_expense_type/`
  for a simple one, `modules/finance/accounting/journal_entries/` for one with more logic,
  or `modules/operations/work_orders/work_order_entries/` for FK pickers on both header and
  line fields.
- **Report aggregation queries** — always a `TODO` stub; write the real query by hand (see
  `docs/report_concepts.md`).
- **Per-line GL splitting** — the GL toggle only supports a single header-level amount
  field, even on `header_detail` modules.
- Computed/derived columns, cross-table joins, or custom validation beyond required-field
  checks.

Conventions worth keeping consistent regardless: button group order **New, Search, Print,
Cancel, Close** then **Save**; Reference No. field is `col-sm-4`/`col-sm-8`, `font-monospace`,
`disabled`; card title bound to `{{ title }}`, never hardcoded.
