/**
 * @api {get} /api/v1/admin/categories Admin List Categories
 * @apiName AdminListCategories
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiParam {String} [search] Search by name or slug
 * @apiParam {String="active","inactive"} [status] Filter by status
 * @apiParam {Number{1..100}} [per_page=15] Number of records per page
 * @apiParam {Boolean} [with_stats=false] Include aggregate stats
 *
 * @apiSampleRequest /api/v1/admin/categories?search=food&per_page=100&with_stats=true
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "categories": {
 *       "current_page": 1,
 *       "data": [
 *         {
 *           "id": 1,
 *           "name": "Ăn uống",
 *           "slug": "an-uong",
 *           "icon": "Utensils",
 *           "icon_background": "#E0F2FE",
 *           "status": "active",
 *           "sort_order": 1,
 *           "locations_count": 12
 *         }
 *       ],
 *       "per_page": 100,
 *       "total": 100
 *     },
 *     "stats": {
 *       "total_categories": 100,
 *       "active_categories": 80,
 *       "inactive_categories": 20,
 *       "total_locations": 450
 *     }
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/categories/:id Admin Category Detail
 * @apiName AdminCategoryDetail
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiParam {Number} id Category id
 *
 * @apiSampleRequest /api/v1/admin/categories/1
 */

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
 * @apiBody {String} [icon_background] Icon background hex color
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
 *     "id": 1,
 *     "name": "Ăn uống",
 *     "slug": "an-uong",
 *     "icon": "Utensils",
 *     "icon_background": "#E0F2FE",
 *     "status": "active",
 *     "sort_order": 1
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
 * @apiBody {String} [icon_background] Icon background hex color
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
 *     "id": 1,
 *     "name": "Khách sạn",
 *     "slug": "khach-san",
 *     "icon": "Hotel",
 *     "icon_background": "#FCE7F3",
 *     "status": "active",
 *     "sort_order": 2
 *   }
 * }
 */

/**
 * @api {patch} /api/v1/admin/categories/reorder Admin Reorder Categories
 * @apiName AdminReorderCategories
 * @apiGroup Admin Categories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiBody {Object[]} items List of reordered items
 * @apiBody {Number} items.id Category id
 * @apiBody {Number} items.sort_order New sort order
 *
 * @apiSampleRequest /api/v1/admin/categories/reorder
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Categories reordered successfully",
 *   "data": null
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
 *   "data": {
 *     "id": 1,
 *     "name": "Ăn uống",
 *     "slug": "an-uong",
 *     "status": "inactive"
 *   }
 * }
 */
