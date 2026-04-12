/**
 * @api {get} /api/v1/tour-categories Get Tour Categories
 * @apiName GetTourCategories
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns active tour categories.
 *
 * @apiSampleRequest /api/v1/tour-categories
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "City Tour",
 *       "slug": "city-tour",
 *       "icon": "fa-city"
 *     }
 *   ]
 * }
 */

/**
 * @api {get} /api/v1/tour-categories/:slug/tours Get Tours by Category Slug
 * @apiName GetToursByCategorySlug
 * @apiGroup Tours
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns paginated active tours for a specific category slug.
 *
 * @apiParam {String} slug Category slug
 * @apiQuery {Number} [per_page=15] Items per page
 *
 * @apiSampleRequest /api/v1/tour-categories/city-tour/tours
 */
