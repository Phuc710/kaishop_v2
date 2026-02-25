# 05 — Clean Code & OOP Principles

> Write once, use everywhere. Fix once, fix ALL.

---

## Core Philosophy

This project follows a **centralized, reusable** code architecture:

1. **Shared CSS** — One file (`admin-pages.css`) styles every admin page.
2. **Shared JS** — One helper (`swal_helper.js`) handles all alerts.
3. **Shared PHP** — Base classes (`Controller`, `Model`) provide common methods.
4. **Shared Views** — Layout files (`head.php`, `nav.php`, `foot.php`) are included everywhere.

> 💡 If you copy-paste code, you're doing it wrong. Extract it into a shared location.

---

## The DRY Principle (Don't Repeat Yourself)

### ❌ BAD: Duplicated code

```php
// In UserController
$this->authService->requireAuth();
$user = $this->authService->getCurrentUser();
if ($user['level'] != 9) { die('Access denied'); }

// Same code copy-pasted in ProductController, GiftcodeController...
```

### ✅ GOOD: Shared method

```php
// In every admin controller — ONE private method
private function requireAdmin() {
    $this->authService->requireAuth();
    $user = $this->authService->getCurrentUser();
    if ($user['level'] != 9) {
        http_response_code(403);
        die('Access denied');
    }
}
```

---

## The Single File Principle (CSS/JS)

### ❌ BAD: Inline styles scattered across views

```php
<!-- users/index.php -->
<style>.user-badge { color: red; }</style>

<!-- products/index.php -->
<style>.user-badge { color: blue; }</style>  <!-- Conflict! -->
```

### ✅ GOOD: Everything in `admin-pages.css`

```css
/* admin-pages.css — THE ONLY file to edit */
.badge-danger  { /* Banned users, delete buttons */ }
.badge-success { /* Active users, save buttons */ }
.date-badge    { /* Timestamp display */ }
```

---

## Reusable UI Components

Every admin page is built from the same atomic components:

| Component | Class | Defined In |
|---|---|---|
| Page wrapper | `.custom-card` | `admin-pages.css` |
| Form groups | `.form-section` | `admin-pages.css` |
| Filter bar | `.dt-filters` | `admin-pages.css` |
| Table wrapper | `.table-wrapper` | `admin-pages.css` |
| Timestamp | `.date-badge` | `admin-pages.css` |
| Alert toasts | `SwalHelper.*` | `swal_helper.js` |

**To build a new admin page:**
1. Copy the HTML structure from an existing page (e.g., `giftcodes.php`).
2. Change the data and column names.
3. Done. CSS and JS are already loaded globally.

---

## Model Inheritance

```
core/Model.php          ← Base class (all(), find(), create(), update(), delete())
    ├── app/Models/User.php      ← Just: protected $table = 'users';
    ├── app/Models/Product.php   ← Just: protected $table = 'products';
    └── app/Models/Category.php  ← Just: protected $table = 'categories';
```

You get full CRUD by setting ONE property. No repeated SQL.

---

## Controller Inheritance

```
core/Controller.php     ← Base class (view(), json(), redirect(), post())
    ├── AuthController.php
    ├── Admin/UserController.php
    ├── Admin/ProductController.php
    └── Admin/GiftcodeController.php
```

---

## Helper Functions (Centralized)

Global helpers live in `hethong/config.php`:

| Function | Purpose |
|---|---|
| `timeAgo($datetime)` | Convert timestamp to relative time ("2 giờ trước") |
| `url($path)` | Generate full URL from path |
| `sendTele($message)` | Send Telegram notification |
| `bannd($status)` | Convert ban status to badge HTML |

> If a function is used in 2+ places, it belongs in `hethong/config.php` or `app/Helpers/`.

---

## Checklist Before Writing Code

- [ ] Does this logic already exist somewhere? → **Reuse it**.
- [ ] Am I adding CSS? → **Put it in `admin-pages.css`**.
- [ ] Am I adding a JS helper? → **Put it in `swal_helper.js` or a shared file**.
- [ ] Am I writing raw SQL in a controller? → **Consider adding it to the Model**.
- [ ] Am I copy-pasting HTML structure? → **Use the standard component template**.
