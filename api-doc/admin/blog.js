/**
 * @api {post} /api/v1/admin/blog Create blog post
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
 * @api {put} /api/v1/admin/blog/:id Update blog post
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
 * @api {delete} /api/v1/admin/blog/:id Delete blog post
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
 * @api {patch} /api/v1/admin/blog/:id/publish Publish blog post
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
