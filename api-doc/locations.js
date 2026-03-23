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

/**
 * @api {post} /api/v1/admin/locations Admin Create Location
 * @apiName AdminCreateLocation
 * @apiGroup AdminLocations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Creates a new location.
 *
 * @apiBody {String} name Location name
 * @apiBody {String} [slug] Slug (unique). If empty, it will be generated.
 * @apiBody {Number} category_id Category id
 * @apiBody {Number} [subcategory_id] Subcategory id
 * @apiBody {String} description Description
 * @apiBody {String} [short_description] Short description
 * @apiBody {String} address Address
 * @apiBody {String} district District
 * @apiBody {String} [ward] Ward
 * @apiBody {Number} latitude Latitude
 * @apiBody {Number} longitude Longitude
 * @apiBody {String} [phone] Phone
 * @apiBody {String} [email] Email
 * @apiBody {String} [website] Website URL
 * @apiBody {Object} [opening_hours] Opening hours object
 * @apiBody {Number} [price_min] Min price
 * @apiBody {Number} [price_max] Max price
 * @apiBody {Number{1-4}} [price_level] Price level
 * @apiBody {String} [thumbnail] Thumbnail URL/path
 * @apiBody {Object[]} [images] Images array
 * @apiBody {String} [video_url] Video URL
 * @apiBody {String="active","inactive","pending"} [status] Status
 * @apiBody {Boolean} [is_featured] Featured
 *
 * @apiSampleRequest /api/v1/admin/locations
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Location created successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "New Location",
 *     "slug": "new-location"
 *   }
 * }
 */

/**
 * @api {put} /api/v1/admin/locations/:id Admin Update Location
 * @apiName AdminUpdateLocation
 * @apiGroup AdminLocations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates an existing location.
 *
 * @apiParam {Number} id Location id
 * @apiBody {String} [name] Location name
 * @apiBody {String} [slug] Slug (unique). If omitted and name changes, slug can be generated.
 * @apiBody {Number} [category_id] Category id
 * @apiBody {Number} [subcategory_id] Subcategory id
 * @apiBody {String} [description] Description
 * @apiBody {String} [address] Address
 * @apiBody {String} [district] District
 * @apiBody {Number} [latitude] Latitude
 * @apiBody {Number} [longitude] Longitude
 * @apiBody {String="active","inactive","pending"} [status] Status
 * @apiBody {Boolean} [is_featured] Featured
 *
 * @apiSampleRequest /api/v1/admin/locations/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Location updated successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "Updated Location",
 *     "slug": "updated-location"
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/admin/locations/:id Admin Delete Location
 * @apiName AdminDeleteLocation
 * @apiGroup AdminLocations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Deletes a location.
 *
 * @apiParam {Number} id Location id
 *
 * @apiSampleRequest /api/v1/admin/locations/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Location deleted successfully",
 *   "data": null
 * }
 */

/**
 * @api {patch} /api/v1/admin/locations/:id/status Admin Update Location Status
 * @apiName AdminUpdateLocationStatus
 * @apiGroup AdminLocations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates location status.
 *
 * @apiParam {Number} id Location id
 * @apiBody {String="active","inactive","pending"} status New status
 *
 * @apiSampleRequest /api/v1/admin/locations/1/status
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Status updated successfully",
 *   "data": {
 *     "id": 1,
 *     "status": "active"
 *   }
 * }
 */

/**
 * @api {patch} /api/v1/admin/locations/:id/featured Admin Toggle Featured
 * @apiName AdminToggleLocationFeatured
 * @apiGroup AdminLocations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Toggles location featured status.
 *
 * @apiParam {Number} id Location id
 * @apiBody {Boolean} is_featured Featured flag
 *
 * @apiSampleRequest /api/v1/admin/locations/1/featured
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Featured status toggled successfully",
 *   "data": {
 *     "id": 1,
 *     "is_featured": true
 *   }
 * }
 */
