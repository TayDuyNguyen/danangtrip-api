/**
 * @api {get} /v1/amenities Get amenities
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
 * @apiSuccess {Number} data.id Amenity ID.
 * @apiSuccess {String} data.name Amenity name.
 * @apiSuccess {String} data.icon Amenity icon.
 * @apiSuccess {String} data.category Amenity category.
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

/**
 * @api {post} /v1/admin/amenities Create amenity
 * @apiName CreateAmenity
 * @apiGroup Admin Amenities
 * @apiPermission Admin
 * @apiDescription Create a new amenity.
 * (Tạo tiện ích mới)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} name Amenity name.
 * @apiBody {String} [icon] Amenity icon class or name.
 * @apiBody {String} category Amenity category (connectivity, parking, comfort, payment).
 *
 * @apiSuccess {Number} code Status code (201).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Created amenity object.
 */

/**
 * @api {delete} /v1/admin/amenities/:id Delete amenity
 * @apiName DeleteAmenity
 * @apiGroup Admin Amenities
 * @apiPermission Admin
 * @apiDescription Delete an amenity.
 * (Xóa tiện ích)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Amenity ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */
