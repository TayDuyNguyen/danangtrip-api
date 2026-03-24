/**
 * @api {get} /v1/user/notifications Get notification list
 * @apiName GetNotifications
 * @apiGroup Notifications
 * @apiPermission User
 * @apiDescription Get a paginated list of notifications for the authenticated user.
 * (Lấy danh sách thông báo có phân trang của người dùng đã xác thực)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiQuery {Boolean} [is_read] Filter by read status (true/false).
 * @apiQuery {Number} [page=1] Page number.
 * @apiQuery {Number} [per_page=15] Items per page.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Paginated data object.
 * @apiSuccess {Object[]} data.data List of notifications.
 * @apiSuccess {String} data.data.title Notification title.
 * @apiSuccess {String} data.data.content Notification content.
 * @apiSuccess {Boolean} data.data.is_read Read status.
 * @apiSuccess {String} data.data.created_at Creation timestamp.
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
 *             "title": "Welcome!",
 *             "content": "Welcome to our application!",
 *             "is_read": false,
 *             "created_at": "2024-03-25T12:00:00.000000Z"
 *           }
 *         ],
 *         "total": 1
 *       }
 *     }
 */

/**
 * @api {patch} /v1/user/notifications/:id/read Mark as read
 * @apiName MarkNotificationRead
 * @apiGroup Notifications
 * @apiPermission User
 * @apiDescription Mark a specific notification as read.
 * (Đánh dấu một thông báo cụ thể là đã đọc)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Notification's unique ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data The updated notification object.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Notification marked as read.",
 *       "data": {
 *         "id": 1,
 *         "is_read": true,
 *         "read_at": "2024-03-25T12:05:00.000000Z"
 *       }
 *     }
 */

/**
 * @api {patch} /v1/user/notifications/read-all Mark all as read
 * @apiName MarkAllNotificationsRead
 * @apiGroup Notifications
 * @apiPermission User
 * @apiDescription Mark all unread notifications as read.
 * (Đánh dấu tất cả thông báo chưa đọc là đã đọc)
 *
 * @apiHeader {String} Authorization Bearer <token>
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 * @apiSuccess {Object} data Data object.
 * @apiSuccess {Number} data.updated_count Number of notifications updated.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "3 notifications marked as read.",
 *       "data": {
 *         "updated_count": 3
 *       }
 *     }
 */

/**
 * @api {delete} /v1/user/notifications/:id Delete notification
 * @apiName DeleteNotification
 * @apiGroup Notifications
 * @apiPermission User
 * @apiDescription Delete a specific notification.
 * (Xóa một thông báo cụ thể)
 *
 * @apiHeader {String} Authorization Bearer <token>
 * @apiParam {Number} id Notification's unique ID.
 *
 * @apiSuccess {Number} code Status code (200).
 * @apiSuccess {String} message Success message.
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "code": 200,
 *       "message": "Notification deleted successfully."
 *     }
 */
