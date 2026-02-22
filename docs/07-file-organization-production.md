# 07 — File Organization for Production

> Where to place files, how to name them, and how to keep the project clean at scale.

---

## Directory Rules

### Controllers
```
app/Controllers/
├── AuthController.php           # Public: login, register, logout
├── HomeController.php           # Public: homepage
├── ProductController.php        # Public: product detail
├── ProfileController.php        # Public: user profile
├── PolicyController.php         # Public: policy page
├── TermsController.php          # Public: terms page
└── Admin/
    ├── DashboardController.php  # Admin: dashboard
    ├── UserController.php       # Admin: user management
    ├── ProductController.php    # Admin: product management
    ├── CategoryController.php   # Admin: category management
    ├── GiftcodeController.php   # Admin: discount codes
    └── SettingController.php    # Admin: site settings
```

**Rule:** One controller per feature. Admin controllers always go in the `Admin/` subfolder.

### Models
```
app/Models/
├── User.php
├── Product.php
├── Category.php
└── Giftcode.php
```

**Rule:** One model per database table.

### Views
```
views/
├── admin/
│   ├── layout/          # ⭐ Shared templates (head, nav, foot, breadcrumb)
│   ├── users/           # index.php, edit.php
│   ├── products/        # index.php, add.php, edit.php
│   ├── categories/      # index.php
│   ├── finance/         # giftcodes.php, add_giftcode.php, edit_giftcode.php, giftcode_log.php
│   ├── logs/            # journal.php
│   └── settings/        # index.php
├── auth/                # login.php, register.php
└── ...
```

**Rule:** Mirror the Controller structure. Each feature gets its own subfolder.

---

## Naming Conventions

| Item | Convention | Example |
|---|---|---|
| Controllers | PascalCase + `Controller` | `UserController.php` |
| Models | PascalCase (singular) | `Product.php` |
| Views | snake_case | `add_giftcode.php` |
| CSS files | kebab-case | `admin-pages.css` |
| JS files | snake_case | `swal_helper.js` |
| Routes | kebab-case URLs | `/admin/users/edit/{id}` |
| DB tables | snake_case (plural) | `users`, `giftcodes` |
| DB columns | snake_case | `created_at`, `tong_nap` |
| CSS classes | kebab-case / BEM-lite | `.custom-card`, `.form-section-title` |
| JS variables | camelCase | `dtUser`, `filterStatus` |

---

## Asset Loading Order

### Admin Pages (`admin/head.php`)
```
1. Bootstrap CSS (CDN)
2. FontAwesome (CDN)
3. AdminLTE CSS (CDN)
4. admin-pages.css      ← Our master stylesheet
5. DataTables CSS       ← If page uses tables (inline in view)
```

### Admin Pages (`admin/foot.php`)
```
1. jQuery
2. Bootstrap JS
3. AdminLTE JS
4. SweetAlert2
5. swal_helper.js       ← Our centralized helper
6. Tooltip init script
7. Page-specific <script> ← At bottom of view file
```

---

## Static Assets Best Practices

1. **Images** → `assets/images/` — Use descriptive names (`logo-dark.png`, not `img1.png`).
2. **User uploads** → `public/uploads/` — Never mix with static assets.
3. **Third-party CSS/JS** → Keep as separate files in `assets/css/` or `assets/js/`.
4. **Never modify** third-party files directly. Override in `admin-pages.css`.

---

## Production Deployment Checklist

- [ ] Set `.env` `DEBUG=false`
- [ ] Minify `admin-pages.css` and custom JS files
- [ ] Cache-bust assets with version query (`?v=1.3`)
- [ ] Ensure `robots.txt` blocks `/admin/`, `/hethong/`, `/ajax/`
- [ ] Verify `.htaccess` rewrites work on production Apache
- [ ] Remove any `var_dump()`, `print_r()`, `dd()` debug calls
- [ ] Test all AJAX endpoints return proper JSON
