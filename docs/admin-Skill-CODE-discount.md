# Module Skill: Discount / Giftcode Management

> Implementation guide for the Discount Code (Mã Giảm Giá) admin module.
> Reference standard: `02-css-ui-design-system.md` and `05-clean-code-oop.md`.

---

## Overview

The Discount module is the **gold standard** for admin UI. All other modules should follow this exact structure.

### Files

| File | Type | Route |
|---|---|---|
| `views/admin/finance/giftcodes.php` | List (Index) | `GET /admin/finance/giftcodes` |
| `views/admin/finance/add_giftcode.php` | Add (Create) | `GET/POST /admin/finance/giftcodes/add` |
| `views/admin/finance/edit_giftcode.php` | Edit (Update) | `GET/POST /admin/finance/giftcodes/edit/{id}` |
| `views/admin/finance/giftcode_log.php` | Log (History) | `GET /admin/finance/giftcodes/log/{id}` |
| `app/Controllers/Admin/GiftcodeController.php` | Controller | — |

---

## Page: List (`giftcodes.php`)

### Structure
```
section.content > container-fluid > card.custom-card
    → card-header (title: "QUẢN LÝ MÃ GIẢM GIÁ")
    → dt-filters
        → row: search inputs + "Xóa Lọc" button + "Tạo mã mới" button
        → top-filter: SHOW dropdown + SORT BY DATE dropdown
    → card-body > table-wrapper > table#giftTable
```

### Table Columns
`MÃ GIẢM GIÁ | SẢN PHẨM ÁP DỤNG | SỐ LƯỢNG | ĐÃ SỬ DỤNG | GIẢM | THỜI GIAN | THAO TÁC`

### Key Features
- Custom `dt-filters` replaces DataTables native search.
- Date filtering via `daterangepicker` + dropdown (7/15/30 days).
- Delete uses `SwalHelper` confirmation → AJAX `$.post` → reload.
- Timestamps use `.date-badge` with `timeAgo()` tooltip.

---

## Page: Add (`add_giftcode.php`)

### Structure
```
section.content > container-fluid > row > col-12 > card.custom-card
    → card-header (title: "TẠO MÃ GIẢM GIÁ MỚI")
    → form
        → card-body
            → form-section: "Thông tin mã giảm giá"
                → row: Giftcode input (col-md-8) + Random button
                → row: Discount % (col-md-4)
                → Usage count input
            → form-section: "Điều kiện áp dụng"
                → Select2 multi-select (products)
                → row: Min order (col-md-6) + Max order (col-md-6)
        → card-footer: Cancel + Save buttons
```

### Key Features
- `generateCode()` JS creates 6-char random alphanumeric codes.
- Select2 for multi-product selection with `allowClear`.
- `.form-label-req` adds red `*` to required fields.

---

## Page: Edit (`edit_giftcode.php`)

Identical structure to Add page, but pre-fills values from database.

### Differences from Add
- Form action posts to `/edit/{id}` instead of `/add`.
- Existing `product_ids` are pre-selected in Select2.
- Title changes to "CHỈNH SỬA MÃ GIẢM GIÁ".

---

## Page: Log (`giftcode_log.php`)

### Structure
```
section.content > container-fluid > row > col-12 > card.custom-card
    → card-header (title: "NHẬT KÝ MÃ: {CODE}")
    → dt-filters
        → row: Username search + Date picker + "Xóa Lọc" button
        → top-filter: SHOW + SORT BY DATE
    → card-body > table-wrapper > table#logTable
```

### Table Columns
`STT | USERNAME | THÔNG TIN ĐƠN HÀNG | THỜI GIAN`

### Key Features
- `parseActivityLog()` PHP function extracts order ID and product name from raw log text.
- Same DataTables pattern as the list page with custom date filtering.

---

## Controller Pattern (`GiftcodeController.php`)

```php
class GiftcodeController extends Controller {
    public function index()    → List all codes, pass to view
    public function add()      → GET: show form | POST: validate & insert
    public function edit($id)  → GET: show form | POST: validate & update
    public function log($id)   → Show usage history for a specific code
    public function delete()   → AJAX: delete code, return JSON
}
```
