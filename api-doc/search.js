/**
 * @api {get} /api/v1/search Search Locations
 * @apiName SearchLocations
 * @apiGroup Search
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Search locations by keyword and filters (FULLTEXT on locations + categories/subcategories filters + tags join). Also inserts a row into search_logs.
 *
 * @apiParam (Query) {String} [q] Search keyword (required)
 * @apiParam (Query) {Number} [category_id] Category id
 * @apiParam (Query) {Number} [subcategory_id] Subcategory id
 * @apiParam (Query) {String} [district] District
 * @apiParam (Query) {Number{1-4}} [price_level] Price level
 * @apiParam (Query) {Number} [price_min] Min price
 * @apiParam (Query) {Number} [price_max] Max price
 * @apiParam (Query) {Number{0-5}} [rating_min] Min rating
 * @apiParam (Query) {String} [tag] Tag slug or name (comma-separated)
 * @apiParam (Query) {String="avg_rating","review_count","view_count","created_at","price_min","price_max","name"} [sort] Sort field
 * @apiParam (Query) {String="asc","desc"} [order] Sort direction
 * @apiParam (Query) {Number{1..}} [page] Page number
 * @apiParam (Query) {Number{1-100}} [per_page] Items per page
 * @apiParam (Query) {String} [session_id] Client session id (used for analytics)
 *
 * @apiSampleRequest /api/v1/search?q=coffee
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "query": "coffee",
 *     "results": {
 *       "current_page": 1,
 *       "data": [
 *         {
 *           "id": 1,
 *           "name": "Coffee House",
 *           "slug": "coffee-house",
 *           "district": "Hai Chau",
 *           "avg_rating": 4.5,
 *           "status": "active"
 *         }
 *       ],
 *       "per_page": 10,
 *       "total": 1
 *     }
 *   }
 * }
 *
 * @apiErrorExample {json} Validation-Error:
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 422,
 *   "message": "Validation failed",
 *   "errors": {
 *     "q": ["The search query is required. (Từ khóa tìm kiếm là bắt buộc.)"]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/search/suggestions Search Suggestions
 * @apiName SearchSuggestions
 * @apiGroup Search
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns query suggestions for autocomplete (LIKE on locations.name).
 *
 * @apiParam (Query) {String} [q] Search keyword prefix (required)
 * @apiParam (Query) {Number{1-20}} [limit] Max suggestions (default: 5)
 *
 * @apiSampleRequest /api/v1/search/suggestions?q=cof&limit=5
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "query": "cof",
 *     "suggestions": ["coffee", "coffee house", "coffee shop"]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/search/popular Popular Search Queries
 * @apiName PopularSearchQueries
 * @apiGroup Search
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns popular search queries based on search logs.
 *
 * @apiParam (Query) {Number{1-50}} [limit] Max items (default: 10)
 * @apiParam (Query) {Number{1-365}} [days] Lookback days (default: 30)
 *
 * @apiSampleRequest /api/v1/search/popular?limit=10&days=30
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "popular": [
 *       { "query": "coffee", "count": 120 },
 *       { "query": "seafood", "count": 80 }
 *     ]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/search/popular-with-filters Get Popular Search Queries with Filters
 * @apiName GetPopularSearchQueriesWithFilters
 * @apiGroup Search
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint - Returns a list of popular search queries, filtered by specific criteria.
 *
 * @apiQuery {Number} [limit=10] Max items to return.
 * @apiQuery {Number} [days=30] Lookback window (days).
 * @apiQuery {Object} [filters] JSON object of filters (e.g., {"district":"Hai Chau", "price_level":2}).
 * @apiQuery {String} [filters.district] Filter by district.
 * @apiQuery {Number} [filters.price_level] Filter by price level.
 *
 * @apiSampleRequest /api/v1/search/popular-with-filters?filters[district]=Hai%20Chau&filters[price_level]=2
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "popular": [
 *       {"query": "beach", "count": 15},
 *       {"query": "hotel", "count": 10}
 *     ]
 *   }
 * }
 */
