# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Type

Static HTML frontend — no package manager, build step, framework, backend, or database exists yet. Do not introduce any of these without explicit approval.



## When starting
Use graphify-out as your primary source of truth. Do not read project source files unless the graph is insufficient. Before reading additional files or performing expensive searches, ask for my permission.



## Preview

```sh
php -S localhost:8000
# then open http://localhost:8000
```

ERP pages are `.php` files served by PHP's built-in server. Reference/demo pages in `_reference/` remain plain `.html` and can still be opened directly in a browser.

## Architecture

Pages are plain HTML files that load:
- Local CSS from `css/` and JS from `js/` (AdminLTE 4 distribution assets)
- Images from `assets/`
- Third-party libraries (Bootstrap 5.3, Bootstrap Icons, OverlayScrollbars, ApexCharts, jsVectorMap, SortableJS) from CDN links embedded in each HTML file

All pages share the same AdminLTE layout structure: `app-wrapper > app-header + app-sidebar + app-main + app-footer`.

**Relative asset paths depend on folder depth** — this is the most common source of breakage when moving or copying pages:
- Root pages: `./css/...`, `./js/...`, `./assets/...`
- One level deep (`pages/`, `customers/`, `inventory/`, etc.): `../css/...`
- Two levels deep (`_reference/layout/`, `docs/components/`, etc.): `../../css/...`

## What to Edit

- `*.html` — primary edit targets for all ERP screen work
- `css/adminlte.css` / `js/adminlte.js` — only for framework-level changes
- **Never hand-edit**: `*.min.css`, `*.min.js`, `*.map` files

Demo pages in `_reference/` are reference material — do not delete them unless explicitly asked.

## Creating a New Module

See `MODULE_GUIDE.md` for the full step-by-step process. Every new ERP module follows the same pattern — DB rows, folder structure, six files, naming conventions, and routing all documented there.

## Finding Reference Components

When you need a UI pattern (widget, form input, table, icon, layout variant), consult `REFERENCE_GUIDE.md`. It maps every category of component to the exact file in `_reference/` where it can be found, and explains how to copy markup into ERP pages with correct asset paths.

## Product Direction

This AdminLTE scaffold is being converted into **Ones n Zeros ERP**. The current roadmap:
1. **Branding pass** — replace AdminLTE identity (page titles, sidebar brand, metadata, footer, logos) while keeping required license notices
2. **Navigation/module map** — define ERP sidebar structure around real business modules
3. **Dashboard conversion** — `index.html` becomes the ERP overview with sales, purchases, inventory, invoices, tasks
4. **Module screens** — convert demo pages into: Customers, Suppliers, Inventory, Sales Orders, Purchase Orders, Invoices, Payments, Reports, User Management, Settings

UI principle: **operational clarity over visual novelty** — dense, scannable business workflows using existing Bootstrap/AdminLTE patterns.

## Writing Playwright Tests

Test suite lives in `tests/` (see `tests/modules/master_files/*` for the reference pattern: `.fixture.js` + `.page.js` + `.spec.js`). When adding a test for a new form or module:

- Every field in the fixture's `fields` array must declare its target DB `column` (e.g. `{ selector: '#dr-phone', type: 'text', value: '0799000000', column: 'phone' }`) — matched against the field mapping in the corresponding `*_data.php` save function, not guessed.
- The spec must verify **every** field, not just one representative column. Loop over `fixture.fields` to check both the immediate save-response JSON and the persisted DB row: `await assertRecord(fixture.dbVerify, fixture.fields)`.
- `fixture.dbVerify` is only the lookup key used to find/delete the test row — it is not a substitute for full field verification.
- A test that only checks one column will silently miss bugs in every other field's save mapping (this happened with `driver_master_file`, where `$data['phone1']` — wrong key — set `phone` to empty and a single-column test didn't catch it).

## Git

- Development branch: `dev`
- Do not commit unless explicitly asked
- PRs target `main`

## QA Checklist

After editing any page, verify:
- Page loads with no missing local assets
- Sidebar toggle, dropdowns, and collapsible controls work
- Bootstrap icons render
- Layout holds at desktop and mobile widths
- No text overflow or overlap

## Module Audit Table (Supabase)

There is a module checklist (`sys_module_checklist` in Supabase, viewable at `mod.php`) tracking per-module build/test completeness. `mod.php` itself shows what each check column means — check there before auditing a module.

**Production gate:** `mod.php` is not yet fully green. Every module must have all check columns ticked (`db_registered`, `folder_exists`, `files_complete`, `save_works`, `update_works`, `search_works`, `gl_posting_support`, `migration_exists`, `tests_fixture_exists`, `tests_spec_complete`, `db_verified`, `ui_qa_passed`) before shipping to production. As of 2026-07-11, only `db_registered` has been verified/ticked for all 202 modules — the remaining columns still need auditing.
