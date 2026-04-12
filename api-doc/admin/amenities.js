/**
 * @api {post} /api/v1/admin/amenities Create amenity
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
 * @api {delete} /api/v1/admin/amenities/:id Delete amenity
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
