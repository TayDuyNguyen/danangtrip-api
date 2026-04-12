/**
 * @api {get} /api/v1/tours Get Tours
 * @apiName GetTours
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns active tours with filters and pagination.
 *
 * @apiParam (Query) {Number} [category_id] Category id
 * @apiParam (Query) {String} [search] Search text (name)
 * @apiParam (Query) {String="active","inactive","pending"} [status] Status filter (default: active)
 * @apiParam (Query) {String="created_at","price","rating_avg"} [order_by] Order field
 * @apiParam (Query) {String="asc","desc"} [order_dir] Order direction
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 * @apiParam (Query) {Number{1..}} [page] Page number
 *
 * @apiSampleRequest /api/v1/tours
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
 *         "name": "Da Nang City Tour",
 *         "slug": "da-nang-city-tour",
 *         "category_id": 1,
 *         "price": 500000,
 *         "rating_avg": 4.5,
 *         "rating_count": 10,
 *         "status": "active"
 *       }
 *     ],
 *     "per_page": 10,
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/tours/featured Get Featured Tours
 * @apiName GetFeaturedTours
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns featured tours.
 *
 * @apiParam (Query) {Number{1-100}} [limit] Max items
 *
 * @apiSampleRequest /api/v1/tours/featured
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Featured Tour",
 *       "slug": "featured-tour",
 *       "price": 1000000,
 *       "rating_avg": 4.8,
 *       "is_featured": true
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/tours/hot Get Hot Tours
 * @apiName GetHotTours
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns hot tours.
 *
 * @apiParam (Query) {Number{1-100}} [limit] Max items
 *
 * @apiSampleRequest /api/v1/tours/hot
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Hot Tour",
 *       "slug": "hot-tour",
 *       "price": 1200000,
 *       "rating_avg": 4.9,
 *       "is_hot": true
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/tours/:slug Get Tour Detail (By Slug)
 * @apiName GetTourDetailBySlug
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns tour detail by slug including category and future schedules.
 *
 * @apiParam {String} slug Tour slug
 *
 * @apiSampleRequest /api/v1/tours/da-nang-city-tour
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "id": 1,
 *     "name": "Da Nang City Tour",
 *     "slug": "da-nang-city-tour",
 *     "description": "Full day tour...",
 *     "price": 500000,
 *     "category": { "id": 1, "name": "City Tour" },
 *     "schedules": [
 *       { "id": 1, "start_date": "2026-05-01", "max_people": 20, "current_people": 5 }
 *     ]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/tours/:id/schedules Get Tour Schedules
 * @apiName GetTourSchedules
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns future schedules for a specific tour.
 *
 * @apiParam {Number} id Tour id
 *
 * @apiSampleRequest /api/v1/tours/1/schedules
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
 *       "current_people": 5
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/tours/:id/ratings Get Tour Ratings
 * @apiName GetTourRatings
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns approved ratings for a tour with pagination.
 *
 * @apiParam {Number} id Tour id
 * @apiParam (Query) {Number{1..}} [page] Page number
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 *
 * @apiSampleRequest /api/v1/tours/1/ratings
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
 *         "user_id": 1,
 *         "score": 5,
 *         "comment": "Excellent!",
 *         "user": { "id": 1, "full_name": "John Doe" }
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/tours/:id/rating-stats Get Tour Rating Stats
 * @apiName GetTourRatingStats
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns star distribution (1-5) for a tour.
 *
 * @apiParam {Number} id Tour id
 *
 * @apiSampleRequest /api/v1/tours/1/rating-stats
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "1": 0,
 *     "2": 1,
 *     "3": 2,
 *     "4": 5,
 *     "5": 10
 *   }
 * }
 */

/**
 * @api {post} /api/v1/tours/:id/check-availability Check Tour Availability
 * @apiName CheckTourAvailability
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Checks if there are slots available for a tour on a specific date.
 *
 * @apiParam {Number} id Tour id
 * @apiBody {Date} date Date to check (YYYY-MM-DD, must be today or future)
 *
 * @apiSampleRequest /api/v1/tours/1/check-availability
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "is_available": true
 *   }
 * }
 */
