/**
 * @api {get} /api/v1/amenities Get amenities
 * @apiName GetAmenities
 * @apiGroup Amenities
 * @apiPermission Public
 * @apiDescription Get a list of all amenities with optional category filter.
 * (Lấy danh sách tất cả tiện ích với bộ lọc danh mục tùy chọn)
 *
 * @apiQuery {String} [category] Filter by category (connectivity, parking, comfort, payment).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of amenities.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": [
 *         {
 *           "id": 1,
 *           "name": "Free Wi-Fi",
 *           "icon": "wifi",
 *           "category": "connectivity"
 *         }
 *       ]
 *     }
 */
