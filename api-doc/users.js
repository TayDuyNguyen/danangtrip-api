/**
 * @api {get} /api/v1/users Get Users
 * @apiName GetUsers
 * @apiGroup Users
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiSampleRequest /api/v1/users
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "users": [
 *       {
 *         "id": 1,
 *         "username": "john",
 *         "email": "john@example.com",
 *         "full_name": "John Doe",
 *         "role": "user"
 *       }
 *     ]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/users/:id Get User Detail
 * @apiName GetUserDetail
 * @apiGroup Users
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id User id
 *
 * @apiSampleRequest /api/v1/users/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "user": {
 *       "id": 1,
 *       "username": "john",
 *       "email": "john@example.com",
 *       "full_name": "John Doe",
 *       "role": "user"
 *     }
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "User not found",
 *   "data": null
 * }
 */

/**
 * @api {post} /api/v1/users Create User
 * @apiName CreateUser
 * @apiGroup Users
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiBody {String} username Username
 * @apiBody {String} email Email
 * @apiBody {String} password Password
 * @apiBody {String} full_name Full name
 * @apiBody {String} [phone] Phone
 * @apiBody {String} [birthdate] Birthdate (YYYY-MM-DD)
 * @apiBody {String} [gender] Gender
 * @apiBody {String} [city] City
 * @apiBody {String="admin","partner","user"} [role] Role
 *
 * @apiSampleRequest /api/v1/users
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "User created successfully",
 *   "data": {
 *     "user": {
 *       "id": 2,
 *       "username": "alice",
 *       "email": "alice@example.com",
 *       "full_name": "Alice",
 *       "role": "user"
 *     }
 *   }
 * }
 */

/**
 * @api {put} /api/v1/users/:id Update User
 * @apiName UpdateUser
 * @apiGroup Users
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id User id
 * @apiBody {String} [username] Username
 * @apiBody {String} [email] Email
 * @apiBody {String} [password] Password
 * @apiBody {String} [full_name] Full name
 * @apiBody {String} [phone] Phone
 * @apiBody {String} [birthdate] Birthdate (YYYY-MM-DD)
 * @apiBody {String} [gender] Gender
 * @apiBody {String} [city] City
 * @apiBody {String="admin","partner","user"} [role] Role
 *
 * @apiSampleRequest /api/v1/users/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "User updated successfully",
 *   "data": {
 *     "user": {
 *       "id": 2,
 *       "full_name": "Alice Updated",
 *       "role": "partner"
 *     }
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/users/:id Delete User
 * @apiName DeleteUser
 * @apiGroup Users
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Protected endpoint - Requires authentication and role=admin
 *
 * @apiParam {Number} id User id
 *
 * @apiSampleRequest /api/v1/users/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "User deleted successfully",
 *   "data": null
 * }
 */
