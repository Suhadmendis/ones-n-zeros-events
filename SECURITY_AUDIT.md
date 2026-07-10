# Security Audit

Findings from a full security review of the app (2026-07-06). This is a record of what was found, not a changelog — nothing listed here has been fixed yet. Delete/update items as they're addressed.

## Critical

### 1. Exposed secrets committed to git
`server/config.php` is tracked in git (with history) and hardcodes real credentials in plaintext:
- `SUPABASE_URL`, `SUPABASE_ANON_KEY` (live JWT), `SUPABASE_PUBLISHABLE` key
- `GOOGLE_MAPS_API_KEY`

`.env.example` exists with placeholders and `.env` is gitignored, but nothing actually loads from `.env` — `config.php` hardcodes the real values directly. Anyone with repo/history access has these keys.

**Fix direction:** move real values into `.env`, load via `getenv()`/similar in `config.php`, strip real values from the tracked file. (Decision already made: keep existing key values as-is for now, do not rotate — rotating is a separate follow-up if desired. Note the old values remain valid and recoverable from git history until rotated.)

### 2. RBAC is essentially unenforced
- `server/general/rbac.php` defines `requireModulePermission($action, $moduleFolder=null)` — the real fine-grained permission check (403 JSON if the user's role lacks the specific permission).
- `server/supabase.php`'s `guardModuleWrite()` is a separate, coarse, **fail-open** guard auto-invoked inside `supabase_post/patch/delete()`. It blocks a write only if the user has *zero* write-type permission on the module, can't distinguish create/edit/delete/approve, and explicitly allows the write through if the module can't be resolved.
- Of 216 module files (148 `*_data.php` + 68 `*_print.php`), only **1** — `modules/settings/modules_mgmt/module_generator/module_generator_data.php` — actually calls `requireModulePermission()`.
- The other 215 files have no explicit login/permission check. In particular, `_print.php` files (68 of them) may allow **unauthenticated reads**, since the coarse guard only fires on writes.

**Fix direction:** roll out `requireModulePermission()` per the mapping already documented in `PENDING.md` (`list/get/report/...`→`can_view`, `save`→`can_create`, `update/toggle`→`can_edit`, `delete`→`can_delete`, `post`→`can_approve`), section by section (Company Management → Finance → Operations → Master Files → Reports → Settings), smoke-testing against the 4 seeded roles (Admin/Manager/Operator/Viewer) each time. Add a top-of-file `requireModulePermission('can_view')` to every `_print.php`. Bake this into `MODULE_GUIDE.md`'s scaffolding template so new modules don't regrow the gap.

### 3. No CSRF protection anywhere
Zero CSRF token generation or validation exists in the repo. All state-changing POST/PATCH/DELETE requests rely solely on the session cookie for auth, which does not protect against cross-site forged requests.

**Fix direction:** add a per-session CSRF token (stored in `$_SESSION`), expose it to forms/AJAX calls, and validate it server-side before processing writes — likely centralized in `server/supabase.php` (alongside `guardModuleWrite()`) to avoid touching all 215 files individually.

## High

### 4. Weak session/cookie configuration
- `server/session.php` calls plain `session_start()` with PHP defaults — no `httponly`, `secure`, or `samesite` cookie flags configured anywhere.
- `server/auth.php`'s login handler never calls `session_regenerate_id()` after establishing `$_SESSION['user']` — session fixation risk (an attacker-supplied session ID can persist through login).

**Fix direction:** configure `session_set_cookie_params()` with `httponly=true`, `samesite=Strict|Lax`, and `secure` conditional on HTTPS (dev runs on plain HTTP via `php -S`); add `session_regenerate_id(true)` on successful login.

### 5. File upload accepts SVG with only extension-based validation
`modules/company_management/administration/company_profile/company_profile_data.php` (`saveUploadedImage()`) validates uploads only by client-supplied filename extension (`jpg,jpeg,png,svg,gif,webp`) — no MIME-type check via `finfo`, no file-size limit, and **SVG is allowed**. SVGs can embed `<script>`/event handlers and are served directly from `/assets/img/company/`, making this a stored-XSS vector. (Destination filenames are hardcoded to `logo`/`seal`, so there's no path-traversal risk.)

**Fix direction:** add `finfo_file()` MIME validation, add a size limit, and drop SVG from the allowed list (simplest fix, since this is a logo/seal upload, not a general document store) — or sanitize SVG content before storage if SVG support is required.

## Medium

### 6. Minimal/inconsistent input validation
No shared input-sanitization helper exists in `server/general/`. Most `*_data.php` files only do `trim()`/type casts on `$_POST`/`$_GET` before sending to Supabase — no `filter_var` or structured validation. This is lower risk than it sounds because **output escaping is already solid** (see below), but it's a defense-in-depth gap.

**Fix direction:** add a lightweight reusable helper (e.g. `server/general/validation.php`) and apply it incrementally to modules as they're touched, rather than a mechanical one-shot sweep of all 148 files.

### 7. No security-relevant test coverage
The Playwright suite (`tests/`) has no test for auth behavior, session expiry, RBAC enforcement (e.g. a Viewer role being blocked from a write action), or CSRF. `tests/setup/auth.setup.js` only logs in to seed reusable storage state — it doesn't assert anything about auth itself.

**Note:** `PENDING.md` claims the Playwright suite is currently broken due to a wrong relative import depth for `helpers/db.js`. Re-verified during this audit — the import paths in the current tree (`'../../../../helpers/db.js'`, 4 levels) are actually correct for spec files 4 directories below `tests/`. This looks like a stale doc note, not a live bug — worth confirming before spending time "fixing" it.

**Fix direction:** add tests for: a Viewer-role login asserting 403 on a representative write action, an unauthenticated request against a `_print.php` file, and a CSRF-token-missing request against a `_data.php` save action.

## Low / already solid — no action needed

- **No raw SQL injection risk.** All data access goes through Supabase's PostgREST REST API (`server/supabase.php`), not raw SQL/PDO — there's no string-concatenated SQL anywhere. (Minor nit: some query strings use `urlencode()` rather than true parameter binding, e.g. in `guardModuleWrite()`, but the values involved are server-derived refs, not raw user text — low risk.)
- **Passwords are properly hashed.** `password_hash()`/`password_verify()` (bcrypt) used consistently in `server/auth.php`, `password_change_data.php`, `create_user_data.php`. A one-off migration script (`scripts/rehash_plaintext_passwords.php`) exists for a completed plaintext-to-bcrypt migration and can be deleted once confirmed safe (see `PENDING.md`).
- **XSS output-escaping is solid.** `*_print.php` files consistently wrap output in `htmlspecialchars()` (139 files, 1000+ occurrences); Vue-driven views use auto-escaping `{{ }}` interpolation with no `v-html` usage found anywhere in `modules/`.

## Suggested rollout order

1. Secrets → `.env` (quick, isolated change)
2. Session/cookie hardening + `session_regenerate_id()` on login (quick, isolated change)
3. File upload MIME/size validation, drop SVG (quick, isolated change)
4. CSRF helper + rollout (moderate — need to confirm how forms actually submit, Vue/AJAX vs classic POST, before finalizing the token-delivery mechanism)
5. RBAC rollout across 215 files, section by section per `PENDING.md`'s order (largest effort)
6. Input validation helper + incremental rollout (ongoing, low priority given output escaping already holds)
7. Security-focused Playwright tests (ideally added alongside each of the above, not deferred to the end)
