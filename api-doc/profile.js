/**
 * @api {get} /user/profile 1. Get Profile
 * @apiName GetProfile
 * @apiGroup Profile
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiSuccess {Number} code HTTP status code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data User profile object.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": {
 *         "id": 1,
 *         "username": "johndoe",
 *         "email": "john@example.com",
 *         "full_name": "John Doe",
 *         "avatar": "avatars/xyz.jpg",
 *         "phone": "0123456789",
 *         "birthdate": "1990-01-01",
 *         "gender": "male",
 *         "city": "Hanoi"
 *       }
 *     }
 */

/**
 * @api {put} /user/profile 2. Update Profile
 * @apiName UpdateProfile
 * @apiGroup Profile
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} [full_name] Full name.
 * @apiBody {String} [phone] Phone number.
 * @apiBody {String} [birthdate] Birthdate (YYYY-MM-DD).
 * @apiBody {String="male","female","other"} [gender] Gender.
 * @apiBody {String} [city] City.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Profile updated successfully.",
 *       "data": { ... }
 *     }
 */

/**
 * @api {post} /user/profile/avatar 3. Upload Avatar
 * @apiName UpdateAvatar
 * @apiGroup Profile
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiHeader {String} Content-Type multipart/form-data
 *
 * @apiBody {File} avatar Avatar image file (jpeg, png, jpg, max 2MB).
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Avatar updated successfully.",
 *       "data": {
 *         "avatar_url": "http://api.example.com/storage/avatars/xyz.jpg"
 *       }
 *     }
 */

/**
 * @api {put} /user/password 4. Change Password
 * @apiName ChangePassword
 * @apiGroup Profile
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} current_password Current password.
 * @apiBody {String} password New password (min 8 chars).
 * @apiBody {String} password_confirmation New password confirmation.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Password changed successfully.",
 *       "data": null
 *     }
 */

/**
 * @api {get} /user/ratings 5. Rating History
 * @apiName GetRatingHistory
 * @apiGroup Profile
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {String="pending","approved","rejected"} [status] Filter by status.
 * @apiParam {Number} [page=1] Page number.
 * @apiParam {Number} [per_page=10] Items per page.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Success",
 *       "data": {
 *         "current_page": 1,
 *         "data": [
 *           {
 *             "id": 1,
 *             "score": 5,
 *             "comment": "Great place!",
 *             "status": "approved",
 *             "location": { "id": 10, "name": "Eiffel Tower" },
 *             "images": [...]
 *           }
 *         ],
 *         "total": 5
 *       }
 *     }
 */
