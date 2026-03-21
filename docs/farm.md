ChatGPT Pro Business Farm Management System
Xây dựng hệ thống quản lý ChatGPT Pro Business Farm tích hợp vào kaishop_v2. Mỗi "farm" là 1 tổ chức OpenAI Business gồm 1 admin + tối đa 4 slot user. Khi user mua hàng, họ nhập Gmail → hệ thống auto invite bằng Admin API key của farm được chọn → cronjob 1 phút sync + bảo vệ farm (revoke/kick vi phạm).

Proposed Changes
1. Database Schema
[NEW] chatgpt/database/chatgpt_migration.sql
Tạo 7 bảng mới:

Bảng	Mô tả
chatgpt_farms	Farm list: admin_email, admin_api_key (encrypted), seat_total=4, seat_used, status
chatgpt_orders	Đơn mua: customer_email, farm_id, status=pending/inviting/active/failed/revoked
chatgpt_allowed_invites	Whitelist invite hợp lệ: order_id, farm_id, target_email, invite_id, status
chatgpt_farm_members_snapshot	Snapshot member hiện tại trong farm: email, role, source=approved/detected_unknown
chatgpt_farm_invites_snapshot	Snapshot invite hiện tại: invite_id, email, status
chatgpt_violations	Bản ghi vi phạm: farm_id, email, type, action_taken
chatgpt_audit_logs	Audit trail đầy đủ: action, actor_email, target_email, result, meta_json
2. Core Services & Models
[NEW] app/Services/ChatGptFarmService.php
Multi-farm OpenAI API wrapper. Nhận $farm array (có admin_api_key), wrap các call:

listMembers(farm)
listInvites(farm)
createInvite(farm, email, role='reader')
revokeInvite(farm, invite_id)
removeMember(farm, user_id)
Key được decrypt trước khi dùng (via CryptoService nếu available).

[NEW] app/Models/ChatGptFarm.php
getActiveFarms() — farms có status=active và seat_used < seat_total
getBestAvailableFarm() — ưu tiên farm ít người nhất
incrementSeatUsed(id) / decrementSeatUsed(id)
create(data), 
update(id, data)
, 
getAll()
, 
getById(id)
[NEW] app/Models/ChatGptOrder.php
create(data) — tạo đơn: customer_email, farm_id, product_code, status
getByEmail(email) — check duplicate active order
getAll(filters)
, 
getById(id)
, updateStatus(id, status)
[NEW] app/Models/ChatGptAllowedInvite.php
create(order_id, farm_id, target_email, invite_id)
isAllowed(farm_id, email) ↔ check invite hợp lệ
updateStatus(id, status) — pending/accepted/expired/revoked
getByFarm(farm_id) — lấy tất cả invite hợp lệ của 1 farm
[NEW] app/Models/ChatGptSnapshot.php
upsertMember(farm_id, email, role, source)
upsertInvite(farm_id, invite_id, email, status)
getMembersForFarm(farm_id)
getInvitesForFarm(farm_id)
markMemberGone(farm_id, email) / markInviteGone(farm_id, invite_id)
[NEW] app/Models/ChatGptAuditLog.php
log(farm_id, action, actor, target, result, reason, meta)
getForFarm(farm_id, limit) — with pagination
3. Public Routes — Luồng mua hàng
[NEW] app/Controllers/ChatGptController.php
GET /chatgpt/pro-1-month-add-farm → hiển thị trang sản phẩm có form nhập Gmail
POST /chatgpt/pro-1-month-add-farm/order → xử lý order:
Validate Gmail
Check email chưa có active order
Tìm farm còn chỗ (getBestAvailableFarm)
Gọi OpenAI invite API
Lưu chatgpt_orders (status=inviting)
Lưu chatgpt_allowed_invites
Tăng seat_used
Log SYSTEM_INVITE_CREATED
Redirect → order success
[NEW] views/chatgpt/product.php
Trang sản phẩm ChatGPT Pro đẹp, có form nhập Gmail, giá, thông tin.

[NEW] views/chatgpt/order_success.php
Trang xác nhận đặt hàng thành công, hướng dẫn user check Gmail.

4. Admin Panel — Quản lý toàn bộ
[NEW] app/Controllers/Admin/ChatGptAdminController.php
Các action:

farms() — Danh sách farm
farmAdd() / farmStore() — Thêm farm (validate API key trước khi lưu)
farmEdit(id) / farmUpdate(id) — Sửa farm
orders() — Danh sách orders
members() — Snapshot members tất cả farms
invites() — Snapshot invites
logs() — Audit logs với filter theo farm/action/date
Views (trong views/admin/chatgpt/):
farms.php — table: farm_name, admin_email, seat_used/seat_total, status, last_sync
farms_add.php — form: tên, admin gmail, api key (test live)
orders.php — table: order_code, customer_email, farm, invite status, created_at
members.php — table: email, farm, approved/unknown, joined_at
invites.php — table: email, farm, status, allowed/unknown
logs.php — table: thời gian, farm, action, actor, target, result, reason
5. Cron Guard
[NEW] chatgpt/cron/guard.php
Chạy bởi cron mỗi 1 phút: php /path/to/chatgpt/cron/guard.php

Flow:

Foreach farm (active):
  1. Fetch current members from OpenAI API
  2. Fetch current invites from OpenAI API
  3. Update snapshots in DB
  4. Compare invites vs allowed_invites:
     - Invite không trong allowed_invites → REVOKE + log INVITE_REVOKED_UNAUTHORIZED
  5. Compare members vs snapshot+allowed:
     - Member không có invite hợp lệ → REMOVE + log MEMBER_REMOVED_UNAUTHORIZED
  6. Rule C (strict mode):
     - Nếu invite lạ/member lạ → tìm member active trong farm có thể đã kéo → KICK sponsor + log
  7. Orders cron check:
     - invite đã accepted → update order.status = active
  8. Update farm.last_sync_at
Secret guard: file check CRON_SECRET env hoặc CLI-only mode (php_sapi_name() === 'cli').

6. Internal API Endpoints
[MODIFY] 
config/routes.php
Thêm routes:

GET  /chatgpt/pro-1-month-add-farm         → ChatGptController@product
POST /chatgpt/pro-1-month-add-farm/order   → ChatGptController@order
GET  /admin/chatgpt/farms                  → Admin\ChatGptAdminController@farms
GET  /admin/chatgpt/farms/add              → Admin\ChatGptAdminController@farmAdd
POST /admin/chatgpt/farms/add              → Admin\ChatGptAdminController@farmStore
GET  /admin/chatgpt/farms/edit/{id}        → Admin\ChatGptAdminController@farmEdit
POST /admin/chatgpt/farms/edit/{id}        → Admin\ChatGptAdminController@farmUpdate
GET  /admin/chatgpt/orders                 → Admin\ChatGptAdminController@orders
GET  /admin/chatgpt/members                → Admin\ChatGptAdminController@members
GET  /admin/chatgpt/invites                → Admin\ChatGptAdminController@invites
GET  /admin/chatgpt/logs                   → Admin\ChatGptAdminController@logs
POST /api/chatgpt/sync-farm                → Api\ChatGptApiController@syncFarm
7. Security Notes
IMPORTANT

Admin API key encrypt trước khi lưu DB (CryptoService đã có sẵn trong project)
Cron file chỉ chạy từ CLI hoặc cần secret header
API key không bao giờ expose ra frontend
Toàn bộ logic invite/kick chỉ server-side
Verification Plan
Manual Testing
Test 1: Thêm Farm

Đăng nhập admin (username/password admin level 9)
Truy cập /admin/chatgpt/farms/add
Nhập tên farm, admin gmail, admin API key hợp lệ
Submit → kiểm tra farm xuất hiện trong /admin/chatgpt/farms
Kiểm tra seat_used=0, seat_total=4, status=active
Test 2: Mua hàng & Auto Invite

Truy cập /chatgpt/pro-1-month-add-farm
Nhập Gmail hợp lệ (ví dụ test Gmail)
Submit → xem trang order success
Vào /admin/chatgpt/orders → thấy đơn mới, status=inviting
Vào /admin/chatgpt/invites → thấy invite pending
Vào /admin/chatgpt/logs → thấy log SYSTEM_INVITE_CREATED
Test 3: Cron Guard

Chạy thủ công: php c:\xampp\htdocs\kaishop_v2\chatgpt\cron\guard.php
Kiểm tra output log (hoặc xem /admin/chatgpt/logs)
Nếu không có vi phạm → thấy "No violations detected"
Nếu có invite lạ → thấy INVITE_REVOKED_UNAUTHORIZED trong log
Test 4: Duplicate Email Check

Mua hàng với 1 Gmail đã có active order
Hệ thống phải hiện lỗi "Email này đã có đơn hàng đang active"