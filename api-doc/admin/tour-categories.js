/**
 * @api {get} /api/v1/admin/tour-categories Admin List Tour Categories
 * @apiName AdminListTourCategories
 * @apiGroup AdminTourCategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Returns a list of all tour categories with filters.
 *
 * @apiQuery {String} [search] Search by name or slug
 * @apiQuery {String="active","inactive"} [status] Filter by status
 *
 * @apiSampleRequest /api/v1/admin/tour-categories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "City Tour",
 *       "slug": "city-tour",
 *       "status": "active"
 *     }
 *   ]
 * }
 */

/**
 * @api {post} /api/v1/admin/tour-categories Admin Create Tour Category
 * @apiName AdminCreateTourCategory
 * @apiGroup AdminTourCategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Creates a new tour category.
 *
 * @apiBody {String} name Category name
 * @apiBody {String} [slug] Unique slug
 * @apiBody {String} [icon] Icon name
 * @apiBody {String} [description] Description
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Initial status
 *
 * @apiSampleRequest /api/v1/admin/tour-categories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Tour category created successfully",
 *   "data": { "id": 1, "name": "City Tour" }
 * }
 */

/**
 * @api {put} /api/v1/admin/tour-categories/:id Admin Update Tour Category
 * @apiName AdminUpdateTourCategory
 * @apiGroup AdminTourCategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates an existing tour category.
 *
 * @apiParam {Number} id Category id
 * @apiBody {String} [name] Category name
 * @apiBody {String} [slug] Unique slug
 * @apiBody {String} [icon] Icon name
 * @apiBody {String} [description] Description
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/tour-categories/1
 */

/**
 * @api {delete} /api/v1/admin/tour-categories/:id Admin Delete Tour Category
 * @apiName AdminDeleteTourCategory
 * @apiGroup AdminTourCategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Deletes a tour category if it has no associated tours.
 *
 * @apiParam {Number} id Category id
 *
 * @apiSampleRequest /api/v1/admin/tour-categories/1
 */

/**
 * @api {patch} /api/v1/admin/tour-categories/:id/status Admin Update Tour Category Status
 * @apiName AdminUpdateTourCategoryStatus
 * @apiGroup AdminTourCategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates the status of a tour category.
 *
 * @apiParam {Number} id Category id
 * @apiBody {String="active","inactive"} status New status
 *
 * @apiSampleRequest /api/v1/admin/tour-categories/1/status
 */
