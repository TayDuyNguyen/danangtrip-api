/**
 * @api {get} /api/v1/admin/tour-schedules Admin List Tour Schedules
 * @apiName AdminListTourSchedules
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Returns a list of all tour schedules with filters.
 *
 * @apiQuery {Number} [tour_id] Filter by tour ID
 * @apiQuery {String} [start_date] Filter by start date (YYYY-MM-DD)
 * @apiQuery {String="active","inactive"} [status] Filter by status
 *
 * @apiSampleRequest /api/v1/admin/tour-schedules
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "tour_id": 1,
 *       "start_date": "2026-05-01",
 *       "max_people": 20,
 *       "current_people": 5,
 *       "status": "active"
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/admin/tour-schedules/:id Admin Get Tour Schedule Detail
 * @apiName AdminGetTourScheduleDetail
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Returns detailed information about a specific tour schedule including associated bookings.
 *
 * @apiParam {Number} id Schedule ID
 *
 * @apiSampleRequest /api/v1/admin/tour-schedules/1
 */

/**
 * @api {post} /api/v1/admin/tours/:id/schedules Admin Create Tour Schedule
 * @apiName AdminCreateTourSchedule
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Creates a new schedule for a specific tour.
 *
 * @apiParam {Number} id Tour ID
 * @apiBody {String} start_date Start date (YYYY-MM-DD, must be future)
 * @apiBody {Number} max_people Maximum number of people
 * @apiBody {Number} [price] Custom price for this schedule (optional, defaults to tour base price)
 * @apiBody {String="active","inactive"} [status] Initial status
 *
 * @apiSampleRequest /api/v1/admin/tours/1/schedules
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Tour schedule created successfully",
 *   "data": { "id": 1, "start_date": "2026-05-01" }
 * }
 */

/**
 * @api {put} /api/v1/admin/tour-schedules/:id Admin Update Tour Schedule
 * @apiName AdminUpdateTourSchedule
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates an existing tour schedule.
 *
 * @apiParam {Number} id Schedule ID
 * @apiBody {String} [start_date] Start date (YYYY-MM-DD)
 * @apiBody {Number} [max_people] Maximum number of people
 * @apiBody {Number} [price] Custom price for this schedule
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/tour-schedules/1
 */

/**
 * @api {delete} /api/v1/admin/tour-schedules/:id Admin Delete Tour Schedule
 * @apiName AdminDeleteTourSchedule
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Deletes a tour schedule if it has no associated bookings.
 *
 * @apiParam {Number} id Schedule ID
 *
 * @apiSampleRequest /api/v1/admin/tour-schedules/1
 */

/**
 * @api {patch} /api/v1/admin/tour-schedules/:id/status Admin Update Tour Schedule Status
 * @apiName AdminUpdateTourScheduleStatus
 * @apiGroup AdminTourSchedules
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates the status of a tour schedule.
 *
 * @apiParam {Number} id Schedule ID
 * @apiBody {String="active","inactive"} status New status
 *
 * @apiSampleRequest /api/v1/admin/tour-schedules/1/status
 */
