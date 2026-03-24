/**
 * @api {get} /v1/admin/users List users
 * @apiName GetAdminUsers
 * @apiGroup Admin User
 * @apiPermission Admin
 * @apiDescription Get a paginated list of all users with filters and search.
 * (Lấy danh sách người dùng có phân trang kèm bộ lọc và tìm kiếm)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [q] Search query for full_name, email, or username.
 * @apiQuery {String} [role] Filter by role (admin, partner, user).
 * @apiQuery {String} [status] Filter by status (active, banned).
 * @apiQuery {Number} [page=1] Page number.
 * @apiQuery {Number} [per_page=15] Items per page.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated users data.
 */

/**
 * @api {get} /v1/admin/users/:id User detail
 * @apiName GetAdminUserDetail
 * @apiGroup Admin User
 * @apiPermission Admin
 * @apiDescription Get specific user details with counts of ratings and point transactions.
 * (Lấy chi tiết người dùng cụ thể kèm số lượng đánh giá và giao dịch điểm)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id User's unique ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data User detail object with `ratings_count` and `point_transactions_count`.
 */

/**
 * @api {patch} /v1/admin/users/:id/status Update user status
 * @apiName UpdateAdminUserStatus
 * @apiGroup Admin User
 * @apiPermission Admin
 * @apiDescription Toggle user status between active and banned.
 * (Kích hoạt hoặc khóa tài khoản người dùng)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id User's unique ID.
 * @apiBody {String} status New status (active, banned).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */

/**
 * @api {patch} /v1/admin/users/:id/role Update user role
 * @apiName UpdateAdminUserRole
 * @apiGroup Admin User
 * @apiPermission Admin
 * @apiDescription Change user role between admin, partner, and user.
 * (Thay đổi vai trò của người dùng)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id User's unique ID.
 * @apiBody {String} role New role (admin, partner, user).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */

/**
 * @api {delete} /v1/admin/users/:id Delete user account
 * @apiName DeleteAdminUser
 * @apiGroup Admin User
 * @apiPermission Admin
 * @apiDescription Delete a user account and its related data (CASCADE).
 * (Xóa vĩnh viễn tài khoản người dùng và các dữ liệu liên quan)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id User's unique ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */
