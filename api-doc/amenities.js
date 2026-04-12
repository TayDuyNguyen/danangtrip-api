/**
 * @api {get} /api/v1/amenities Get Amenities
 * @apiName GetAmenities
 * @apiGroup Amenities
 * @apiVersion 1.0.0
 * @apiPermission public
 *
 * @apiDescription Public endpoint. Returns all amenities with optional category filter.
 *
 * @apiParam (Query) {String="connectivity","parking","comfort","payment"} [category] Filter by amenity category.
 *
 * @apiSampleRequest /api/v1/amenities
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Free Wi-Fi",
 *       "icon": "wifi",
 *       "category": "connectivity",
 *       "created_at": "2024-01-01T00:00:00.000000Z",
 *       "updated_at": "2024-01-01T00:00:00.000000Z"
 *     }
 *   ]
 * }
 *
 * @apiErrorExample {json} Validation-Error:
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 422,
 *   "message": "Validation failed",
 *   "errors": {
 *     "category": ["The selected amenity category is invalid."]
 *   }
 * }
 */

/**
 * @api {post} /api/v1/admin/amenities Create Amenity
 * @apiName CreateAdminAmenity
 * @apiGroup Amenities
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Creates a new amenity.
 *
 * @apiBody {String} name Amenity name (max 100, unique).
 * @apiBody {String} [icon] Icon identifier (max 100).
 * @apiBody {String="connectivity","parking","comfort","payment"} category Amenity category.
 *
 * @apiSampleRequest /api/v1/admin/amenities
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Amenity created successfully.",
 *   "data": {
 *     "id": 1,
 *     "name": "Free Wi-Fi",
 *     "icon": "wifi",
 *     "category": "connectivity",
 *     "created_at": "2024-01-01T00:00:00.000000Z",
 *     "updated_at": "2024-01-01T00:00:00.000000Z"
 *   }
 * }
 *
 * @apiErrorExample {json} Validation-Error:
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 422,
 *   "message": "Validation failed",
 *   "errors": {
 *     "name": ["This amenity name already exists."],
 *     "category": ["The amenity category is required."]
 *   }
 * }
 *
 * @apiErrorExample {json} Unauthorized:
 * HTTP/1.1 401 Unauthorized
 * {
 *   "code": 401,
 *   "message": "Unauthorized"
 * }
 */

/**
 * @api {put} /api/v1/admin/amenities/:id Update Amenity
 * @apiName UpdateAdminAmenity
 * @apiGroup Amenities
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Updates an existing amenity. All body fields are optional.
 *
 * @apiParam {Number} id Amenity ID.
 *
 * @apiBody {String} [name] New amenity name (max 100, unique).
 * @apiBody {String} [icon] New icon identifier (max 100).
 * @apiBody {String="connectivity","parking","comfort","payment"} [category] New amenity category.
 *
 * @apiSampleRequest /api/v1/admin/amenities/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Amenity updated successfully.",
 *   "data": {
 *     "id": 1,
 *     "name": "Free Wi-Fi Premium",
 *     "icon": "wifi-premium",
 *     "category": "connectivity",
 *     "created_at": "2024-01-01T00:00:00.000000Z",
 *     "updated_at": "2024-06-01T00:00:00.000000Z"
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Amenity not found."
 * }
 */

/**
 * @api {delete} /api/v1/admin/amenities/:id Delete Amenity
 * @apiName DeleteAdminAmenity
 * @apiGroup Amenities
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Deletes an amenity. Associated location_amenities records are removed via CASCADE.
 *
 * @apiParam {Number} id Amenity ID.
 *
 * @apiSampleRequest /api/v1/admin/amenities/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Amenity deleted successfully.",
 *   "data": null
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Amenity not found."
 * }
 */
