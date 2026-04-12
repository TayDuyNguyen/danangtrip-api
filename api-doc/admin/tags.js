/**
 * @api {post} /api/v1/admin/tags Create tag
 * @apiName CreateTag
 * @apiGroup Admin Tags
 * @apiPermission Admin
 * @apiDescription Create a new tag.
 * (Tạo tag mới)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} name Tag name.
 * @apiBody {String} [slug] Tag slug.
 * @apiBody {String} type Tag type (cuisine, service, feature, atmosphere).
 *
 * @apiSuccess {Number} code Status code (201).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Created tag object.
 */

/**
 * @api {delete} /api/v1/admin/tags/:id Delete tag
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
