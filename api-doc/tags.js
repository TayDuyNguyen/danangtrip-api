/**
 * @api {get} /api/v1/tags Get Tags
 * @apiName GetTags
 * @apiGroup Tags
 * @apiVersion 1.0.0
 * @apiPermission public
 *
 * @apiDescription Public endpoint. Returns all tags with optional type filter.
 *
 * @apiParam (Query) {String="cuisine","service","feature","atmosphere"} [type] Filter by tag type.
 *
 * @apiSampleRequest /api/v1/tags
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "Romantic",
 *       "slug": "romantic",
 *       "type": "atmosphere",
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
 *     "type": ["The selected tag type is invalid."]
 *   }
 * }
 */

/**
 * @api {post} /api/v1/admin/tags Create Tag
 * @apiName CreateAdminTag
 * @apiGroup Tags
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Creates a new tag.
 *
 * @apiBody {String} name Tag name (max 100, unique).
 * @apiBody {String} slug Tag slug (max 100, unique).
 * @apiBody {String="cuisine","service","feature","atmosphere"} type Tag type.
 *
 * @apiSampleRequest /api/v1/admin/tags
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Tag created successfully.",
 *   "data": {
 *     "id": 1,
 *     "name": "Romantic",
 *     "slug": "romantic",
 *     "type": "atmosphere",
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
 *     "name": ["This tag name already exists."],
 *     "slug": ["This tag slug already exists."]
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
 * @api {put} /api/v1/admin/tags/:id Update Tag
 * @apiName UpdateAdminTag
 * @apiGroup Tags
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Updates an existing tag. All body fields are optional.
 *
 * @apiParam {Number} id Tag ID.
 *
 * @apiBody {String} [name] New tag name (max 100, unique).
 * @apiBody {String} [slug] New tag slug (max 100, unique).
 * @apiBody {String="cuisine","service","feature","atmosphere"} [type] New tag type.
 *
 * @apiSampleRequest /api/v1/admin/tags/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tag updated successfully.",
 *   "data": {
 *     "id": 1,
 *     "name": "Romantic Updated",
 *     "slug": "romantic-updated",
 *     "type": "atmosphere",
 *     "created_at": "2024-01-01T00:00:00.000000Z",
 *     "updated_at": "2024-06-01T00:00:00.000000Z"
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Tag not found."
 * }
 */

/**
 * @api {delete} /api/v1/admin/tags/:id Delete Tag
 * @apiName DeleteAdminTag
 * @apiGroup Tags
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Deletes a tag. Associated location_tags records are removed via CASCADE.
 *
 * @apiParam {Number} id Tag ID.
 *
 * @apiSampleRequest /api/v1/admin/tags/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tag deleted successfully.",
 *   "data": null
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Tag not found."
 * }
 */
