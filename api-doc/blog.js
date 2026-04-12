/**
 * @api {get} /api/v1/blog Get blog posts
 * @apiName GetBlogPosts
 * @apiGroup Blog
 * @apiPermission Public
 * @apiDescription Get a paginated list of published blog posts.
 * (Lấy danh sách bài viết Blog đã xuất bản có phân trang)
 *
 * @apiQuery {Number} [category_id] Filter by blog category ID.
 * @apiQuery {Number} [page=1] Page number.
 * @apiQuery {Number} [per_page=15] Items per page.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated data object.
 */

/**
 * @api {get} /api/v1/blog/:slug Get blog post detail
 * @apiName GetBlogPostDetail
 * @apiGroup Blog
 * @apiPermission Public
 * @apiDescription Get detail of a specific published blog post by slug.
 * (Lấy chi tiết một bài viết Blog đã xuất bản theo slug)
 *
 * @apiParam {String} slug Unique slug of the blog post.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Blog post object.
 */

/**
 * @api {get} /api/v1/blog/categories Get blog categories
 * @apiName GetBlogCategories
 * @apiGroup Blog
 * @apiPermission Public
 * @apiDescription Get a list of all blog categories with post counts.
 * (Lấy danh sách tất cả danh mục Blog kèm số lượng bài viết)
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object[]} data List of blog categories.
 */
