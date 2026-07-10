# AI Agent Guide

This repository is expected to be developed with help from AI agents. Follow these instructions before making changes.

## First Read

Before editing code or content, read:

1. `README.md`
2. `docs/PROJECT_BRIEF.md`
3. `docs/ARCHITECTURE.md`
4. `docs/DEVELOPMENT.md`
5. `docs/ROADMAP.md`

## Current Project Reality

- This is an AdminLTE 4 based ERP frontend prototype.
- The active app uses PHP entry points, shared PHP partials, module folders, and Supabase helper code.
- Node/package files are present for Playwright testing; there is still no frontend build pipeline or framework router.
- AdminLTE demo/reference material still exists and should be treated as scaffolding, not finished ERP functionality.
- The desired destination is an operational ERP frontend for Ones n Zeros.

## Editing Rules

- Keep changes scoped to the requested task.
- Preserve the existing folder structure unless a task explicitly requires restructuring.
- Prefer editing readable source files:
  - `*.html`
  - `*.php`
  - module-local `*.js`
  - `css/adminlte.css` only when framework-level styling must change
  - `js/adminlte.js` only when framework-level behavior must change
- Do not manually edit minified files or source maps unless the task specifically asks for distribution asset updates:
  - `*.min.css`
  - `*.min.js`
  - `*.map`
- Do not delete upstream demo pages just because they look unused. They may be useful as implementation references during the ERP conversion.
- Do not introduce a framework, bundler, alternate backend, or database without explicit approval.

## UI Guidelines

- Treat this as an operational ERP interface, not a marketing website.
- Favor dense, clear, repeatable business workflows.
- Use Bootstrap/AdminLTE conventions already present in the project.
- Keep navigation predictable.
- Keep tables, forms, filters, and action buttons easy to scan.
- Avoid decorative redesigns that reduce clarity.

## Branding Direction

Replace upstream AdminLTE identity gradually and intentionally:

- Page titles
- Meta titles/descriptions
- Sidebar brand text
- Header labels
- Footer links
- Logos and image alt text
- Demo company names

Do not remove AdminLTE license references from files that require attribution.

## Verification Checklist

After changes, check:

- The edited HTML page opens in a browser.
- Relative asset paths still work from that page depth.
- Sidebar links still resolve correctly.
- Bootstrap icons render.
- Dropdowns, sidebar toggle, and collapsible controls still work.
- No obvious text overflow or layout overlap appears on desktop and mobile widths.

## Git Hygiene

- Work on the current branch unless instructed otherwise.
- Do not revert user changes.
- Do not commit unless explicitly asked.
- Keep generated local files out of Git.
- `.DS_Store`, editor settings, and other local files should stay ignored.

## Documentation Updates

When a change affects project structure, workflow, or conventions, update the relevant Markdown file in `docs/`.
