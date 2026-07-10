# Architecture

## Overview

This repository is currently a PHP-based AdminLTE ERP frontend prototype. It uses PHP entry points and shared partials for layout, PHP helper files for Supabase access, and plain JavaScript for page behavior.

The original AdminLTE static pages remain available as reference material, but the active application flow is centered on `index.php`, `login.php`, `home.php`, `partials/`, `server/`, and `modules/`.

## Runtime Model

The app runs through a PHP server:

```text
Browser
  |-- PHP-rendered AdminLTE pages
  |-- Local CSS from css/
  |-- Local JS from js/
  |-- Local images from assets/
  |-- CDN libraries from cdn.jsdelivr.net
  `-- Supabase REST API through server/supabase.php helpers
```

## Page Structure

Most full application pages are assembled by `home.php` from shared partials and module files:

```text
app-wrapper
|-- partials/header.php
|-- partials/sidebar.php
|-- app-main
|   `-- modules/<section>/<subsection>/<module>/<module>.php
`-- partials/footer.php
```

Relative paths vary depending on folder depth:

- PHP layout partials generally use root-relative paths such as `/css/...`, `/js/...`, `/assets/...`, and `/modules/...`.
- AdminLTE reference HTML pages may still use relative paths based on their folder depth.
- Module PHP files are included by `home.php`, so their local PHP includes should use `__DIR__` when referencing sibling files.

Be careful when moving or copying HTML between folders.

## Important Directories

- `assets/` contains static images and logo assets.
- `css/` contains AdminLTE CSS outputs and source maps.
- `js/` contains AdminLTE JavaScript outputs and source maps.
- `partials/` contains shared PHP layout pieces.
- `server/` contains Supabase, session, logging, and module helper code.
- `modules/` contains ERP module screens, data endpoints, search modals, JavaScript, and print views.
- `database/` contains schema material.
- `tests/` contains Playwright tests.
- `docs/` contains upstream AdminLTE documentation and project Markdown files.
- `_reference/` contains upstream AdminLTE reference pages.
- `pages/` contains application-style pages.

## Dependencies

Observed dependencies include:

- AdminLTE 4.0.0
- Bootstrap 5.3
- Bootstrap Icons
- PHP
- Supabase
- Playwright
- OverlayScrollbars
- ApexCharts
- jsVectorMap
- SortableJS
- Source Sans 3 font

Most browser-side third-party dependencies are loaded through CDN links in the rendered pages. Node dependencies are used for tests, not for a frontend build step.

## Asset Policy

Treat these files as generated or vendor distribution assets unless intentionally updating AdminLTE itself:

- `css/*.min.css`
- `css/*.map`
- `js/*.min.js`
- `js/*.map`

For product-level screen changes, edit HTML first. Add custom project CSS or JavaScript only when a repeated pattern needs it.

## Future Architecture Options

Possible future directions:

- Keep as static prototype for design and workflow validation.
- Add a lightweight static build pipeline.
- Convert repeated HTML into templates or components.
- Integrate with an API backend.
- Migrate to a frontend framework.

Any major architecture change should be documented before implementation.
