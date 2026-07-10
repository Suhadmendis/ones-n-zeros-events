# Development Guide

## Prerequisites

PHP is required to run the application locally. Node dependencies are required only when running Playwright tests.

Install test dependencies when needed:

```sh
npm install
```

## Local Preview

From the repository root:

```sh
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

## Common Entry Points

- `login.php` - login page
- `index.php` - authenticated dashboard entry point
- `home.php` - module host/router
- `partials/` - shared layout files
- `modules/` - ERP module screens and handlers
- `_reference/` - AdminLTE reference material
- `pages/` - application-style reference pages

## Recommended Workflow

1. Create or switch to a feature branch.
2. Read `AGENTS.md` and relevant docs.
3. Identify the page, partial, helper, or module being changed.
4. Make focused edits.
5. Preview the page through the PHP server.
6. Check responsive behavior.
7. Update Markdown documentation if the change affects conventions or structure.

## HTML Editing Notes

- Keep relative paths correct for the file location.
- Preserve required AdminLTE structure unless intentionally changing layout.
- Keep Bootstrap classes consistent with nearby examples.
- Prefer existing AdminLTE components before creating new patterns.
- Remove placeholder/demo text only when replacing it with product content.

## PHP Editing Notes

- Keep shared layout changes in `partials/` when they affect multiple pages.
- Keep module-specific UI and handlers inside the relevant `modules/` directory.
- Use `server/` helpers for Supabase, session, logging, and module behavior instead of duplicating connection logic.
- Use `__DIR__` for PHP includes inside module folders.

## CSS Editing Notes

- Avoid broad changes to `css/adminlte.css` for one-page needs.
- Prefer page-level utility classes first.
- If custom project styling grows, create a dedicated project stylesheet and document it here.
- Do not edit minified CSS by hand.

## JavaScript Editing Notes

- Existing behavior is mostly AdminLTE, module-local scripts, and shared helper scripts.
- Keep page-specific JavaScript near the page unless it becomes shared behavior.
- Do not edit minified JavaScript by hand.
- Avoid adding browser/runtime dependencies without documenting why.

## Automated Tests

Playwright tests live in `tests/` and use `playwright.config.js`.

```sh
npm test
```

Tests expect the PHP app to be available at `http://localhost:8000` and require valid login/database values in `.env`.

## Manual QA Checklist

For changed pages, verify:

- Page loads through the PHP server without missing local assets.
- CDN dependencies load when online.
- Sidebar toggle works.
- Dropdowns and collapses work.
- Icons render.
- Tables and forms remain usable.
- Layout works at desktop and mobile widths.
- Text does not overlap or overflow controls.

## Git Notes

Current development branch:

```text
main
```

Do not commit changes unless explicitly asked.
