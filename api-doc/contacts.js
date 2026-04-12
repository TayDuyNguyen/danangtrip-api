/**
 * @api {post} /api/v1/contacts Submit Contact Form
 * @apiName SubmitContact
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission public
 *
 * @apiDescription Public endpoint. Submits a contact form message.
 *
 * @apiBody {String} name Sender's full name (max 100).
 * @apiBody {String} email Sender's email address (max 100).
 * @apiBody {String} [phone] Sender's phone number (max 20).
 * @apiBody {String} [subject] Message subject (max 200).
 * @apiBody {String} message Message content.
 *
 * @apiSampleRequest /api/v1/contacts
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Your message has been sent successfully.",
 *   "data": {
 *     "id": 1,
 *     "name": "Nguyen Van A",
 *     "email": "user@example.com",
 *     "phone": "0901234567",
 *     "subject": "Inquiry",
 *     "message": "Hello, I have a question...",
 *     "status": "new",
 *     "created_at": "2024-01-01T00:00:00.000000Z"
 *   }
 * }
 *
 * @apiErrorExample {json} Validation-Error:
 * HTTP/1.1 422 Unprocessable Entity
 * {
 *   "code": 422,
 *   "message": "Validation failed",
 *   "errors": {
 *     "email": ["Please provide a valid email address."],
 *     "message": ["The message is required."]
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/contacts Get Contacts List
 * @apiName GetAdminContacts
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns paginated list of contact submissions.
 *
 * @apiParam (Query) {String="new","read","replied"} [status] Filter by status.
 * @apiParam (Query) {Number{1..}} [page=1] Page number.
 * @apiParam (Query) {Number{1-100}} [per_page=15] Items per page.
 *
 * @apiSampleRequest /api/v1/admin/contacts
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "current_page": 1,
 *     "data": [
 *       {
 *         "id": 1,
 *         "name": "Nguyen Van A",
 *         "email": "user@example.com",
 *         "subject": "Inquiry",
 *         "status": "new",
 *         "created_at": "2024-01-01T00:00:00.000000Z"
 *       }
 *     ],
 *     "total": 1
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/contacts/:id Get Contact Detail
 * @apiName GetAdminContactDetail
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Returns contact detail. Automatically marks status as "read" if currently "new".
 *
 * @apiParam {Number} id Contact ID.
 *
 * @apiSampleRequest /api/v1/admin/contacts/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": {
 *     "id": 1,
 *     "name": "Nguyen Van A",
 *     "email": "user@example.com",
 *     "phone": "0901234567",
 *     "subject": "Inquiry",
 *     "message": "Hello, I have a question...",
 *     "status": "read",
 *     "reply": null,
 *     "replied_by": null,
 *     "replied_at": null,
 *     "created_at": "2024-01-01T00:00:00.000000Z"
 *   }
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Contact not found."
 * }
 */

/**
 * @api {post} /api/v1/admin/contacts/:id/reply Reply to Contact
 * @apiName ReplyAdminContact
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Sends a reply to a contact. Updates status to "replied", records replied_by and replied_at.
 *
 * @apiParam {Number} id Contact ID.
 *
 * @apiBody {String} reply Reply content.
 *
 * @apiSampleRequest /api/v1/admin/contacts/1/reply
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Reply sent successfully.",
 *   "data": null
 * }
 *
 * @apiErrorExample {json} Already-Replied:
 * HTTP/1.1 400 Bad Request
 * {
 *   "code": 400,
 *   "message": "This contact has already been replied to."
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Contact not found."
 * }
 */

/**
 * @api {delete} /api/v1/admin/contacts/:id Delete Contact
 * @apiName DeleteAdminContact
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Permanently deletes a contact record.
 *
 * @apiParam {Number} id Contact ID.
 *
 * @apiSampleRequest /api/v1/admin/contacts/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Contact deleted successfully.",
 *   "data": null
 * }
 *
 * @apiErrorExample {json} Not-Found:
 * HTTP/1.1 404 Not Found
 * {
 *   "code": 404,
 *   "message": "Contact not found."
 * }
 */

/**
 * @api {get} /api/v1/admin/contacts/export Export Contacts to Excel
 * @apiName ExportAdminContacts
 * @apiGroup Contacts
 * @apiVersion 1.0.0
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 *
 * @apiDescription Admin endpoint. Exports contacts list as an Excel (.xlsx) file.
 *
 * @apiParam (Query) {String="new","read","replied"} [status] Filter by status.
 *
 * @apiSampleRequest /api/v1/admin/contacts/export
 *
 * @apiSuccessExample {binary} Success-Response:
 * HTTP/1.1 200 OK
 * Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
 * Content-Disposition: attachment; filename="contacts_20240101_120000.xlsx"
 */
