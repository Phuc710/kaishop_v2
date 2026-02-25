# Admin Journal OOP Guide

## Muc tieu
- Dung chung 1 khung cho 2 man:
  - `admin/logs/activities`
  - `admin/logs/balance-changes`
- Tach rieng:
  - Data/query logic: `app/Models/AdminJournal.php`
  - Page flow + mapping UI: `app/Controllers/Admin/JournalController.php`
  - Giao dien dung chung: `views/admin/logs/journal.php`

## Kien truc
- `AdminJournal` (Model):
  - Tu nhan biet cot thoi gian (`created_at`/`time`), cot thiet bi (`device`/`user_agent`/...)
  - Build filter chung: `user_id`, `username`, `time_range`, `date_filter`
  - Build filter rieng:
    - Activity: `action`, `ip`, `device`
    - Balance: `reason`
  - Fallback du lieu:
    - Uu tien `lich_su_bien_dong_so_du` neu co
    - Neu khong co, dung `history_nap_bank`

- `JournalController`:
  - `activities()` va `balanceChanges()` chi khai bao config rieng
  - Dung chung:
    - `buildQueryState()`
    - `paginate()`
    - `renderJournal()`
    - map du lieu sang cot hien thi

- `journal.php` (View):
  - 1 form filter chung
  - 1 bang dong theo `columns + rows`
  - 1 block pagination chung
  - 1 block css/js nho dung chung

## Query params
- Chung:
  - `user_id`, `username`, `time_range` (YYYY-MM-DD), `date_filter`, `per_page`, `page`
- Activity:
  - `action`, `ip`, `device`
- Balance:
  - `reason`

## Date filter
- `date_filter=all|today|7days|30days`
- `time_range=YYYY-MM-DD` loc trong ngay do (00:00:00 -> 23:59:59)

## Cach mo rong nhanh
1. Them cot moi trong bang:
   - Them vao `columns` trong controller.
   - Them mapping trong `mapActivityRows()` hoac `mapBalanceRows()`.
2. Them o filter:
   - Them input trong `filters` config.
   - Them dieu kien SQL trong `AdminJournal`.
3. Tao man journal thu 3:
   - Tao action moi trong `JournalController`.
   - Tai su dung `renderJournal()` va view `admin/logs/journal`.

## Luu y
- He thong hien tai co the khong co du lieu `history_nap_bank`; man bien dong se hien 0 row.
- Fallback so du truoc/sau tu `history_nap_bank` la du lieu uoc luong (khong phai ledger chuan).
