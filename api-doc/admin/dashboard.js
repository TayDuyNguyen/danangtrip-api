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
