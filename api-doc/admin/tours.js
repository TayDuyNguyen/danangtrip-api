/**
 * @api {post} /api/v1/admin/tours Admin Create Tour
 * @apiName AdminCreateTour
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Creates a new tour.
 *
 * @apiBody {String} name Tour name
 * @apiBody {String} [slug] Unique slug (if empty, generated from name)
 * @apiBody {Number} category_id Tour category ID
 * @apiBody {String} description Tour description
 * @apiBody {String} [short_description] Short description
 * @apiBody {Object[]} itinerary Itinerary array
 * @apiBody {Number} price Base price
 * @apiBody {String} [thumbnail] Thumbnail URL
 * @apiBody {String[]} [images] Array of image URLs
 * @apiBody {Number} duration_days Tour duration (days)
 * @apiBody {Number} duration_nights Tour duration (nights)
 * @apiBody {String="active","inactive","pending"} [status] Initial status
 * @apiBody {Boolean} [is_featured] Featured flag
 * @apiBody {Boolean} [is_hot] Hot flag
 *
 * @apiSampleRequest /api/v1/admin/tours
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Tour created successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "New Tour",
 *     "slug": "new-tour"
 *   }
 * }
 */

/**
 * @api {put} /api/v1/admin/tours/:id Admin Update Tour
 * @apiName AdminUpdateTour
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates an existing tour.
 *
 * @apiParam {Number} id Tour id
 * @apiBody {String} [name] Tour name
 * @apiBody {String} [slug] Unique slug
 * @apiBody {Number} [category_id] Tour category ID
 * @apiBody {String} [description] Tour description
 * @apiBody {Number} [price] Base price
 * @apiBody {String="active","inactive","pending"} [status] Status
 * @apiBody {Boolean} [is_featured] Featured flag
 * @apiBody {Boolean} [is_hot] Hot flag
 *
 * @apiSampleRequest /api/v1/admin/tours/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tour updated successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "Updated Tour",
 *     "status": "active"
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/admin/tours/:id Admin Delete Tour
 * @apiName AdminDeleteTour
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Deletes a tour.
 *
 * @apiParam {Number} id Tour id
 *
 * @apiSampleRequest /api/v1/admin/tours/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tour deleted successfully",
 *   "data": null
 * }
 */

/**
 * @api {patch} /api/v1/admin/tours/:id/status Admin Update Tour Status
 * @apiName AdminUpdateTourStatus
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates tour status.
 *
 * @apiParam {Number} id Tour id
 * @apiBody {String="active","inactive","pending"} status New status
 *
 * @apiSampleRequest /api/v1/admin/tours/1/status
 */

/**
 * @api {patch} /api/v1/admin/tours/:id/featured Admin Toggle Featured
 * @apiName AdminToggleTourFeatured
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Toggles featured status.
 *
 * @apiParam {Number} id Tour id
 *
 * @apiSampleRequest /api/v1/admin/tours/1/featured
 */

/**
 * @api {patch} /api/v1/admin/tours/:id/hot Admin Toggle Hot
 * @apiName AdminToggleTourHot
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Toggles hot status.
 *
 * @apiParam {Number} id Tour id
 *
 * @apiSampleRequest /api/v1/admin/tours/1/hot
 */

/**
 * @api {get} /api/v1/admin/tours/export Admin Export Tours
 * @apiName AdminExportTours
 * @apiGroup AdminTours
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Exports all tours to CSV.
 *
 * @apiSampleRequest /api/v1/admin/tours/export
 *
 * @apiSuccess {File} file CSV file with tour data
 */
