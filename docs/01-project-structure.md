# 01 — Project Structure

> Where every file lives and why.

---

## Root Directory

| Path | Purpose |
|---|---|
| `index.php` | Application entry point. Bootstraps the framework, loads `.env`, initializes the Router. |
| `.env` | Environment variables (DB credentials, API keys). **Never committed to git.** |
| `.htaccess` | Apache rewrite rules — sends all requests to `index.php`. |
| `csdl.sql` | Database schema & seed data for fresh installs. |

---

## Core Framework (`core/`)

The micro-framework that powers the entire app. **Do NOT modify** unless you understand MVC internals.

| File | Role |
|---|---|
| `Router.php` | Parses URL → maps to Controller method. Supports `GET`/`POST`, route params, middleware. |
| `Controller.php` | Base class. Provides `view()`, `json()`, `redirect()`, `post()` helpers. |
| `Model.php` | Base class. Provides `all()`, `find()`, `create()`, `update()`, `delete()` via `$table` property. |
| `Database.php` | MySQLi singleton connection wrapper. |

---

## Application Logic (`app/`)

| Folder | What Goes Here |
|---|---|
| `Controllers/` | Request handlers. One controller per feature. Admin controllers live in `Controllers/Admin/`. |
| `Models/` | Database models extending `core/Model.php`. One model per DB table. |
| `Services/` | Business logic helpers (e.g., `AuthService` for login/session). |
| `Validators/` | Input validation classes. Keep validation out of controllers. |
| `Helpers/` | Utility functions (formatting, calculations). |
| `Middlewares/` | Request middleware (auth checks, rate limiting). |

### Controller Naming Convention
- Public: `app/Controllers/ProductController.php`
- Admin: `app/Controllers/Admin/UserController.php`

---

## Configuration (`config/`)

| File | Purpose |
|---|---|
| `app.php` | Global app config (site name, timezone, debug mode). |
| `routes.php` | All route definitions. Maps URL patterns to Controller@method. |

---

## Views (`views/`)

PHP template files that render HTML. Organized by feature area.

```
views/
├── admin/              # Admin panel views
│   ├── layout/         # Shared: head.php, nav.php, breadcrumb.php, foot.php
│   ├── finance/        # Giftcodes, orders, payments
│   ├── users/          # User management (index, edit)
│   ├── products/       # Product management
│   ├── categories/     # Category management
│   ├── logs/           # System logs (journal)
│   └── settings/       # Admin settings
├── auth/               # Login, register, forgot password
├── home/               # Homepage
├── product/            # Product detail (public)
├── profile/            # User profile
├── policy/             # Policy pages
└── terms/              # Terms of service
```

---

## Static Assets (`assets/`)

```
assets/
├── css/
│   ├── admin-pages.css   # ⭐ THE master admin stylesheet (global)
│   ├── admin.css          # AdminLTE base overrides
│   ├── style.css          # Public-facing main stylesheet
│   ├── styles.css         # Extended public styles
│   ├── responsive.css     # Mobile responsiveness
│   └── ...                # Third-party: bootstrap, datatables, quill, etc.
├── js/
│   ├── swal_helper.js     # ⭐ Centralized SweetAlert helpers
│   ├── main.js            # Public-facing JS logic
│   ├── script.js          # Extended public scripts
│   └── ...                # Third-party: jquery, datatables, sweetalert, etc.
└── images/
    └── ...                # Static images, logos, icons
```

---

## Legacy System (`hethong/`)

Older procedural PHP code. Being gradually replaced by `app/` OOP structure.

| File | Purpose |
|---|---|
| `config.php` | Legacy helper functions (`timeAgo()`, `sendTele()`, etc.) |
| `head.php` / `head2.php` | Public-facing HTML head includes |
| `nav.php` / `footer.php` | Public-facing navigation & footer |

---

## Admin Layout (`admin/`)

| File | Purpose |
|---|---|
| `head.php` | Admin HTML head — loads `admin-pages.css`, Bootstrap, FontAwesome |
| `nav.php` | Sidebar navigation (uses `AdminMenuRenderer`) |
| `foot.php` | Closing scripts — jQuery, AdminLTE, tooltip init |

---

## Other Directories

| Path | Purpose |
|---|---|
| `pages/` | Legacy standalone pages |
| `ajax/` | AJAX endpoint handlers |
| `database/` | Database migration helpers |
| `public/` | Publicly accessible uploads |
| `docs/` | This documentation folder |
