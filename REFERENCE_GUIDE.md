# Reference Guide

All AdminLTE 4 demo and reference pages live in `_reference/`. When building an ERP screen and you need a component pattern, layout, or UI element, look here first.

## How to Browse

```sh
python3 -m http.server 8000
```

Then open `http://localhost:8000/_reference/<folder>/<file>.html` in a browser to see the live rendered component.

---

## Quick Lookup by Need

| I need... | Go to |
|---|---|
| KPI / metric summary cards | `_reference/widgets/small-box.html` |
| Icon + stat info cards | `_reference/widgets/info-box.html` |
| Card layouts (collapsible, tabbed, footer) | `_reference/widgets/cards.html` |
| Any form input (text, select, date, file…) | `_reference/forms/elements.html` |
| Form layout patterns (horizontal, inline, grid) | `_reference/forms/layout.html` |
| Form validation styles | `_reference/forms/validation.html` |
| Multi-step form wizard | `_reference/forms/wizard.html` |
| Basic sortable HTML table | `_reference/tables/simple.html` |
| Advanced table (search, pagination, export) | `_reference/tables/data.html` |
| Buttons, badges, alerts, modals, tabs, accordions | `_reference/UI/general.html` |
| Bootstrap Icons catalog | `_reference/UI/icons.html` |
| Timeline component | `_reference/UI/timeline.html` |
| Login / register screens | `_reference/examples/login.html` |
| Email list / thread / compose layout | `_reference/mailbox/inbox.html` |
| Sidebar layout variants | `_reference/layout/` (see below) |
| Color theme customization | `_reference/generate/theme.html` |
| Alternative dashboard designs | `_reference/index2.html`, `_reference/index3.html` |

---

## Reference Folder Contents

### `_reference/layout/` — Sidebar & header layout variants
| File | What it shows |
|---|---|
| `fixed-sidebar.html` | Sidebar fixed, content scrolls |
| `fixed-header.html` | Header fixed at top |
| `fixed-footer.html` | Footer pinned at bottom |
| `fixed-complete.html` | Header + sidebar + footer all fixed |
| `collapsed-sidebar.html` | Sidebar collapsed to icon-only on load |
| `collapsed-sidebar-without-hover.html` | Collapsed sidebar, no expand on hover |
| `sidebar-mini.html` | Compact mini sidebar |
| `unfixed-sidebar.html` | Default scrollable sidebar |
| `logo-switch.html` | Logo swap behavior between collapsed/expanded |
| `layout-custom-area.html` | Custom area injection in sidebar |
| `layout-rtl.html` | Right-to-left language layout |

### `_reference/widgets/` — Reusable data display blocks
| File | What it shows |
|---|---|
| `small-box.html` | Coloured metric cards with icon, number, trend link |
| `info-box.html` | Icon-left info cards with label and value |
| `cards.html` | Card header/body/footer, collapsible, tabbed, colour variants |

### `_reference/forms/` — Input and form patterns
| File | What it shows |
|---|---|
| `elements.html` | Every input type: text, number, email, password, select, multi-select, checkbox, radio, toggle switch, file upload, date, colour picker, range slider, textarea |
| `layout.html` | Horizontal form, inline form, grid-based form layouts |
| `validation.html` | Valid/invalid state styles, feedback messages, Bootstrap validation classes |
| `wizard.html` | Multi-step wizard with progress indicator |

### `_reference/tables/` — Data table patterns
| File | What it shows |
|---|---|
| `simple.html` | Plain Bootstrap table with sortable columns |
| `data.html` | DataTables.js: search, column sort, pagination, export (CSV, Excel, PDF, Print) |

### `_reference/UI/` — General UI components
| File | What it shows |
|---|---|
| `general.html` | Buttons (all variants/sizes), button groups, badges, pills, alerts, modals, offcanvas, tabs, pills nav, accordion, collapse, tooltips, popovers, progress bars, spinners, toasts, breadcrumbs, pagination |
| `icons.html` | Full Bootstrap Icons set with search — copy icon class names from here |
| `timeline.html` | Vertical timeline with icon markers and content blocks |

### `_reference/mailbox/` — Email/messaging UI
| File | What it shows |
|---|---|
| `inbox.html` | Message list with sender, subject, date, unread state |
| `read.html` | Message detail/thread view with reply area |
| `compose.html` | Compose form with To/CC/Subject/body fields |

### `_reference/examples/` — Auth and special-purpose pages
| File | What it shows |
|---|---|
| `login.html` | Centered login card |
| `login-v2.html` | Full-background login variant |
| `register.html` | Registration form card |
| `register-v2.html` | Full-background registration variant |
| `lockscreen.html` | Lockscreen with avatar and PIN entry |

### `_reference/generate/` — Theming
| File | What it shows |
|---|---|
| `theme.html` | Live colour customizer that generates CSS variable overrides |

---

## How to Copy a Pattern into an ERP Page

1. Serve the project and open the reference page in a browser
2. Find the component you want — inspect it to identify the HTML block
3. Open the reference HTML file and copy the relevant markup
4. Paste into your target ERP page (e.g. `pages/invoice.html` or `customers/list.html`)
5. Check and adjust asset paths — the path depth changes between `_reference/` subfolders and ERP pages:

### Asset Path Rules

| File location | Use these paths |
|---|---|
| Root (`index.html`) | `./css/` &nbsp; `./js/` &nbsp; `./assets/` |
| One level deep (`pages/`, `customers/`, `inventory/`, …) | `../css/` &nbsp; `../js/` &nbsp; `../assets/` |
| `_reference/` subfolders | `../../css/` &nbsp; `../../js/` &nbsp; `../../assets/` |

When copying HTML from a `_reference/` file into a `pages/` file, any inline `src` or `href` that points to `../../assets/` must become `../assets/`. The CSS and JS links in the `<head>` are already correct in your target ERP file — do not copy them from the reference file.

---

## Finding an Icon

1. Open `http://localhost:8000/_reference/UI/icons.html`
2. Use the browser's built-in search (Cmd+F) to find the icon by name
3. Copy the class from the icon label, e.g. `bi-box-seam`
4. Use it as `<i class="bi bi-box-seam"></i>` in your page
