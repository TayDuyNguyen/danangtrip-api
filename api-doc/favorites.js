/**
 * @api {get} /user/favorites 1. List favorites
 * @apiName GetFavorites
 * @apiGroup Favorites
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {Number} [page=1] Page number.
 * @apiParam {Number} [per_page=10] Items per page.
 *
 * @apiSuccess {Number} code HTTP status code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated list of favorites.
 * @apiSuccess {Object[]} data.data List of favorite objects.
 * @apiSuccess {Number} data.data.id Favorite ID.
 * @apiSuccess {Number} data.data.user_id User ID.
 * @apiSuccess {Number} data.data.location_id Location ID.
 * @apiSuccess {String} data.data.created_at ISO date string.
 * @apiSuccess {Object} data.data.location Location details.
 * @apiSuccess {Number} data.data.location.id Location ID.
 * @apiSuccess {String} data.data.location.name Location name.
 * @apiSuccess {String} data.data.location.thumbnail Location thumbnail.
 * @apiSuccess {Object} data.data.location.category Category details.
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
 *             "user_id": 5,
 *             "location_id": 10,
 *             "created_at": "2026-03-24T10:00:00.000000Z",
 *             "location": {
 *               "id": 10,
 *               "name": "Eiffel Tower",
 *               "thumbnail": "eiffel.jpg",
 *               "category": {
 *                 "id": 1,
 *                 "name": "Landmark"
 *               }
 *             }
 *           }
 *         ],
 *         "total": 1
 *       }
 *     }
 */

/**
 * @api {post} /user/favorites 2. Add to favorites
 * @apiName AddFavorite
 * @apiGroup Favorites
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {Number} location_id ID of the location to save.
 *
 * @apiSuccess {Number} code HTTP status code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Null} data Always null.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 201 Created
 *     {
 *       "code": 201,
 *       "message": "Added to favorites list.",
 *       "data": null
 *     }
 */

/**
 * @api {delete} /user/favorites/:location_id 3. Remove from favorites
 * @apiName RemoveFavorite
 * @apiGroup Favorites
 * @apiVersion 1.0.0
 * @apiPermission User
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiParam {Number} location_id ID of the location to remove.
 *
 * @apiSuccess {Number} code HTTP status code.
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Null} data Always null.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Removed from favorites list.",
 *       "data": null
 *     }
 */
