/**
 * @api {get} /api/v1/admin/users Get Admin Users
 * @apiName GetAdminUsers
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns a paginated list of all users with filters.
 *
 * @apiParam (Query) {String} [search] Search full_name, email, or username.
 * @apiParam (Query) {String="admin","partner","user"} [role] Filter by role.
 * @apiParam (Query) {String="active","banned"} [status] Filter by status.
 * @apiParam (Query) {Number{1..}} [page=1] Page number.
 * @apiParam (Query) {Number{1-100}} [per_page=15] Items per page.
 *
 * @apiSampleRequest /api/v1/admin/users
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "current_page": 1,
 *     "data": [
 *       {
 *         "id": 1,
 *         "username": "john",
 *         "email": "john@example.com",
 *         "full_name": "John Doe",
 *         "role": "user",
 *         "status": "active"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/users/:id Get Admin User Detail
 * @apiName GetAdminUserDetail
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns specific user details with counts.
 *
 * @apiParam {Number} id User id
 *
 * @apiSampleRequest /api/v1/admin/users/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "id": 1,
 *     "username": "john",
 *     "full_name": "John Doe",
 *     "ratings_count": 5,
 *     "point_transactions_count": 2
 *   }
 * }
 */

/**
 * @api {post} /api/v1/admin/users Create Admin User
 * @apiName CreateAdminUser
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Creates a new user account.
 *
 * @apiBody {String} username Unique username
 * @apiBody {String} email Unique email
 * @apiBody {String} password Password
 * @apiBody {String} full_name User's full name
 * @apiBody {String} [phone] Phone number
 * @apiBody {String="admin","partner","user"} [role="user"] Role
 *
 * @apiSampleRequest /api/v1/admin/users
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 */

/**
 * @api {put} /api/v1/admin/users/:id Update Admin User
 * @apiName UpdateAdminUser
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Updates user account information.
 *
 * @apiParam {Number} id User id
 * @apiBody {String} [full_name] New full name
 * @apiBody {String} [email] New email
 * @apiBody {String="admin","partner","user"} [role] New role
 *
 * @apiSampleRequest /api/v1/admin/users/1
 */

/**
 * @api {patch} /api/v1/admin/users/:id/status Update Admin User Status
 * @apiName UpdateAdminUserStatus
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Toggle between active and banned.
 *
 * @apiParam {Number} id User id
 * @apiBody {String="active","banned"} status New status
 *
 * @apiSampleRequest /api/v1/admin/users/1/status
 */

/**
 * @api {patch} /api/v1/admin/users/:id/role Update Admin User Role
 * @apiName UpdateAdminUserRole
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Change user role.
 *
 * @apiParam {Number} id User id
 * @apiBody {String="admin","partner","user"} role New role
 *
 * @apiSampleRequest /api/v1/admin/users/1/role
 */

/**
 * @api {delete} /api/v1/admin/users/:id Delete Admin User
 * @apiName DeleteAdminUser
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Deletes a user account.
 *
 * @apiParam {Number} id User id
 *
 * @apiSampleRequest /api/v1/admin/users/1
 */

/**
 * @api {get} /api/v1/admin/users/:id/bookings Get User Booking History
 * @apiName GetAdminUserBookings
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns paginated booking history for a specific user.
 *
 * @apiParam {Number} id User id
 * @apiParam (Query) {Number{1..}} [page=1] Page number.
 * @apiParam (Query) {Number{1-100}} [per_page=15] Items per page.
 *
 * @apiSampleRequest /api/v1/admin/users/1/bookings
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "current_page": 1,
 *     "data": [
 *       {
 *         "id": 1,
 *         "booking_code": "BK-20240101-001",
 *         "status": "confirmed",
 *         "total_price": 500000,
 *         "created_at": "2024-01-01T00:00:00.000000Z"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/users/:id/ratings Get User Ratings
 * @apiName GetAdminUserRatings
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns paginated ratings submitted by a specific user.
 *
 * @apiParam {Number} id User id
 * @apiParam (Query) {Number{1..}} [page=1] Page number.
 * @apiParam (Query) {Number{1-100}} [per_page=15] Items per page.
 *
 * @apiSampleRequest /api/v1/admin/users/1/ratings
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "current_page": 1,
 *     "data": [
 *       {
 *         "id": 1,
 *         "score": 5,
 *         "comment": "Great place!",
 *         "status": "approved",
 *         "created_at": "2024-01-01T00:00:00.000000Z"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/users/export Export Users to Excel
 * @apiName ExportAdminUsers
 * @apiGroup Admin User
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Exports the users list as an Excel (.xlsx) file.
 *
 * @apiParam (Query) {String="admin","staff","user"} [role] Filter by role.
 * @apiParam (Query) {String="active","banned"} [status] Filter by status.
 *
 * @apiSampleRequest /api/v1/admin/users/export
 *
 * @apiSuccessExample {binary} Success-Response:
 * HTTP/1.1 200 OK
 * Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
 * Content-Disposition: attachment; filename="users_20240101_120000.xlsx"
 */
