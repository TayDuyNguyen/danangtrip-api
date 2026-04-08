/**
 * @api {post} /api/v1/payments/callback Payment callback
 * @apiName PaymentCallback
 * @apiGroup Payments
 * @apiPermission Public
 * @apiDescription Webhook to receive payment results from gateway.
 * (Webhook nhận kết quả thanh toán từ cổng thanh toán)
 *
 * @apiBody {String} transaction_code Unique transaction code.
 * @apiBody {String} status Status of the payment ('success' or 'failed').
 * @apiBody {Object} [gateway_specific] Other gateway-specific data.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Null or success data.
 */

/**
 * @api {post} /api/v1/payments/create Create payment link
 * @apiName CreatePayment
 * @apiGroup Payments
 * @apiPermission Protected
 * @apiDescription Create a payment link for a booking.
 * (Tạo liên kết thanh toán cho đơn đặt chỗ)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {Number} booking_id ID of the booking.
 * @apiBody {String} payment_method Payment method ('momo', 'vnpay', 'zalopay').
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Payment data and URL.
 * @apiSuccess {Object} data.payment Payment record.
 * @apiSuccess {String} data.payment_url URL to redirect the user to the gateway.
 */

/**
 * @api {get} /api/v1/payments/status/{transaction_code} Check payment status
 * @apiName GetPaymentStatus
 * @apiGroup Payments
 * @apiPermission Protected
 * @apiDescription Check the status of a specific payment transaction.
 * (Kiểm tra trạng thái của một giao dịch thanh toán cụ thể)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {String} transaction_code The transaction code.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Payment status information.
 * @apiSuccess {String} data.payment_status Current status ('pending', 'paid', 'failed', 'refunded').
 * @apiSuccess {String} data.transaction_code Transaction code.
 * @apiSuccess {Number} data.booking_id Associated booking ID.
 */

/**
 * @api {post} /api/v1/payments/retry/{booking_code} Retry payment
 * @apiName RetryPayment
 * @apiGroup Payments
 * @apiPermission Protected
 * @apiDescription Retry payment for a booking that hasn't been paid.
 * (Thử thanh toán lại cho một đơn đặt chỗ chưa được thanh toán)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {String} booking_code The unique booking code.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data New payment data and URL.
 */

/**
 * @api {get} /api/v1/admin/payments List all transactions
 * @apiName AdminListPayments
 * @apiGroup Admin Payments
 * @apiPermission Admin
 * @apiDescription Get a list of all payment transactions with filters.
 * (Lấy danh sách tất cả các giao dịch thanh toán với bộ lọc)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [payment_status] Filter by status ('pending', 'paid', 'failed', 'refunded').
 * @apiQuery {String} [payment_gateway] Filter by gateway.
 * @apiQuery {String} [date_from] Start date (YYYY-MM-DD).
 * @apiQuery {String} [date_to] End date (YYYY-MM-DD).
 * @apiQuery {Number} [page] Page number.
 * @apiQuery {Number} [per_page] Items per page.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated list of payments.
 */

/**
 * @api {get} /api/v1/admin/payments/{id} Get transaction detail
 * @apiName AdminGetPayment
 * @apiGroup Admin Payments
 * @apiPermission Admin
 * @apiDescription Get detailed information about a specific payment transaction.
 * (Lấy thông tin chi tiết về một giao dịch thanh toán cụ thể)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {Number} id Payment ID.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Detailed payment record.
 */

/**
 * @api {post} /api/v1/admin/payments/{id}/refund Refund a payment
 * @apiName AdminRefundPayment
 * @apiGroup Admin Payments
 * @apiPermission Admin
 * @apiDescription Process a refund for a paid transaction.
 * (Xử lý hoàn tiền cho một giao dịch đã thanh toán)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {Number} id Payment ID.
 * @apiBody {String} refund_reason Reason for the refund.
 *
 * @apiSuccess {Number} code HTTP Status Code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Null or success data.
 */

/**
 * @api {get} /api/v1/admin/payments/export Export transactions to Excel
 * @apiName AdminExportPayments
 * @apiGroup Admin Payments
 * @apiPermission Admin
 * @apiDescription Download an Excel file containing filtered payment transactions.
 * (Tải xuống tệp Excel chứa các giao dịch thanh toán đã lọc)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [payment_status] Filter by status.
 * @apiQuery {String} [payment_gateway] Filter by gateway.
 * @apiQuery {String} [date_from] Start date.
 * @apiQuery {String} [date_to] End date.
 *
 * @apiSuccess {File} excel Excel file download.
 */
