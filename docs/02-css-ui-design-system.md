# 02 — CSS & UI Design System

> The single source of truth for colors, layout, and reusable UI components.

---

## Golden Rule

**All admin panel styles live in ONE file:** `assets/css/admin-pages.css`.

- ❌ No `<style>` blocks inside view files.
- ❌ No inline `style="..."` attributes (except dynamic values like progress bars).
- ✅ Every visual change goes into `admin-pages.css`.
- ✅ Fix CSS once → fixed everywhere.

---

## Color Palette

| Token | Hex | Usage |
|---|---|---|
| `--primary-color` | `#8b5cf6` | Purple accent — card borders, buttons, pagination active |
| `--btn-search` | `#8b5cf6` | Edit/action button background |
| `--bg-content` | `#f4f6f9` | Page background (content-wrapper) |
| `--card-bg` | `#ffffff` | Card backgrounds |
| `--text-color` | `#374151` | Default body text |
| `--text-muted` | `#6b7280` | Secondary/helper text |
| `--danger` | `#ef4444` | Delete buttons, banned badges |
| `--success` | `#22c55e` | Active badges, add-money buttons |
| `--info` | `#38bdf8` | Info badges, log buttons |

---

## Core UI Components

### 1. Custom Card (`.custom-card`)

The universal wrapper for ALL admin content. Full width, subtle shadow, no top border.

```html
<div class="card custom-card">
    <div class="card-header border-0 pb-0">
        <h3 class="card-title text-uppercase font-weight-bold">PAGE TITLE</h3>
    </div>
    <div class="card-body pt-3">
        <!-- Content here -->
    </div>
</div>
```

**CSS provides:**
- `border-radius: 8px`
- `box-shadow` subtle elevation
- `.card-title` gets a purple left-border pill via `::before` pseudo-element

### 2. Form Section (`.form-section`)

Groups related form fields with a titled block. Used on Add/Edit pages.

```html
<div class="form-section">
    <div class="form-section-title">Section Name</div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label class="font-weight-bold form-label-req">Field</label>
                <input type="text" class="form-control" required>
            </div>
        </div>
    </div>
</div>
```

**CSS provides:**
- Light gray background with left-border accent
- `.form-label-req` adds a red `*` via `::after`

### 3. DataTable Filters (`.dt-filters`)

Custom search bar replacing the default DataTables search. Consistent across all list pages.

```html
<div class="dt-filters">
    <div class="row g-2 justify-content-center align-items-center mb-3">
        <div class="col-md-4 mb-2">
            <input id="f-keyword" class="form-control form-control-sm" placeholder="Search...">
        </div>
        <div class="col-md-3 mb-2 text-center">
            <button id="btn-clear" class="btn btn-danger btn-sm shadow-sm w-100">
                <i class="fas fa-trash"></i> Clear Filters
            </button>
        </div>
    </div>
    <div class="top-filter mb-2">
        <div class="filter-show">
            <span class="filter-label">SHOW :</span>
            <select id="f-length" class="filter-select flex-grow-1">...</select>
        </div>
    </div>
</div>
```

### 4. Table Wrapper

Always wrap tables inside `.table-responsive.table-wrapper` for mobile scroll and rounded corners.

```html
<div class="table-responsive table-wrapper mb-3">
    <table class="table text-nowrap table-hover table-bordered w-100">
        <thead>
            <tr>
                <th class="text-center font-weight-bold align-middle">COLUMN</th>
            </tr>
        </thead>
    </table>
</div>
```

> ⚠️ Never use `table-striped`. All rows are white for a clean aesthetic.

### 5. Date Badge (`.date-badge`)

Timestamp display with tooltip showing relative time ("2 giờ trước").

```html
<span class="badge date-badge" data-toggle="tooltip" title="<?= timeAgo($time) ?>">
    <?= $time ?>
</span>
```

### 6. Info Box (Summary Cards)

Used on index pages to show quick statistics.

```html
<div class="info-box shadow-sm mb-3" style="border-radius: 8px;">
    <span class="info-box-icon bg-primary elevation-1" style="border-radius: 8px;">
        <i class="fas fa-users"></i>
    </span>
    <div class="info-box-content">
        <span class="info-box-text font-weight-bold text-uppercase">TOTAL USERS</span>
        <span class="info-box-number h4 mb-0"><?= $count ?></span>
    </div>
</div>
```

---

## Card Footer (Forms)

Transparent, borderless footer for form submit/cancel buttons.

```html
<div class="card-footer text-right bg-transparent border-top-0 pt-0">
    <a href="..." class="btn btn-light border mr-2 px-4">
        <i class="fas fa-times mr-1"></i>Cancel
    </a>
    <button type="submit" class="btn btn-primary px-4">
        <i class="fas fa-save mr-1"></i>Save
    </button>
</div>
```

---

## Section & Page Layout Rules

| Page Type | Layout |
|---|---|
| **List/Index** | `custom-card` → `dt-filters` → `table-wrapper` → DataTable |
| **Add/Create** | `custom-card` → `form-section`(s) → `card-footer` |
| **Edit/Update** | Same as Add. Pre-fill values from database. |
| **Log/History** | Same as List. Fewer filter columns. |

---

## Responsive Behavior

- Admin pages rely on Bootstrap 4 grid (`col-md-*`, `col-lg-*`, `col-xl-*`).
- Tables use `.table-responsive` for horizontal scroll on mobile.
- Sidebar collapses via AdminLTE built-in toggle.
