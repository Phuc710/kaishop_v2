# Module Skill: User Management

> Implementation guide for the User Management (Thành Viên) admin module.
> Reference standard: `02-css-ui-design-system.md` and `05-clean-code-oop.md`.

---

## Overview

The User Management module handles listing, editing, and financial operations on user accounts.

### Files

| File | Type | Route |
|---|---|---|
| `views/admin/users/index.php` | List (Index) | `GET /admin/users` |
| `views/admin/users/edit.php` | Edit (Profile) | `GET/POST /admin/users/edit/{username}` |
| `app/Controllers/Admin/UserController.php` | Controller | — |
| `app/Models/User.php` | Model | `$table = 'users'` |

---

## Page: List (`index.php`)

### Structure
```
section.content > container-fluid
    → row.mb-3: Summary Info Boxes (3 columns)
        → col-md-4: Total Users (bg-primary, fa-users)
        → col-md-4: Banned Users (bg-danger, fa-user-slash)
        → col-md-4: Admin Users (bg-success, fa-user-shield)
    → card.custom-card
        → card-header (title: "DANH SÁCH THÀNH VIÊN")
        → dt-filters
            → row: Keyword search + Status dropdown + "Xóa Lọc" button
            → top-filter: SHOW dropdown
        → card-body > table-wrapper > table#datatable1
```

### Table Columns
`ID | USERNAME | EMAIL | SỐ DƯ | TỔNG NẠP | TRẠNG THÁI | NGÀY TẠO | THAO TÁC`

### Key Features
- **Summary Info Boxes** at the top show real-time statistics computed in the controller.
- **Status column** uses Badge rendering: `.badge-success` (Active) / `.badge-danger` (Banned).
- **TỔNG NẠP** column pulls from `tong_nap` database field.
- Status filter dropdown allows filtering by Active/Banned.
- Delete uses SweetAlert confirmation → redirect with `?delete=` param.

---

## Page: Edit (`edit.php`)

### Structure
```
section.content > container-fluid > row > col-12 > card.custom-card
    → card-header (title: "HỒ SƠ THÀNH VIÊN: {username}")
    → card-body
        → row (2 columns)
            → col-xl-7: form-section "Thông tin tài khoản"
                → row: Username (col-md-6) + Email (col-md-6)
                → row: Status dropdown (col-md-6) + Level dropdown (col-md-6)
                → Save button (right-aligned)
            → col-xl-5: form-section "Quản lý tài chính"
                → Current balance display (h3, text-success)
                → row: Add Money card (col-md-6) + Subtract Money card (col-md-6)
    → card-footer: "Quay lại danh sách" button (centered)
```

### Financial Sub-Cards

The Add/Subtract Money forms use a lightweight nested card pattern:

```html
<div class="card border-success shadow-none mb-0 h-100">
    <div class="card-header bg-success text-white py-2">
        <h6 class="card-title mb-0 font-weight-bold">
            <i class="fas fa-plus-circle mr-1"></i> Add Money
        </h6>
    </div>
    <div class="card-body p-3">
        <form>
            <input class="form-control form-control-sm" name="tien_cong" required>
            <textarea class="form-control form-control-sm" name="rs_cong" required></textarea>
            <button class="btn btn-success btn-sm w-100">Confirm</button>
        </form>
    </div>
</div>
```

> Same pattern for Subtract Money but with `border-danger` / `bg-danger` / `btn-danger`.

---

## Controller Pattern (`UserController.php`)

```php
class UserController extends Controller {
    public function index()            → List all users + compute stats (total, banned, admin)
    public function edit($username)    → GET: show user profile form
    public function update($username)  → POST: save user info changes
    public function addMoney($username)→ POST: add balance + log to history_nap_bank
    public function subMoney($username)→ POST: subtract balance + log to history_nap_bank
    public function delete()           → POST: delete user via AJAX
}
```

### Statistics Computation (in `index()`)

```php
$totalUsers = count($users);
$bannedUsers = 0;
$adminUsers = 0;

foreach ($users as $u) {
    if ($u['bannd'] == 1) $bannedUsers++;
    if ($u['level'] == 9) $adminUsers++;
}
```

These values are passed to the view and rendered in the Info Box summary cards.
