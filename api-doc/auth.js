/**
 * @api {post} /api/v1/auth/register Register
 * @apiName Register
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiBody {String} username Username
 * @apiBody {String} email Email
 * @apiBody {String} password Password
 * @apiBody {String} password_confirmation Password confirmation
 * @apiBody {String} full_name Full name
 * @apiBody {String} [phone] Phone
 * @apiBody {String} [birthdate] Birthdate (YYYY-MM-DD)
 * @apiBody {String} [gender] Gender
 * @apiBody {String} [city] City
 * @apiBody {String="admin","partner","user"} [role] Role
 *
 * @apiSampleRequest /api/v1/auth/register
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "User registered successfully",
 *   "data": {
 *     "id": 1,
 *     "username": "john",
 *     "email": "john@example.com",
 *     "full_name": "John Doe",
 *     "role": "user"
 *   }
 * }
 *
 * @apiErrorExample {json} Validation-Error:
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 422,
 *   "message": "Validation failed",
 *   "data": null,
 *   "errors": {
 *     "email": ["The email address is required."]
 *   }
 * }
 */

/**
 * @api {post} /api/v1/auth/login Login
 * @apiName Login
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiBody {String} email Email
 * @apiBody {String} password Password
 *
 * @apiSampleRequest /api/v1/auth/login
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Login successful",
 *   "data": {
 *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
 *     "user": {
 *       "id": 1,
 *       "username": "john",
 *       "email": "john@example.com",
 *       "role": "user"
 *     }
 *   }
 * }
 *
 * @apiErrorExample {json} Unauthorized:
 * HTTP/1.1 401 Unauthorized
 * {
 *   "code": 401,
 *   "message": "Invalid credentials",
 *   "data": null
 * }
 */

/**
 * @api {post} /api/v1/auth/logout Logout
 * @apiName Logout
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint - Requires authentication
 *
 * @apiSampleRequest /api/v1/auth/logout
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Logged out successfully",
 *   "data": null
 * }
 */

/**
 * @api {get} /api/v1/auth/me Me
 * @apiName Me
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint - Requires authentication
 *
 * @apiSampleRequest /api/v1/auth/me
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "id": 1,
 *     "username": "john",
 *     "email": "john@example.com",
 *     "role": "user"
 *   }
 * }
 */

/**
 * @api {post} /api/v1/auth/refresh Refresh Token
 * @apiName RefreshToken
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiDescription Public or Protected endpoint - Returns a new JWT token.
 *
 * @apiHeader {String} [Authorization] Bearer token (JWT)
 *
 * @apiSampleRequest /api/v1/auth/refresh
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Token refreshed",
 *   "data": {
 *     "token": "new_eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
 *   }
 * }
 */

/**
 * @api {post} /api/v1/auth/forgot-password Forgot Password
 * @apiName ForgotPassword
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint - Sends a password reset email to the user.
 *
 * @apiBody {String} email User's registered email
 *
 * @apiSampleRequest /api/v1/auth/forgot-password
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Reset password link sent to your email",
 *   "data": null
 * }
 */

/**
 * @api {post} /api/v1/auth/reset-password Reset Password
 * @apiName ResetPassword
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint - Resets user's password using a token.
 *
 * @apiBody {String} email User's email
 * @apiBody {String} token Reset token from email
 * @apiBody {String} password New password
 * @apiBody {String} password_confirmation Confirm new password
 *
 * @apiSampleRequest /api/v1/auth/reset-password
 */

/**
 * @api {post} /api/v1/auth/verify-email Verify Email
 * @apiName VerifyEmail
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Verifies the user's email using a code.
 *
 * @apiBody {String} otp One-time password (OTP) code from email
 *
 * @apiSampleRequest /api/v1/auth/verify-email
 */

/**
 * @api {post} /api/v1/auth/resend-verification Resend Verification
 * @apiName ResendVerification
 * @apiGroup Auth
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Resends the email verification OTP.
 *
 * @apiSampleRequest /api/v1/auth/resend-verification
 */
