/**
 * @api {post} /api/v1/ratings Create Rating
 * @apiName CreateRating
 * @apiGroup Ratings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint. Create a new rating (pending by default).
 *
 * @apiBody {Number} location_id Location id
 * @apiBody {Number{1-5}} score Score (1-5)
 * @apiBody {String} [comment] Comment text
 * @apiBody {File[]} [images] Images array (max 5 files)
 *
 * @apiSampleRequest /api/v1/ratings
 *
 * @apiSuccessExample {json} Created:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Rating created successfully",
 *   "data": {
 *     "id": 1,
 *     "user_id": 1,
 *     "location_id": 10,
 *     "score": 5,
 *     "comment": "Great place!",
 *     "image_count": 2,
 *     "status": "pending",
 *     "images": [
 *       { "id": 1, "image_url": "https://...", "sort_order": 0 },
 *       { "id": 2, "image_url": "https://...", "sort_order": 1 }
 *     ]
 *   }
 * }
 */

/**
 * @api {put} /api/v1/ratings/:id Update Rating
 * @apiName UpdateRating
 * @apiGroup Ratings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint. Update your own rating. Updating sets status back to pending.
 *
 * @apiParam {Number} id Rating id
 * @apiBody {Number{1-5}} [score] Score (1-5)
 * @apiBody {String} [comment] Comment text
 * @apiBody {File[]} [images] Images array (max 5 files). If provided, replaces existing images.
 *
 * @apiSampleRequest /api/v1/ratings/1
 *
 * @apiSuccessExample {json} Success:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Rating updated successfully",
 *   "data": {
 *     "id": 1,
 *     "status": "pending"
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/ratings/:id Delete Rating
 * @apiName DeleteRating
 * @apiGroup Ratings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint. Delete your own rating.
 *
 * @apiParam {Number} id Rating id
 *
 * @apiSampleRequest /api/v1/ratings/1
 *
 * @apiSuccessExample {json} Success:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Rating deleted successfully",
 *   "data": null
 * }
 */

/**
 * @api {post} /api/v1/ratings/:id/helpful Mark Rating Helpful
 * @apiName MarkRatingHelpful
 * @apiGroup Ratings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Protected endpoint. Increment helpful_count for an approved rating.
 *
 * @apiParam {Number} id Rating id
 *
 * @apiSampleRequest /api/v1/ratings/1/helpful
 *
 * @apiSuccessExample {json} Success:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Marked as helpful",
 *   "data": {
 *     "id": 1,
 *     "helpful_count": 11
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/ratings Admin List Ratings
 * @apiName AdminListRatings
 * @apiGroup AdminRatings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. List ratings with filters and pagination.
 *
 * @apiParam (Query) {String="pending","approved","rejected"} [status] Filter status
 * @apiParam (Query) {Number} [location_id] Filter by location id
 * @apiParam (Query) {Number{1..}} [page] Page number
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 *
 * @apiSampleRequest /api/v1/admin/ratings?status=pending
 */

/**
 * @api {patch} /api/v1/admin/ratings/:id/approve Admin Approve Rating
 * @apiName AdminApproveRating
 * @apiGroup AdminRatings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Approve a pending rating. Location stats and point deductions are handled by RatingObserver.
 *
 * @apiParam {Number} id Rating id
 *
 * @apiSampleRequest /api/v1/admin/ratings/1/approve
 */

/**
 * @api {patch} /api/v1/admin/ratings/:id/reject Admin Reject Rating
 * @apiName AdminRejectRating
 * @apiGroup AdminRatings
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Reject a pending rating with reason. Creates a notification for the user.
 *
 * @apiParam {Number} id Rating id
 * @apiBody {String} rejected_reason Reject reason
 *
 * @apiSampleRequest /api/v1/admin/ratings/1/reject
 */
