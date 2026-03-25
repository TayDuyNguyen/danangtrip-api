/**
 * @api {get} /v1/tags Get tags
 * @apiName GetTags
 * @apiGroup Tags
 * @apiPermission Public
 * @apiDescription Get a list of all tags with optional type filter.
 * (Lấy danh sách tất cả tags với bộ lọc loại tùy chọn)
 *
 * @apiQuery {String} [type] Filter by type (cuisine, service, feature, atmosphere).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of tags.
 * @apiSuccess {Number} data.id Tag ID.
 * @apiSuccess {String} data.name Tag name.
 * @apiSuccess {String} data.slug Tag slug.
 * @apiSuccess {String} data.type Tag type.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": [
 *         {
 *           "id": 1,
 *           "name": "Romantic",
 *           "slug": "romantic",
 *           "type": "atmosphere"
 *         }
 *       ]
 *     }
 */

/**
 * @api {post} /v1/admin/tags Create tag
 * @apiName CreateTag
 * @apiGroup Admin Tags
 * @apiPermission Admin
 * @apiDescription Create a new tag.
 * (Tạo tag mới)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} name Tag name.
 * @apiBody {String} slug Tag slug.
 * @apiBody {String} type Tag type (cuisine, service, feature, atmosphere).
 *
 * @apiSuccess {Number} code Status code (201).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Created tag object.
 */

/**
 * @api {delete} /v1/admin/tags/:id Delete tag
 * @apiName DeleteTag
 * @apiGroup Admin Tags
 * @apiPermission Admin
 * @apiDescription Delete a tag.
 * (Xóa tag)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Tag ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */
