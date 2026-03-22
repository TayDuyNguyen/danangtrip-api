/**
 * @api {post} /api/v1/admin/subcategories Create Subcategory
 * @apiName CreateSubcategory
 * @apiGroup Subcategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiBody {Number} category_id Category id
 * @apiBody {String} name Subcategory name
 * @apiBody {String} [slug] Subcategory slug (unique). If omitted, slug will be generated from name.
 * @apiBody {String} [description] Description
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/subcategories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Subcategory created successfully",
 *   "data": {
 *     "subcategory": {
 *       "id": 10,
 *       "category_id": 1,
 *       "name": "Hải sản",
 *       "slug": "hai-san"
 *     }
 *   }
 * }
 */

/**
 * @api {put} /api/v1/admin/subcategories/:id Update Subcategory
 * @apiName UpdateSubcategory
 * @apiGroup Subcategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id Subcategory id
 * @apiBody {Number} [category_id] Category id
 * @apiBody {String} [name] Subcategory name
 * @apiBody {String} [slug] Subcategory slug (unique). If omitted and name is provided, slug will be updated from name.
 * @apiBody {String} [description] Description
 * @apiBody {Number} [sort_order] Sort order
 * @apiBody {String="active","inactive"} [status] Status
 *
 * @apiSampleRequest /api/v1/admin/subcategories/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Subcategory updated successfully",
 *   "data": {
 *     "subcategory": {
 *       "id": 10,
 *       "category_id": 1,
 *       "name": "Quán cà phê",
 *       "slug": "quan-ca-phe"
 *     }
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/admin/subcategories/:id Delete Subcategory
 * @apiName DeleteSubcategory
 * @apiGroup Subcategories
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id Subcategory id
 *
 * @apiSampleRequest /api/v1/admin/subcategories/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Subcategory deleted successfully",
 *   "data": null
 * }
 */
