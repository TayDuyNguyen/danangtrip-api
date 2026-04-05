/**
 * @api {post} /api/v1/admin/categories Create Category
 * @apiName CreateCategory
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiBody {String} name Category name
 * @apiBody {String} [slug] Category slug (unique). If omitted, slug will be generated from name.
 * @apiBody {String} [icon] Icon name
 * @apiBody {String} [description] Description
 * @apiBody {String} [image] Image URL
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/categories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Category created successfully",
 *   "data": {
 *     "category": {
 *       "id": 1,
 *       "name": "Ăn uống",
 *       "slug": "an-uong"
 *     }
 *   }
 * }
 */

/**
 * @api {put} /api/v1/admin/categories/:id Update Category
 * @apiName UpdateCategory
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id Category id
 * @apiBody {String} [name] Category name
 * @apiBody {String} [slug] Category slug (unique). If omitted and name is provided, slug will be updated from name.
 * @apiBody {String} [icon] Icon name
 * @apiBody {String} [description] Description
 * @apiBody {String} [image] Image URL
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/categories/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Category updated successfully",
 *   "data": {
 *     "category": {
 *       "id": 1,
 *       "name": "Khách sạn",
 *       "slug": "khach-san"
 *     }
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/admin/categories/:id Delete Category
 * @apiName DeleteCategory
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id Category id
 *
 * @apiSampleRequest /api/v1/admin/categories/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Category deleted successfully",
 *   "data": null
 * }
 *
 * @apiErrorExample {json} Conflict:
 * HTTP/1.1 409 Conflict
 * {
 *   "code": 409,
 *   "message": "Cannot delete category because it has subcategories",
 *   "data": null
 * }
 */

/**
 * @api {patch} /api/v1/admin/categories/:id/status Update Category Status
 * @apiName UpdateCategoryStatus
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id Category id
 * @apiBody {String="active","inactive"} status New status
 *
 * @apiSampleRequest /api/v1/admin/categories/1/status
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Category status updated successfully",
 *   "data": null
 * }
 */
