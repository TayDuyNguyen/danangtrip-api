/**
 * @api {get} /api/v1/admin/bookings Admin List Bookings
 * @apiName AdminListBookings
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Returns paginated bookings with filters.
 *
 * @apiQuery {String} [search] Search by booking_code, user name/email, or tour name
 * @apiQuery {String="pending","confirmed","cancelled","completed"} [booking_status] Filter by status
 * @apiQuery {String="pending","paid","failed","refunded"} [payment_status] Filter by payment status
 * @apiQuery {String} [from_date] Filter by booked_at (YYYY-MM-DD)
 * @apiQuery {String} [to_date] Filter by booked_at (YYYY-MM-DD)
 * @apiQuery {Number} [per_page=10] Items per page
 * @apiQuery {String} [sort_by=created_at] Sort field
 * @apiQuery {String="asc","desc"} [sort_order=desc] Sort direction
 *
 * @apiSampleRequest /api/v1/admin/bookings
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
 *         "payment_status": "pending",
 *         "total_amount": 1000000,
 *         "user": { "id": 1, "full_name": "John Doe" }
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/bookings/export Admin Export Bookings
 * @apiName AdminExportBookings
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Exports filtered bookings to CSV/Excel.
 *
 * @apiSampleRequest /api/v1/admin/bookings/export
 *
 * @apiSuccess {File} file Exported file
 */

/**
 * @api {get} /api/v1/admin/bookings/:id Admin Get Booking Detail
 * @apiName AdminGetBookingDetail
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Returns detailed information about a specific booking including items and user.
 *
 * @apiParam {Number} id Booking ID
 *
 * @apiSampleRequest /api/v1/admin/bookings/1
 */

/**
 * @api {patch} /api/v1/admin/bookings/:id/status Admin Update Booking Status
 * @apiName AdminUpdateBookingStatus
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates booking status directly.
 *
 * @apiParam {Number} id Booking ID
 * @apiBody {String="pending","confirmed","cancelled","completed"} status New status
 *
 * @apiSampleRequest /api/v1/admin/bookings/1/status
 */

/**
 * @api {post} /api/v1/admin/bookings/:id/confirm Admin Confirm Booking
 * @apiName AdminConfirmBooking
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Confirms a booking and sends confirmation email.
 *
 * @apiParam {Number} id Booking ID
 *
 * @apiSampleRequest /api/v1/admin/bookings/1/confirm
 */

/**
 * @api {post} /api/v1/admin/bookings/:id/cancel Admin Cancel Booking
 * @apiName AdminCancelBooking
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Cancels a booking with reason.
 *
 * @apiParam {Number} id Booking ID
 * @apiBody {String} [cancellation_reason] Reason for cancellation
 *
 * @apiSampleRequest /api/v1/admin/bookings/1/cancel
 */

/**
 * @api {post} /api/v1/admin/bookings/:id/complete Admin Complete Booking
 * @apiName AdminCompleteBooking
 * @apiGroup AdminBookings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Marks a booking as completed.
 *
 * @apiParam {Number} id Booking ID
 *
 * @apiSampleRequest /api/v1/admin/bookings/1/complete
 */
