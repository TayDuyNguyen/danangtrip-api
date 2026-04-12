/**
 * @api {post} /api/v1/upload/image Upload Single Image
 * @apiName UploadSingleImage
 * @apiGroup Upload
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Uploads a single image to Cloudinary.
 *
 * @apiBody {File} image Image file (jpeg, png, webp, max 5MB)
 * @apiBody {String} [folder] Optional folder name in Cloudinary
 *
 * @apiSampleRequest /api/v1/upload/image
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Image uploaded successfully.",
 *   "data": {
 *     "url": "https://res.cloudinary.com/...",
 *     "public_id": "folder/image_id",
 *     "asset_id": "asset_id"
 *   }
 * }
 */

/**
 * @api {post} /api/v1/upload/images Upload Multiple Images
 * @apiName UploadMultipleImages
 * @apiGroup Upload
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Uploads multiple images to Cloudinary (max 10).
 *
 * @apiBody {File[]} images Array of image files (jpeg, png, webp, max 5MB each)
 * @apiBody {String} [folder] Optional folder name in Cloudinary
 *
 * @apiSampleRequest /api/v1/upload/images
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Images uploaded successfully.",
 *   "data": {
 *     "items": [
 *       {
 *         "url": "https://res.cloudinary.com/...",
 *         "public_id": "folder/image_id_1",
 *         "asset_id": "asset_id_1"
 *       },
 *       {
 *         "url": "https://res.cloudinary.com/...",
 *         "public_id": "folder/image_id_2",
 *         "asset_id": "asset_id_2"
 *       }
 *     ]
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/upload/image Delete Image
 * @apiName DeleteUploadedImage
 * @apiGroup Upload
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission user
 *
 * @apiDescription Protected endpoint - Deletes an image from Cloudinary using its public_id.
 *
 * @apiBody {String} public_id The public ID of the image to delete
 *
 * @apiSampleRequest /api/v1/upload/image
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Image deleted successfully.",
 *   "data": null
 * }
 */
