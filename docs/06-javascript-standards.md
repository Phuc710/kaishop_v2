# 06 — JavaScript Standards

> How to organize, write, and maintain JavaScript across the project.

---

## File Organization

| File | Scope | Description |
|---|---|---|
| `assets/js/jquery.js` | Global | jQuery 3.x — loaded on every page |
| `assets/js/bootstrap.js` | Global | Bootstrap 4 JS (modals, tooltips, etc.) |
| `assets/js/sweetalert.js` | Global | SweetAlert2 library |
| `assets/js/swal_helper.js` | Global | ⭐ Centralized alert & toast helpers |
| `assets/js/datatables.js` | Admin | DataTables library |
| `assets/js/main.js` | Public | Public-facing page logic |
| `assets/js/script.js` | Public | Extended public scripts |
| View `<script>` blocks | Page | Page-specific DataTable init, filters |

---

## The SwalHelper Pattern

**Never** call `Swal.fire()` directly with raw config. Use the centralized helper:

```javascript
// ✅ GOOD — Consistent across the app
SwalHelper.toast('Saved successfully!', 'success');
SwalHelper.toast('Something went wrong', 'error');

// ❌ BAD — Inconsistent config everywhere
Swal.fire({ title: 'OK', icon: 'success', timer: 1500 });
```

### Available Methods

```javascript
SwalHelper.toast(message, type)      // Auto-dismiss notification
SwalHelper.success(title, message)   // Success modal
SwalHelper.error(title, message)     // Error modal
SwalHelper.confirm(options)          // Confirmation dialog
```

---

## DataTables Initialization Standard

Every admin list page follows this exact pattern:

```javascript
let dt;
$(document).ready(function () {
    dt = $('#myTable').DataTable({
        // Custom DOM: table + footer (info + pagination)
        dom: 't<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center"p>>',
        responsive: true,
        autoWidth: false,
        order: [[0, "desc"]],
        pageLength: 10,
        columnDefs: [
            { orderable: false, targets: [-1] }  // Last column (actions)
        ],
        language: {
            sZeroRecords: 'Không tìm thấy dữ liệu',
            sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
            sInfoEmpty: 'Không có dữ liệu',
            sInfoFiltered: '(lọc từ _MAX_)',
            oPaginate: { sPrevious: '‹', sNext: '›' }
        }
    });

    // Hook: Custom search input
    $('#f-keyword').on('input keyup', function () {
        dt.search(this.value.trim()).draw();
    });

    // Hook: Page length dropdown
    $('#f-length').change(function () {
        dt.page.len($(this).val()).draw();
    });

    // Hook: Clear all filters
    $('#btn-clear').click(function () {
        $('input[id^="f-"], select[id^="f-"]').val('');
        dt.search('').columns().search('');
        dt.page.len(10).order([0, 'desc']).draw();
    });
});
```

> ⚠️ The `dom` property hides DataTables' built-in search. We use `.dt-filters` instead.

---

## Delete Confirmation Pattern

```javascript
function deleteItem(id) {
    Swal.fire({
        title: 'Confirm delete?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash"></i> Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('/admin/example/delete', { id: id }, function (res) {
                if (res.success) {
                    SwalHelper.toast('Deleted!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    SwalHelper.toast(res.message || 'Error', 'error');
                }
            }).fail(() => SwalHelper.toast('Server error', 'error'));
        }
    });
}
```

---

## Tooltip Initialization

Globally initialized in `admin/foot.php`:

```javascript
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
```

No need to init per-page. Just add `data-toggle="tooltip" title="..."` to any element.

---

## Rules

1. **No inline `onclick` for complex logic** — Use event listeners where possible.
2. **Third-party libraries** go in `assets/js/` as standalone files.
3. **Page-specific JS** goes in a `<script>` block at the bottom of the view file.
4. **Shared helpers** go in `assets/js/swal_helper.js` or a new shared file.
5. **Always use `let`/`const`** — Never `var`.
