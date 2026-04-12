/**
 * @api {get} /api/v1/categories Get Categories
 * @apiName GetCategories
 * @apiGroup Categories
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns active categories with active subcategories.
 *
 * @apiSampleRequest /api/v1/categories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "categories": [
 *       {
 *         "id": 1,
 *         "name": "Ăn uống",
 *         "slug": "an-uong",
 *         "icon": "fa-utensils",
 *         "description": "Mô tả",
 *         "image": null,
 *         "sort_order": 1,
 *         "status": "active",
 *         "subcategories": [
 *           {
 *             "id": 10,
 *             "category_id": 1,
 *             "name": "Hải sản",
 *             "slug": "hai-san",
 *             "description": null,
 *             "sort_order": 1,
 *             "status": "active"
 *           }
 *         ]
 *       }
 *     ]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/categories/:id Get Category Detail
 * @apiName GetCategoryDetail
 * @apiGroup Categories
 * @apiVersion 1.0.0
 *
 * @apiParam {Number} id Category id
 *
 * @apiDescription Public endpoint. Returns active category detail with active subcategories.
 *
 * @apiSampleRequest /api/v1/categories/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "category": {
 *       "id": 1,
 *       "name": "Ăn uống",
 *       "slug": "an-uong",
 *       "icon": "fa-utensils",
 *       "description": "Mô tả",
 *       "image": null,
 *       "sort_order": 1,
 *       "status": "active",
 *       "subcategories": []
 *     }
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Category not found",
 *   "data": null
 * }
 */

/**
 * @api {get} /api/v1/categories/:slug/locations Get Locations by Category Slug
 * @apiName GetLocationsByCategorySlug
 * @apiGroup Categories
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns paginated active locations belonging to the given category slug.
 *
 * @apiParam {String} slug Category slug
 * @apiQuery {Number} [per_page=15] Number of results per page
 *
 * @apiSampleRequest /api/v1/categories/an-uong/locations
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "locations": {
 *       "current_page": 1,
 *       "data": [
 *         {
 *           "id": 1,
 *           "name": "Nhà hàng Hải Sản",
 *           "slug": "nha-hang-hai-san",
 *           "status": "active"
 *         }
 *       ],
 *       "per_page": 15,
 *       "total": 1
 *     }
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Category not found",
 *   "data": null
 * }
 */
