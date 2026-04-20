# Database Schema Annotated

Tai lieu nay tong hop schema dang duoc su dung trong du an `danangtrip-api`, doi chieu voi thiet ke ban gui va ghi chu cac diem quan trong de maintain nhanh hon.

## Pham vi hien tai

- Bang nghiep vu chinh: `users`, `categories`, `subcategories`, `locations`, `tags`, `location_tags`, `amenities`, `location_amenities`, `tour_categories`, `tours`, `tour_schedules`, `bookings`, `booking_items`, `payments`, `ratings`, `rating_images`, `favorites`, `views`, `search_logs`, `notifications`, `contacts`, `blog_categories`, `blog_posts`, `blog_post_categories`, `refresh_tokens`
- Bang he thong Laravel: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`
- Luu y: du an hien tai da mo rong `favorites` va `views` de ho tro ca `tour_id`, khong chi `location_id`

## So do nghiep vu hien tai

### 1. `users`

- `id`: khoa chinh
- `username`, `email`: unique, dung cho dang nhap
- `password`: mat khau da hash
- `full_name`: ten hien thi
- `avatar`, `phone`, `birthdate`, `gender`, `city`: thong tin ho so
- `role`: mac dinh `user`, da duoc index de tach quyen nhanh
- `status`: hien tai dang dung `pending | active | blocked`, khac voi ban thiet ke `active | banned`
- `email_verified_at`, `last_login_at`: phuc vu xac thuc va audit
- `created_at`, `updated_at`

Khac biet quan trong:

- Migration hien tai khong tao `remember_token`

### 2. `categories`

- Danh muc cha cua dia diem
- Co `slug`, `icon`, `description`, `image`, `sort_order`, `status`
- `status` da index de loc nhanh danh muc dang hoat dong

### 3. `subcategories`

- Thuoc `categories` qua `category_id`
- Dung de chia nho dia diem theo nhom con

### 4. `locations`

- Bang trung tam cho dia diem du lich/an uong/luu tru
- `category_id`, `subcategory_id`: FK den danh muc
- `district`, `ward`, `latitude`, `longitude`: phuc vu tim kiem theo khu vuc va ban do
- `opening_hours`: kieu JSON
- `price_min`, `price_max`, `price_level`: metadata ve gia
- `avg_rating`, `review_count`, `view_count`, `favorite_count`: so lieu tong hop de sort/filter nhanh
- `images`: dang luu JSON array
- `created_by`: user tao ban ghi

Chi muc dang co:

- `category_id`
- `subcategory_id`
- `district`
- `status`
- `is_featured`
- `avg_rating`
- `view_count`
- Fulltext index tren mot so cot tim kiem da duoc bo sung bang migration rieng

### 5. `tags`

- Nhan gan cho dia diem
- `type` phan loai theo `cuisine | service | feature | atmosphere`

### 6. `location_tags`

- Bang pivot `locations` <-> `tags`
- Unique `(location_id, tag_id)` de tranh gan trung tag

### 7. `amenities`

- Danh sach tien ich cua dia diem
- `category` dung de nhom UI/bo loc

### 8. `location_amenities`

- Bang pivot `locations` <-> `amenities`
- Unique `(location_id, amenity_id)`

### 9. `tour_categories`

- Danh muc cha cua san pham tour
- Co `sort_order`, `status`, `icon`

### 10. `tours`

- San pham tour
- `itinerary`, `inclusions`, `exclusions`, `location_ids`: dang luu JSON
- `price_adult`, `price_child`, `price_infant`: gia co ban
- `discount_percent`: giam gia mac dinh
- `available_from`, `available_to`: khoang thoi gian tour mo ban
- `status`: du an hien tai da chuan hoa ve `active | inactive | sold_out`
- `is_featured`, `is_hot`, `view_count`, `booking_count`: phuc vu homepage va dashboard

Khac biet quan trong:

- Migration goc tao `status = available`, nhung da co migration chuan hoa sang `active`

### 11. `tour_schedules`

- Lich khoi hanh cu the cua tung tour
- Unique `(tour_id, start_date)` de moi ngay chi co mot dot khoi hanh cho mot tour
- `price_*` co the override gia tu bang `tours`
- `status`: `available | full | cancelled`

### 12. `bookings`

- Don dat tour tong
- `booking_code`: ma don unique
- `user_id`: nullable de ho tro guest checkout
- `customer_*`: snapshot thong tin nguoi dat tai thoi diem mua
- `total_amount`, `discount_amount`, `final_amount`, `deposit_amount`: tong tien
- `payment_method`, `payment_status`, `booking_status`: trang thai xu ly
- `booked_at`, `confirmed_at`, `cancelled_at`, `completed_at`: moc thoi gian nghiep vu

### 13. `booking_items`

- Chi tiet tung dong san pham trong booking
- Co lien ket den `tour_id` va `tour_schedule_id`
- `travel_date` va `quantity_*` phuc vu tinh tien, thong ke, doi soat

### 14. `payments`

- Lich su thanh toan theo booking
- `transaction_code`: unique
- `payment_status`: du an hien tai dung `pending | success | failed | refunded`
- `gateway_response`: dang luu JSON

Khac biet quan trong:

- Ban thiet ke mong muon `paid`; code hien tai da chuan hoa sang `success`

### 15. `ratings`

- Danh gia cho `location` hoac `tour`
- `booking_id`: nullable, de rang buoc danh gia sau mua co the siet them o service layer
- `status`: `pending | approved | rejected`
- `approved_by`, `approved_at`: audit duyet danh gia
- `helpful_count`: dem so luot huu ich

Khac biet quan trong:

- Hien tai bang nay luu `image_count`, khong luu `image_urls`
- Anh rating duoc tach sang bang `rating_images`, phu hop hon cho quan ly media
- Mới co unique `(user_id, location_id)` trong migration goc; neu can rang buoc `(user_id, tour_id)` can kiem tra va bo sung bang migration moi neu chua co

### 16. `rating_images`

- Anh dinh kem cho `ratings`
- `sort_order` dung de sap xep

### 17. `favorites`

- Da ho tro luu yeu thich cho ca `location` va `tour`
- `user_id`: FK user
- `location_id`: nullable sau migration bo sung `tour_id`
- `tour_id`: nullable, phuc vu yeu thich tour

Khac biet quan trong:

- Ban thiet ke ban dau chi co `location_id`
- Hien tai schema thuc te co 2 unique index:
    - `favorites_user_location_unique`
    - `favorites_user_tour_unique`

### 18. `blog_categories`

- Danh muc rieng cho blog, tach biet khoi categories dia diem

### 19. `blog_posts`

- Bai viet noi dung
- `author_id`, `status`, `published_at` da duoc index cho trang danh sach

### 20. `blog_post_categories`

- Pivot `blog_posts` <-> `blog_categories`

### 21. `views`

- Ghi nhan luot xem cua user hoac guest
- `session_id`: theo doi guest
- `time_spent`: so giay o lai
- Da mo rong them `tour_id` de track view cho tour

Khac biet quan trong:

- Ban thiet ke ban dau chi co `location_id`
- Hien tai `location_id` da nullable de view co the gan vao `tour_id`

### 22. `search_logs`

- Log tu khoa tim kiem va bo loc da ap dung
- `filters` dang luu JSON
- Da duoc bo sung index tim kiem phu hop cho query log

### 23. `notifications`

- Thong bao he thong cho user
- `data` dang luu JSON/text bo sung
- Index `(user_id, is_read)` phuc vu dem unread va danh sach thong bao

### 24. `contacts`

- Form lien he tu khach
- `status`: `new | read | replied`
- `replied_by`, `replied_at`, `reply`: theo doi xu ly phan hoi

### 25. `refresh_tokens`

- Bang them cua auth hien tai, khong nam trong schema 24 bang goc
- Dung cho flow refresh token rotation
- Cot chinh:
    - `user_id`
    - `token`: hash SHA-256 cua refresh token
    - `expires_at`
    - `used_at`
    - `previous_token_id`: phuc vu reuse detection

## Bang he thong Laravel

### `password_reset_tokens`

- Phuc vu quen mat khau mac dinh cua Laravel

### `sessions`

- Duoc dung vi project dang de `SESSION_DRIVER=database`

### `cache`, `cache_locks`

- Ho tro cache/database lock

### `jobs`, `job_batches`, `failed_jobs`

- Ho tro queue job va theo doi loi

## Nhung diem dang lech so voi thiet ke ban gui

1. `users.status` hien tai la `pending | active | blocked`, chua trung hoan toan voi `active | banned`
2. `users` chua co `remember_token` trong migration hien tai
3. `favorites` va `views` da duoc mo rong them `tour_id`
4. `ratings` tach anh sang `rating_images`, khong luu `image_urls` trong bang chinh
5. `payments.payment_status` da duoc chuan hoa ve `success` thay vi `paid`
6. `tours.status` da duoc chuan hoa ve `active` thay vi `available`
7. Project co them `refresh_tokens` va cac bang he thong Laravel nen tong so bang thuc te lon hon 24

## Nhung phan nen bo sung tiep neu muon schema sat hon ban thiet ke

1. Them migration bo sung `remember_token` cho `users` neu ban can chuc nang nho dang nhap.
2. Chuan hoa enum/trang thai `users.status` va service layer de tranh `blocked`/`banned` dung song song.
3. Kiem tra rang buoc unique `(user_id, tour_id)` o `ratings` neu business yeu cau moi user chi duoc danh gia mot tour mot lan.
4. Neu muon biet ro dia diem nao nam trong tour, can can nhac tach `tours.location_ids` thanh bang pivot `tour_locations`.
5. Neu can bao cao tai chinh chuan hon, co the bo sung `currency`, `gateway_transaction_id`, `refund_amount` cho `payments`.

## File code nen doc cung

- `database/migrations/*`: nguon su that cua schema
- `app/Models/*`: quan he Eloquent hien dang su dung
- `database/seeders/LocationSeeder.php`
- `database/seeders/TourSeeder.php`
- `database/seeders/TourScheduleSeeder.php`
