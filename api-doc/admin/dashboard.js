/**
 * @api {get} /v1/admin/dashboard Get overview statistics
 * @apiName GetDashboardOverview
 * @apiGroup Admin Dashboard
 * @apiPermission Admin
 * @apiDescription Get overview statistics including total users, locations, ratings, and views.
 * (Lấy thống kê tổng quan bao gồm tổng số người dùng, địa điểm, đánh giá và lượt xem)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Overview statistics data.
 * @apiSuccess {Number} data.total_users Total number of users.
 * @apiSuccess {Number} data.total_locations Total number of locations.
 * @apiSuccess {Number} data.total_ratings Total number of ratings.
 * @apiSuccess {Number} data.total_views Total number of views across all locations.
 */

/**
 * @api {get} /v1/admin/reports/locations Get location reports
 * @apiName GetLocationReports
 * @apiGroup Admin Dashboard
 * @apiPermission Admin
 * @apiDescription Get location statistics grouped by category and district.
 * (Lấy thống kê địa điểm theo danh mục và quận)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [from] Start date (YYYY-MM-DD).
 * @apiQuery {String} [to] End date (YYYY-MM-DD).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of location statistics.
 * @apiSuccess {Number} data.category_id Category ID.
 * @apiSuccess {String} data.district District name.
 * @apiSuccess {Number} data.count Number of locations.
 * @apiSuccess {Object} data.category Category details.
 */

/**
 * @api {get} /v1/admin/reports/ratings Get rating reports
 * @apiName GetRatingReports
 * @apiGroup Admin Dashboard
 * @apiPermission Admin
 * @apiDescription Get rating statistics grouped by date and status.
 * (Lấy thống kê đánh giá theo ngày và trạng thái)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [from] Start date (YYYY-MM-DD).
 * @apiQuery {String} [to] End date (YYYY-MM-DD).
 * @apiQuery {String} [status] Filter by status (pending, approved, rejected).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of rating statistics.
 * @apiSuccess {String} data.date Date of ratings.
 * @apiSuccess {String} data.status Rating status.
 * @apiSuccess {Number} data.count Number of ratings.
 */

/**
 * @api {get} /v1/admin/reports/users Get user reports
 * @apiName GetUserReports
 * @apiGroup Admin Dashboard
 * @apiPermission Admin
 * @apiDescription Get new user statistics grouped by month for a specific year.
 * (Lấy thống kê người dùng mới theo tháng trong một năm)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {Number} [year] Year to filter (default: current year).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data User statistics data.
 * @apiSuccess {Number} data.year The year of the report.
 * @apiSuccess {Object[]} data.stats List of monthly statistics.
 * @apiSuccess {Number} data.stats.month Month number (1-12).
 * @apiSuccess {Number} data.stats.count Number of new users.
 */

/**
 * @api {get} /v1/admin/reports/points Get point transaction reports
 * @apiName GetPointReports
 * @apiGroup Admin Dashboard
 * @apiPermission Admin
 * @apiDescription Get point transaction statistics grouped by type and date.
 * (Lấy thống kê giao dịch điểm theo loại và ngày)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [from] Start date (YYYY-MM-DD).
 * @apiQuery {String} [to] End date (YYYY-MM-DD).
 * @apiQuery {String} [type] Filter by transaction type (purchase, spend, bonus, refund).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of point transaction statistics.
 * @apiSuccess {String} data.date Date of transactions.
 * @apiSuccess {String} data.type Transaction type.
 * @apiSuccess {Number} data.count Number of transactions.
 * @apiSuccess {Number} data.total_amount Total amount of points.
 */

/**
 * @api {get} /api/v1/admin/dashboard/stats Get Dashboard Stats
 * @apiName GetDashboardStats
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns total counts for users, tours, bookings and revenue.
 *
 * @apiSampleRequest /api/v1/admin/dashboard/stats
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "total_users": 120,
 *     "total_tours": 45,
 *     "total_bookings": 300,
 *     "total_revenue": 15000000
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/dashboard/revenue Get Revenue Statistics
 * @apiName GetDashboardRevenue
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns revenue grouped by period.
 *
 * @apiParam (Query) {String="day","week","month","year"} [period=month] Grouping period.
 * @apiParam (Query) {String} [from] Start date (YYYY-MM-DD).
 * @apiParam (Query) {String} [to] End date (YYYY-MM-DD).
 *
 * @apiSampleRequest /api/v1/admin/dashboard/revenue
 */

/**
 * @api {get} /api/v1/admin/dashboard/top-tours Get Top Tours
 * @apiName GetDashboardTopTours
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns top tours ranked by booking count.
 *
 * @apiParam (Query) {Number{1-50}} [limit=10] Number of tours to return.
 * @apiParam (Query) {String} [from] Start date (YYYY-MM-DD).
 * @apiParam (Query) {String} [to] End date (YYYY-MM-DD).
 *
 * @apiSampleRequest /api/v1/admin/dashboard/top-tours
 */

/**
 * @api {get} /api/v1/admin/dashboard/top-locations Get Top Locations
 * @apiName GetDashboardTopLocations
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns top locations ranked by favorite and view count.
 *
 * @apiParam (Query) {Number{1-50}} [limit=10] Number of locations to return.
 *
 * @apiSampleRequest /api/v1/admin/dashboard/top-locations
 */

/**
 * @api {get} /api/v1/admin/dashboard/user-growth Get User Growth
 * @apiName GetDashboardUserGrowth
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns new user count grouped by month for a given year.
 *
 * @apiParam (Query) {Number} [year] Year to report (default: current year).
 *
 * @apiSampleRequest /api/v1/admin/dashboard/user-growth
 */

/**
 * @api {get} /api/v1/admin/dashboard/booking-trend Get Booking Trend
 * @apiName GetDashboardBookingTrend
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns daily booking count for the last N days.
 *
 * @apiParam (Query) {Number{1-365}} [days=30] Number of days to look back.
 *
 * @apiSampleRequest /api/v1/admin/dashboard/booking-trend
 */

/**
 * @api {get} /api/v1/admin/reports/bookings Get Booking Reports
 * @apiName GetBookingReports
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns booking report grouped by status and date.
 *
 * @apiParam (Query) {String} [from] Start date (YYYY-MM-DD).
 * @apiParam (Query) {String} [to] End date (YYYY-MM-DD).
 * @apiParam (Query) {String="pending","confirmed","completed","cancelled"} [status] Filter by booking status.
 * @apiParam (Query) {String="pending","paid","refunded","failed"} [payment_status] Filter by payment status.
 *
 * @apiSampleRequest /api/v1/admin/reports/bookings
 */

/**
 * @api {get} /api/v1/admin/reports/revenue-detail Get Revenue Detail Report
 * @apiName GetRevenueDetailReport
 * @apiGroup Admin Dashboard
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns detailed revenue report grouped by tour.
 *
 * @apiParam (Query) {String} [from] Start date (YYYY-MM-DD).
 * @apiParam (Query) {String} [to] End date (YYYY-MM-DD).
 *
 * @apiSampleRequest /api/v1/admin/reports/revenue-detail
 */
