# Ones n Zeros ERP — Development Progress

## Phase 1: Project Setup & Scaffold

- Converted AdminLTE 4 static demo into a PHP-served ERP shell
- Established routing via `home.php?page=<name>` — single layout shell, swappable content fragments
- Created `partials/` directory for shared PHP includes (`head.php`, `header.php`, `sidebar.php`, `footer.php`, `scripts.php`)
- Added ERP branding: page title, sidebar brand, footer, favicon

## Phase 2: Navigation & Dashboard

- Replaced AdminLTE demo sidebar navigation with ERP module map
- Converted `index.php` into an ERP overview dashboard
- Added dashboard tile linking to the Example page (`/home.php?page=example`)
- Cleaned up unused demo pages; reference material preserved in `_reference/`

## Phase 3: Example / Form Showcase Page

### Page scaffolding
- Created `entries/examples/example.php` — form element showcase, routed via `home.php?page=example`
- Created `entries/examples/example.js` — co-located JS, not included from `home.php`
- All script tags live inside their respective PHP fragment files

### Button groups
- Single "Example" card replaces the multi-card demo layout
- Three button groups in one row:
  - Action group: Add (primary) / Edit (secondary) / Search (success) / Active (info) / Cancel (warning) / Close (danger)
  - State group: Save / Delete / Reset — disabled until form is dirty
  - Options dropdown with View Details / Duplicate / Export / Archive items

### Horizontal form inputs (varying widths)
- Left column: Email (col-sm-9 full-width feel) / Password (col-sm-5 half)
- Right column: Username (col-sm-7 three-quarter) / Phone (col-sm-4 quarter)
- All bound with Vue `v-model`

### Additional form elements
- Remark — textarea
- Department — select (Sales / Finance / Warehouse / Procurement)
- Invoice Date — date picker
- Total Amount — input-group with LKR prefix
- Customer — read-only input + search button opening modal
- Permissions — three checkboxes (Can View / Can Edit / Can Delete)
- Payment Method — three radio buttons (Cash / Bank Transfer / Credit Card)

### Card footer
- "You have unsaved changes." warning shown via `v-if="isDirty"`
- Large success Save button bottom-right

## Phase 4: Vue 3 Integration

- Added Vue 3 CDN (`vue.global.prod.js`) to `partials/scripts.php` — available globally
- `example.js` mounts a Vue 3 app on `#example-app`
- `data()` holds the full `form` object with all field defaults
- `computed.isDirty` — true when any field differs from its default value
- `methods`: `onAdd`, `onEdit`, `onActive`, `onCancel`, `onClose`, `onReset`, `onSave`, `setCustomer(name)`

## Phase 5: Customer Search Modal

- Extracted search modal into `entries/examples/example_search.php` (separate from `example.php`)
- Extracted search logic into `entries/examples/example_search.js`
- Modal size: `modal-xl`
- DataTables 1.13.8 (Bootstrap 5 theme) with jQuery 3.7.1
  - 20 sample customer records (ID, Name, Email, Phone, Category, City, Status, Total Orders, Total Value, Last Order)
  - Status column renders colour-coded badges (Active = success, Inactive = danger, Pending = warning)
  - Global search, column sorting, pagination, page-size dropdown (5 / 10 / 25 / 50)
  - Column-specific search fields: Customer Name, Category
  - `columns.adjust().draw()` called on `shown.bs.modal` to fix column widths

## Phase 6: DataTables Export Buttons

- Added DataTables Buttons 2.4.2 extension to the search modal
- Added CDN dependencies: JSZip 3.10.1, pdfmake 0.2.7, buttons.html5, buttons.print
- Export buttons rendered above the table: Copy / Excel / CSV / PDF / Print
- Bootstrap 5 button styling with Bootstrap Icons on Excel and PDF buttons
- `dom` string: `<"d-flex justify-content-between align-items-center mb-2"Bf>rtip`

## Reference Material

- `_reference/` — original AdminLTE demo pages, kept as UI component lookup
- `REFERENCE_GUIDE.md` — maps component categories to exact reference files
- `CLAUDE.md` — project conventions, architecture notes, QA checklist
