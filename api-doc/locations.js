/**
 * @api {get} /api/v1/locations Get Locations
 * @apiName GetLocations
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns active locations with filters and pagination.
 *
 * @apiParam (Query) {Number} [category_id] Category id
 * @apiParam (Query) {Number} [subcategory_id] Subcategory id
 * @apiParam (Query) {String} [district] District name
 * @apiParam (Query) {String} [search] Search text (name/address)
 * @apiParam (Query) {Number{1-4}} [price_level] Price level (1-4)
 * @apiParam (Query) {Boolean} [is_featured] Featured filter
 * @apiParam (Query) {String="avg_rating","review_count","view_count","created_at","price_min"} [sort_by] Sort field
 * @apiParam (Query) {String="asc","desc"} [sort_order] Sort direction
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 * @apiParam (Query) {Number{1..}} [page] Page number
 *
 * @apiSampleRequest /api/v1/locations
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
 *         "name": "Example Location",
 *         "slug": "example-location",
 *         "category_id": 1,
 *         "subcategory_id": 10,
 *         "district": "Hai Chau",
 *         "address": "123 Street",
 *         "latitude": 16.0678,
 *         "longitude": 108.2208,
 *         "avg_rating": 4.5,
 *         "review_count": 12,
 *         "view_count": 100,
 *         "price_min": 10000,
 *         "price_max": 50000,
 *         "price_level": 2,
 *         "is_featured": true,
 *         "status": "active"
 *       }
 *     ],
 *     "per_page": 10,
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/locations/featured Get Featured Locations
 * @apiName GetFeaturedLocations
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns featured locations.
 *
 * @apiParam (Query) {Number{1-100}} [limit] Max items
 *
 * @apiSampleRequest /api/v1/locations/featured
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Featured Location",
 *       "slug": "featured-location",
 *       "avg_rating": 4.8,
 *       "is_featured": true,
 *       "status": "active"
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/locations/nearby Get Nearby Locations
 * @apiName GetNearbyLocations
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns nearby active locations using Haversine distance.
 *
 * @apiParam (Query) {Number} [lat] Latitude (-90..90) (required)
 * @apiParam (Query) {Number} [lng] Longitude (-180..180) (required)
 * @apiParam (Query) {Number{0.1-50}} [radius] Radius in kilometers
 * @apiParam (Query) {Number{1-100}} [limit] Max items
 * @apiParam (Query) {String="avg_rating","review_count","view_count","created_at","price_min"} [sort_by] Sort field
 * @apiParam (Query) {String="asc","desc"} [sort_order] Sort direction
 *
 * @apiSampleRequest /api/v1/locations/nearby?lat=16.0678&lng=108.2208
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Nearby Location",
 *       "slug": "nearby-location",
 *       "latitude": 16.0678,
 *       "longitude": 108.2208,
 *       "distance": 1.25,
 *       "status": "active"
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/locations/:slug Get Location Detail (By Slug)
 * @apiName GetLocationDetailBySlug
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns location detail by slug.
 *
 * @apiParam {String} slug Location slug
 *
 * @apiSampleRequest /api/v1/locations/example-location
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "id": 1,
 *     "name": "Example Location",
 *     "slug": "example-location",
 *     "description": "Long description",
 *     "address": "123 Street",
 *     "district": "Hai Chau",
 *     "latitude": 16.0678,
 *     "longitude": 108.2208,
 *     "status": "active"
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Location not found"
 * }
 */

/**
 * @api {get} /api/v1/locations/:id/ratings Get Location Ratings
 * @apiName GetLocationRatings
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns approved ratings for a location with pagination.
 *
 * @apiParam {Number} id Location id
 * @apiParam (Query) {String="created_at","rating"} [sort_by] Sort field
 * @apiParam (Query) {Number{1..}} [page] Page number
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 *
 * @apiSampleRequest /api/v1/locations/1/ratings
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
 *         "location_id": 1,
 *         "user_id": 1,
 *         "rating": 5,
 *         "comment": "Great place!",
 *         "created_at": "2026-03-23T00:00:00.000000Z",
 *         "user": {
 *           "id": 1,
 *           "username": "john",
 *           "full_name": "John Doe"
 *         },
 *         "images": []
 *       }
 *     ],
 *     "per_page": 10,
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {post} /api/v1/locations/:id/view Record Location View
 * @apiName RecordLocationView
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Records a view for a location (increments view_count).
 *
 * @apiParam {Number} id Location id
 * @apiBody {String} [session_id] Optional client/session identifier
 *
 * @apiSampleRequest /api/v1/locations/1/view
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "View recorded",
 *   "data": null
 * }
 */
