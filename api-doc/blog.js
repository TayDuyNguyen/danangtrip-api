/**
 * @api {get} /v1/blog Get blog posts
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
 * @api {get} /v1/blog/:slug Get blog post detail
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
 * @api {get} /v1/blog/categories Get blog categories
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

/**
 * @api {post} /v1/admin/blog Create blog post
 * @apiName CreateBlogPost
 * @apiGroup Admin Blog
 * @apiPermission Admin
 * @apiDescription Create a new blog post.
 * (Tạo bài viết Blog mới)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiBody {String} title Blog title.
 * @apiBody {String} content Blog content (HTML supported).
 * @apiBody {String} [excerpt] Short summary.
 * @apiBody {String} [featured_image] URL or path to image.
 * @apiBody {Number[]} category_ids Array of category IDs.
 * @apiBody {String} [status="draft"] Post status (draft, published).
 * @apiBody {String} [published_at] Date/time to publish.
 *
 * @apiSuccess {Number} code Status code (201).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Created blog post object.
 */

/**
 * @api {put} /v1/admin/blog/:id Update blog post
 * @apiName UpdateBlogPost
 * @apiGroup Admin Blog
 * @apiPermission Admin
 * @apiDescription Update an existing blog post.
 * (Cập nhật bài viết Blog hiện có)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Blog post ID.
 *
 * @apiBody {String} [title] Blog title.
 * @apiBody {String} [content] Blog content.
 * @apiBody {String} [excerpt] Short summary.
 * @apiBody {String} [featured_image] URL or path to image.
 * @apiBody {Number[]} [category_ids] Array of category IDs.
 * @apiBody {String} [status] Post status (draft, published).
 * @apiBody {String} [published_at] Date/time to publish.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Updated blog post object.
 */

/**
 * @api {delete} /v1/admin/blog/:id Delete blog post
 * @apiName DeleteBlogPost
 * @apiGroup Admin Blog
 * @apiPermission Admin
 * @apiDescription Delete a blog post.
 * (Xóa bài viết Blog)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Blog post ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 */

/**
 * @api {patch} /v1/admin/blog/:id/publish Publish blog post
 * @apiName PublishBlogPost
 * @apiGroup Admin Blog
 * @apiPermission Admin
 * @apiDescription Change status of a blog post (publish/unpublish).
 * (Thay đổi trạng thái bài viết Blog - xuất bản/ẩn)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Blog post ID.
 * @apiBody {String} status New status (draft, published).
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Updated blog post object.
 */
