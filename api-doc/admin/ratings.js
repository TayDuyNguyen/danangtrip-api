/**
 * @api {get} /api/v1/admin/ratings Admin List Ratings
 * @apiName AdminListRatings
 * @apiGroup Admin Ratings
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
 * @apiGroup Admin Ratings
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
 * @apiGroup Admin Ratings
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
