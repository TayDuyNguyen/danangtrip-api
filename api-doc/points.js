/**
 * @api {get} /v1/user/points Get point balance
 * @apiName GetPointBalance
 * @apiGroup Points
 * @apiPermission User
 * @apiDescription Get the current point balance of the authenticated user.
 * (Lấy số dư điểm hiện tại của người dùng đã xác thực)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Data object.
 * @apiSuccess {Number} data.point_balance Current point balance.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": {
 *         "point_balance": 1500
 *       }
 *     }
 */

/**
 * @api {get} /v1/user/points/transactions Get transaction history
 * @apiName GetPointTransactions
 * @apiGroup Points
 * @apiPermission User
 * @apiDescription Get the point transaction history of the authenticated user.
 * (Lấy lịch sử giao dịch điểm của người dùng đã xác thực)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {String} [type] Filter by type (purchase, spend, bonus, refund).
 * @apiQuery {String} [status] Filter by status (pending, completed, failed).
 * @apiQuery {Number} [page=1] Page number.
 * @apiQuery {Number} [per_page=15] Items per page.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated data.
 * @apiSuccess {Object[]} data.data List of transactions.
 * @apiSuccess {Number} data.data.id Transaction ID.
 * @apiSuccess {String} data.data.transaction_code Unique code.
 * @apiSuccess {String} data.data.type Transaction type.
 * @apiSuccess {Number} data.data.amount Points changed.
 * @apiSuccess {Number} data.data.balance_before Balance before.
 * @apiSuccess {Number} data.data.balance_after Balance after.
 * @apiSuccess {String} data.data.payment_method Payment method.
 * @apiSuccess {String} data.data.status Transaction status.
 * @apiSuccess {String} data.data.description Description.
 * @apiSuccess {String} data.data.created_at Created time.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": {
 *         "current_page": 1,
 *         "data": [
 *           {
 *             "id": 1,
 *             "transaction_code": "PTABCD12345",
 *             "type": "purchase",
 *             "amount": 5000,
 *             "balance_before": 0,
 *             "balance_after": 5000,
 *             "payment_method": "momo",
 *             "status": "completed",
 *             "description": "Purchase points via MOMO",
 *             "created_at": "2024-03-24T10:00:00.000000Z"
 *           }
 *         ],
 *         "total": 1
 *       }
 *     }
 */

/**
 * @api {post} /v1/user/points/purchase Purchase points
 * @apiName PurchasePoints
 * @apiGroup Points
 * @apiPermission User
 * @apiDescription Purchase points using a payment method.
 * (Nạp điểm bằng phương thức thanh toán)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {Number} amount Amount of points to purchase (min 1000).
 * @apiBody {String} payment_method Payment method (momo, vnpay, bank).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Data object.
 * @apiSuccess {Object} data.transaction Created transaction object.
 * @apiSuccess {Number} data.new_balance Updated point balance.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Points purchased successfully.",
 *       "data": {
 *         "transaction": {
 *           "id": 2,
 *           "transaction_code": "PTXYZ789",
 *           "type": "purchase",
 *           "amount": 2000,
 *           "balance_before": 5000,
 *           "balance_after": 7000,
 *           "payment_method": "vnpay",
 *           "status": "completed",
 *           "created_at": "2024-03-24T11:00:00.000000Z"
 *         },
 *         "new_balance": 7000
 *       }
 *     }
 */
