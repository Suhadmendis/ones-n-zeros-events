# Ones n Zeros ERP

AdminLTE-based ERP interface for Ones n Zeros.

This repository started from a customized AdminLTE 4 static distribution and has grown into a PHP-based ERP frontend prototype. It now includes shared PHP partials, module pages, Supabase-backed data access, and Playwright tests alongside the original AdminLTE reference material.

## Current State

- PHP entry points, shared partials, module pages, static assets, and AdminLTE reference pages.
- Supabase is used as the current backend data service through PHP helpers in `server/`.
- Node dependencies are present for Playwright-based browser testing; there is still no frontend build pipeline.
- AdminLTE 4 assets are checked into `css/` and `js/`.
- `_reference/`, `pages/`, and `docs/` still contain upstream AdminLTE demo/reference content and should be treated as scaffolding.
- External browser dependencies are still mostly loaded from CDNs in the HTML/PHP output.

## Preview

Run the project through PHP's built-in server from the repository root.

Example:

```sh
php -S localhost:8000
```

Then visit:

```text
http://localhost:8000
```

## Repository Map

```text
.
|-- index.php               # Authenticated dashboard entry point
|-- home.php                # Module host/router
|-- login.php               # Login page
|-- assets/                 # Images, logos, avatars, product images
|-- css/                    # AdminLTE CSS, RTL CSS, minified builds, maps
|-- js/                     # AdminLTE JavaScript, minified builds, maps
|-- modules/                # ERP module pages, scripts, data handlers, and print views
|-- partials/               # Shared PHP layout partials
|-- server/                 # Supabase, session, logging, and module helpers
|-- database/               # Database schema material
|-- tests/                  # Playwright tests
|-- docs/                   # AdminLTE docs plus project documentation
|-- _reference/             # Upstream AdminLTE demo/reference pages
|-- pages/                  # ERP-adjacent pages and app demos
|-- package.json            # Playwright test scripts and dependencies
`-- playwright.config.js    # Root Playwright configuration
```

## Key Technologies

- AdminLTE 4.0.0
- Bootstrap 5.3
- Bootstrap Icons
- PHP
- Supabase
- Vanilla JavaScript
- Playwright
- OverlayScrollbars
- ApexCharts
- jsVectorMap
- SortableJS

## Development Direction

The next development phase should convert the generic AdminLTE demo into a coherent ERP product interface:

1. Apply Ones n Zeros branding across titles, navigation, logos, footer, and metadata.
2. Decide the ERP module structure before deeply editing pages.
3. Convert useful demo pages into real product screens.
4. Remove unused upstream demo pages only after confirming they are not needed as references.
5. Keep the current PHP/Supabase architecture documented as it evolves.
6. Introduce a frontend build system only when it solves a real development problem.

## AI Agent Instructions

AI agents should read `AGENTS.md` before changing the repository.

For project context, also read:

- `docs/PROJECT_BRIEF.md`
- `docs/ARCHITECTURE.md`
- `docs/DEVELOPMENT.md`
- `docs/ROADMAP.md`
