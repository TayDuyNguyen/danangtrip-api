/**
 * @api {get} /api/v1/tags Get tags
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
