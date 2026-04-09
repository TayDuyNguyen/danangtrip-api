/**
 * @api {post} /api/v1/bookings/calculate Calculate Booking Price
 * @apiName CalculateBooking
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Calculates the total price and breakdown for a potential booking.
 *
 * @apiBody {Number} tour_schedule_id Tour schedule ID
 * @apiBody {Number} adult_count Number of adults
 * @apiBody {Number} [child_count=0] Number of children
 * @apiBody {String} [promo_code] Optional promotional code
 *
 * @apiSampleRequest /api/v1/bookings/calculate
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "total_amount": 1000000,
 *     "discount_amount": 0,
 *     "final_amount": 1000000,
 *     "deposit_amount": 500000
 *   }
 * }
 */

/**
 * @api {post} /api/v1/bookings Store Booking
 * @apiName StoreBooking
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Creates a new booking.
 *
 * @apiBody {Number} tour_schedule_id Tour schedule ID
 * @apiBody {Number} adult_count Number of adults
 * @apiBody {Number} [child_count=0] Number of children
 * @apiBody {String} customer_name Full name of the customer
 * @apiBody {String} customer_email Email of the customer
 * @apiBody {String} customer_phone Phone of the customer
 * @apiBody {String} [customer_address] Address of the customer
 * @apiBody {String} [customer_note] Special requests/notes
 * @apiBody {String="momo","vnpay","zalopay","bank_transfer","cash"} payment_method Payment method
 *
 * @apiSampleRequest /api/v1/bookings
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Booking created successfully",
 *   "data": {
 *     "id": 1,
 *     "booking_code": "BK-2026-0001",
 *     "final_amount": 1000000
 *   }
 * }
 */

/**
 * @api {get} /api/v1/user/bookings Get User Bookings
 * @apiName GetUserBookings
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Returns paginated bookings for the authenticated user.
 *
 * @apiQuery {String="pending","confirmed","cancelled","completed"} [booking_status] Filter by status
 * @apiQuery {Number} [per_page=10] Items per page
 *
 * @apiSampleRequest /api/v1/user/bookings
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
 *         "booking_code": "BK-2026-0001",
 *         "booking_status": "pending",
 *         "final_amount": 1000000,
 *         "booked_at": "2026-04-08 10:00:00"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/user/bookings/:id Get Booking Detail
 * @apiName GetUserBookingDetail
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Returns detailed information about a specific booking.
 *
 * @apiParam {Number} id Booking ID
 *
 * @apiSampleRequest /api/v1/user/bookings/1
 */

/**
 * @api {get} /api/v1/user/bookings/code/:booking_code Get Booking by Code
 * @apiName GetUserBookingByCode
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Returns detailed information about a booking using its unique code.
 *
 * @apiParam {String} booking_code Unique booking code
 *
 * @apiSampleRequest /api/v1/user/bookings/code/BK-2026-0001
 */

/**
 * @api {get} /api/v1/user/bookings/:id/invoice Download Invoice
 * @apiName GetUserBookingInvoice
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Returns a PDF invoice for the booking.
 *
 * @apiParam {Number} id Booking ID
 *
 * @apiSampleRequest /api/v1/user/bookings/1/invoice
 *
 * @apiSuccess {File} file PDF invoice file
 */

/**
 * @api {post} /api/v1/user/bookings/:id/cancel Cancel Booking
 * @apiName CancelUserBooking
 * @apiGroup Bookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Cancels a booking if it's still in 'pending' status.
 *
 * @apiParam {Number} id Booking ID
 * @apiBody {String} [cancellation_reason] Reason for cancellation
 *
 * @apiSampleRequest /api/v1/user/bookings/1/cancel
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Booking cancelled successfully",
 *   "data": null
 * }
 */
