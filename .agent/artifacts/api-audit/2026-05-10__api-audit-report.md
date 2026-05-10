# API Audit Report — DanangTrip API

> Date: 2026-05-10
> Auditor: Antigravity Agent
> Sources: `DATN_Tài liệu/docs/api/api_list.md` vs `danangtrip-api/routes/api.php`
> Tổng API trong tài liệu: **184 endpoints** (46 Public + 41 User + 97 Admin)

---

## 1. Tình trạng tận dụng tài liệu trong Skill Sets

### Hiện trạng
- **Bộ skill `danangtrip-web` và `danangtrip-admin`** chỉ trỏ đến `src/constants/endpoints.ts` và `src/config/api.ts` — **CHƯA** tham chiếu đến `DATN_Tài liệu/docs/api/api_list.md`.
- File `api_list.md` (37KB, 422 dòng) là **nguồn chân lý duy nhất** chứa: mô tả từng endpoint, params request, bảng DB bị ảnh hưởng, branch Git, link test script.
- **Kết quả**: Agent AI khi phân tích màn hình (skill 01) và tạo API contract (skill 03) phải tự suy diễn thay vì đọc tài liệu chuẩn → nguy cơ sai sót cao.

### Khuyến nghị ngay lập tức
Thêm vào `Files bắt buộc đọc trước` của **skill 01 và skill 03** (cả admin lẫn web):
```
- d:/DATN/DATN_Tài liệu/docs/api/api_list.md (nguồn chân lý về API)
```

---

## 2. Gap Analysis: Tài liệu vs Code thực tế

### ✅ Đã implement đúng (Routes có trong code)

| Nhóm | Tài liệu | Code | Status |
|------|----------|------|--------|
| Auth (9 endpoints) | ✅ | ✅ | MATCH |
| Categories CRUD | ✅ | ✅ | MATCH |
| Subcategories CRUD | ✅ | ✅ | MATCH |
| Locations (public + admin) | ✅ | ✅ | MATCH |
| Tours (public + admin) | ✅ | ✅ | MATCH |
| Tour Categories | ✅ | ✅ | MATCH |
| Tour Schedules | ✅ | ✅ | MATCH |
| Ratings (public + user + admin) | ✅ | ✅ | MATCH |
| Blog (public) | ✅ | ✅ | MATCH |
| Blog Posts Admin CRUD | ✅ | ✅ | MATCH |
| Blog Categories Admin | ✅ | ✅ | MATCH |
| Bookings (user) | ✅ | ✅ | MATCH |
| Bookings (admin) - list/show/status/export | ✅ | ✅ | MATCH |
| Payments (user + admin) | ✅ | ✅ | MATCH |
| Admin Users CRUD | ✅ | ✅ | MATCH |
| Admin Dashboard | ✅ | ✅ | MATCH |
| Tags & Amenities | ✅ | ✅ | MATCH |
| Contacts | ✅ | ✅ | MATCH |
| Upload | ✅ | ✅ | MATCH |
| Notifications | ✅ | ✅ | MATCH |

---

### ❌ SAI SÓT / MISSING ENDPOINTS

#### BUG-01: Admin Booking confirm/complete/cancel thiếu routes riêng
- **Tài liệu định nghĩa** 3 routes riêng biệt:
  - `POST /admin/bookings/{id}/confirm`
  - `POST /admin/bookings/{id}/complete`
  - `POST /admin/bookings/{id}/cancel`
- **Code thực tế** chỉ có: `PATCH /admin/bookings/{id}/status`
- **Controller** (`Admin/BookingController.php`): chỉ implement `index`, `export`, `show`, `updateStatus`, `statusCounts` — KHÔNG có `confirm()`, `complete()`, `cancel()`.
- **Impact**: Admin không thể confirm/cancel đơn qua action riêng (phải dùng status PATCH chung) — frontend admin bị thiếu workflow rõ ràng.
- **Mức độ**: 🔴 HIGH — ảnh hưởng UX admin dashboard

#### BUG-02: Favorites check route sai path
- **Tài liệu**: `GET /user/favorites/check/{location_id}` (path param)
- **Code thực tế**: `GET /user/favorites/check` (query param `?location_id=`)
- **Impact**: Frontend web đang gọi sai endpoint path.
- **Mức độ**: 🟡 MEDIUM — cần đồng bộ tài liệu hoặc sửa code

#### BUG-03: CONFIG, WEATHER, HEALTH endpoints không có trong code
- **Tài liệu** định nghĩa:
  - `GET /config` — cấu hình website
  - `GET /weather` — thời tiết Đà Nẵng
  - `GET /health` — server health
- **Code thực tế**: Không có route nào trong 3 endpoints này tại `routes/api.php`.
- **Impact**: Frontend không thể lấy config website hay weather — các tính năng này không hoạt động.
- **Mức độ**: 🟡 MEDIUM

#### BUG-04: User Search History endpoints thiếu
- **Tài liệu**:
  - `GET /user/search-history`
  - `DELETE /user/search-history`
- **Code thực tế**: Không có route nào.
- **Impact**: Tính năng lịch sử tìm kiếm của user không hoạt động.
- **Mức độ**: 🟡 MEDIUM

#### BUG-05: User Account Delete endpoint thiếu
- **Tài liệu**: `DELETE /user/account` (xóa tài khoản có confirm)
- **Code thực tế**: Không có route.
- **Mức độ**: 🟡 MEDIUM

#### BUG-06: Admin Locations Stats & Districts route ordering (Route Conflict Risk)
- **Code**:
  ```php
  Route::get('/locations/export', ...);
  Route::get('/locations/stats', ...);
  Route::get('/locations/districts', ...);
  Route::get('/locations', ...);
  Route::put('/locations/{id}', ...);  // ← đặt sau → OK
  ```
- Vì Laravel match top-down, `/locations/stats` PHẢI đặt **trước** `/locations/{id}` — hiện tại đúng nhưng fragile.
- **Recommendation**: Dùng `whereNumber('id')` constraint cho `{id}` routes (đã có ở public routes, cần verify admin routes).
- **Mức độ**: 🟢 LOW (hiện tại không lỗi nhưng cần chú ý)

#### BUG-07: Admin Tours Export route ordering (Conflict Risk)
- **Code** (line 315): `Route::get('/tours/export', ...)` đặt SAU các routes `{id}` — Laravel sẽ match `/tours/export` vào `{id}=export` **TRƯỚC** nếu không có `whereNumber('id')`.
- **Verify**: Line 308-310 dùng `whereNumber('id')` → hiện tại OK.
- Nhưng nếu ai thêm route mới mà quên `whereNumber` → conflict.
- **Mức độ**: 🟢 LOW

#### BUG-08: Admin Ratings Export route ordering (Conflict Risk)
- **Code** (line 284): `Route::get('/ratings/export', ...)` đặt SAU `Route::get('/ratings', ...)` — OK vì không có `{id}` conflict ở đây.
- Tuy nhiên nên đặt `/export` trước các routes `{id}` theo convention.
- **Mức độ**: 🟢 LOW

#### BUG-09: location-categories alias route
- **Code** (line 70): `Route::get('/location-categories', [CategoryController::class, 'index'])` — là alias, không có trong tài liệu chính thức.
- Nếu frontend web dùng `/location-categories`, tài liệu không phản ánh → có thể gây nhầm lẫn.
- **Mức độ**: 🟢 LOW (chỉ cần document)

---

### ⚠️ INCONSISTENCY giữa tài liệu và code

| # | Tài liệu | Code | Loại |
|---|----------|------|------|
| I-01 | `DELETE /user/favorites/{location_id}` (path param) | `DELETE /user/favorites` (body param) | Path mismatch |
| I-02 | `bookings` filter dùng `from_date`, `to_date` | Code dùng `from_date`, `to_date` (cần verify request class) | Cần verify |
| I-03 | `/admin/reports/locations` (line 232) | Code có nhưng không trong tài liệu summary table | Thiếu trong summary |

---

## 3. Chất lượng tổng thể API

### ✅ Điểm mạnh
- **Route structure rõ ràng**: Public / Protected / Admin tách biệt 3 tầng.
- **Throttle rate limiting**: Đầy đủ `throttle:api.strict`, `api.auth`, `api.standard`, `api.admin`, `api.exports`, `api.uploads`, `api.callbacks` — rất tốt.
- **Service layer**: Tất cả business logic trong `app/Services/` — Controller chỉ là thin transport. ✅
- **whereNumber() constraints**: Phần lớn `{id}` routes có constraint — giảm routing conflict.
- **Regex constraints**: Booking code, transaction code có regex pattern — tốt.
- **Export functionality**: Đủ các endpoints export Excel cho tất cả data.

### 🟡 Điểm cần cải thiện
1. **Missing 3 semantic booking actions** (BUG-01) — nên tách confirm/complete/cancel thành method riêng thay vì generic `updateStatus`.
2. **Missing /config, /weather, /health** (BUG-03).
3. **Missing User search history + account delete** (BUG-04, BUG-05).
4. **Inconsistent path param vs query param** giữa tài liệu và code (I-01, BUG-02).

---

## 4. Kế hoạch sửa lỗi (Priority)

| Priority | Bug | Action | Effort |
|----------|-----|--------|--------|
| 🔴 P1 | BUG-01: Admin booking confirm/complete/cancel | Thêm 3 routes + methods trong `Admin/BookingController` | 2h |
| 🟡 P2 | BUG-02: Favorites check path mismatch | Đồng bộ: sửa route thành `/check/{location_id}` HOẶC cập nhật tài liệu | 30m |
| 🟡 P2 | BUG-03: /config, /weather, /health | Implement 3 controllers + routes | 3h |
| 🟡 P2 | I-01: DELETE favorites path mismatch | Sửa `DELETE /user/favorites` thành `/user/favorites/{location_id}` HOẶC cập nhật tài liệu | 30m |
| 🟡 P3 | BUG-04: User search history | Implement routes + ProfileController methods | 1h |
| 🟡 P3 | BUG-05: User account delete | Implement route + ProfileController method | 1h |
| 🟢 P4 | BUG-06..09: Route ordering | Review và reorder + add missing whereNumber | 1h |

---

## 5. Khuyến nghị cập nhật Skill Sets

### Cần thêm vào skill 01 và skill 03 (cả admin và web)

**Thêm vào "Files bắt buộc đọc trước":**
```
- d:/DATN/DATN_Tài liệu/docs/api/api_list.md
  → Nguồn chân lý về 184 endpoints: mô tả, params, DB tables, branch Git, test scripts
```

**Thêm rule vào skill 01 (Screen Analysis):**
```
- Khi map Data Fields → đọc api_list.md để biết chính xác:
  - Tên param (vd: from_date vs date_from)
  - Bảng DB nào bị ảnh hưởng
  - Required/Optional fields
  - Throttle class áp dụng
```

**Thêm rule vào skill 03 (Types & API Contract):**
```
- Trước khi tạo API module: đối chiếu api_list.md để xác nhận:
  - Method + path chính xác (không tự suy diễn)
  - Params names (không nhầm snake_case)
  - Auth level (Public / User / Admin)
  - Kết quả operation trả về gì
```

### Thêm vào endpoints.ts của admin
Các endpoint admin chưa có trong `src/constants/endpoints.ts`:
- `ADMIN_BOOKINGS_CONFIRM: (id) => /api/v1/admin/bookings/${id}/confirm` *(sau khi BUG-01 fix)*
- `ADMIN_DASHBOARD_OVERVIEW: /api/v1/admin/dashboard`
- `ADMIN_REPORTS_LOCATIONS: /api/v1/admin/reports/locations`
- `ADMIN_REPORTS_RATINGS: /api/v1/admin/reports/ratings`

---

## 6. Tóm tắt Executive

| Metric | Giá trị |
|--------|---------|
| Tổng endpoints trong tài liệu | 184 |
| Đã implement trong code | ~175 (~95%) |
| Missing/Bug nghiêm trọng | 5 (BUG-01..05) |
| Inconsistency | 3 (I-01..03) |
| Route ordering risk | 4 (BUG-06..09) |
| Chất lượng code (Service layer) | ⭐⭐⭐⭐⭐ |
| Coverage tài liệu trong skill sets | ❌ Chưa tận dụng |
| Khuyến nghị tổng thể | Sửa BUG-01 trước khi ship admin |
