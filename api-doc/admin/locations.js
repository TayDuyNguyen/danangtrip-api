/**
 * @api {post} /api/v1/admin/locations Admin Create Location
 * @apiName AdminCreateLocation
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Creates a new location.
 *
 * @apiBody {String} name Location name
 * @apiBody {String} [slug] Slug (unique). If empty, it will be generated.
 * @apiBody {Number} category_id Category id
 * @apiBody {Number} [subcategory_id] Subcategory id
 * @apiBody {String} description Description
 * @apiBody {String} [short_description] Short description
 * @apiBody {String} address Address
 * @apiBody {String} district District
 * @apiBody {String} [ward] Ward
 * @apiBody {Number} latitude Latitude
 * @apiBody {Number} longitude Longitude
 * @apiBody {String} [phone] Phone
 * @apiBody {String} [email] Email
 * @apiBody {String} [website] Website URL
 * @apiBody {Object} [opening_hours] Opening hours object
 * @apiBody {Number} [price_min] Min price
 * @apiBody {Number} [price_max] Max price
 * @apiBody {Number{1-4}} [price_level] Price level
 * @apiBody {String} [thumbnail] Thumbnail URL/path
 * @apiBody {Object[]} [images] Images array
 * @apiBody {String} [video_url] Video URL
 * @apiBody {String="active","inactive","pending"} [status] Status
 * @apiBody {Boolean} [is_featured] Featured
 *
 * @apiSampleRequest /api/v1/admin/locations
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 201 Created
 * {
 *   "code": 201,
 *   "message": "Location created successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "New Location",
 *     "slug": "new-location"
 *   }
 * }
 */

/**
 * @api {put} /api/v1/admin/locations/:id Admin Update Location
 * @apiName AdminUpdateLocation
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates an existing location.
 *
 * @apiParam {Number} id Location id
 * @apiBody {String} [name] Location name
 * @apiBody {String} [slug] Slug (unique). If omitted and name changes, slug can be generated.
 * @apiBody {Number} [category_id] Category id
 * @apiBody {Number} [subcategory_id] Subcategory id
 * @apiBody {String} [description] Description
 * @apiBody {String} [address] Address
 * @apiBody {String} [district] District
 * @apiBody {Number} [latitude] Latitude
 * @apiBody {Number} [longitude] Longitude
 * @apiBody {String="active","inactive","pending"} [status] Status
 * @apiBody {Boolean} [is_featured] Featured
 *
 * @apiSampleRequest /api/v1/admin/locations/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Location updated successfully",
 *   "data": {
 *     "id": 1,
 *     "name": "Updated Location",
 *     "slug": "updated-location"
 *   }
 * }
 */

/**
 * @api {delete} /api/v1/admin/locations/:id Admin Delete Location
 * @apiName AdminDeleteLocation
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Deletes a location.
 *
 * @apiParam {Number} id Location id
 *
 * @apiSampleRequest /api/v1/admin/locations/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Location deleted successfully",
 *   "data": null
 * }
 */

/**
 * @api {patch} /api/v1/admin/locations/:id/status Admin Update Location Status
 * @apiName AdminUpdateLocationStatus
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Updates location status.
 *
 * @apiParam {Number} id Location id
 * @apiBody {String="active","inactive","pending"} status New status
 *
 * @apiSampleRequest /api/v1/admin/locations/1/status
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Status updated successfully",
 *   "data": {
 *     "id": 1,
 *     "status": "active"
 *   }
 * }
 */

/**
 * @api {patch} /api/v1/admin/locations/:id/featured Admin Toggle Featured
 * @apiName AdminToggleLocationFeatured
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Toggles location featured status.
 *
 * @apiParam {Number} id Location id
 * @apiBody {Boolean} is_featured Featured flag
 *
 * @apiSampleRequest /api/v1/admin/locations/1/featured
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Featured status toggled successfully",
 *   "data": {
 *     "id": 1,
 *     "is_featured": true
 *   }
 * }
 */

/**
 * @api {get} /api/v1/admin/locations/export Admin Export Locations
 * @apiName AdminExportLocations
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Exports all locations to CSV.
 *
 * @apiSampleRequest /api/v1/admin/locations/export
 *
 * @apiSuccess {File} file CSV file with location data
 */

/**
 * @api {post} /api/v1/admin/locations/:id/tags Admin Attach Tags
 * @apiName AdminAttachLocationTags
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Attaches or syncs tags to a location.
 *
 * @apiParam {Number} id Location id
 * @apiBody {Number[]} tag_ids Array of tag IDs
 *
 * @apiSampleRequest /api/v1/admin/locations/1/tags
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tags attached successfully",
 *   "data": null
 * }
 */

/**
 * @api {delete} /api/v1/admin/locations/:id/tags/:tagId Admin Detach Tag
 * @apiName AdminDetachLocationTag
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Detaches a specific tag from a location.
 *
 * @apiParam {Number} id Location id
 * @apiParam {Number} tagId Tag id
 *
 * @apiSampleRequest /api/v1/admin/locations/1/tags/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Tag detached successfully",
 *   "data": null
 * }
 */

/**
 * @api {post} /api/v1/admin/locations/:id/amenities Admin Attach Amenities
 * @apiName AdminAttachLocationAmenities
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Attaches or syncs amenities to a location.
 *
 * @apiParam {Number} id Location id
 * @apiBody {Number[]} amenity_ids Array of amenity IDs
 *
 * @apiSampleRequest /api/v1/admin/locations/1/amenities
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Amenities attached successfully",
 *   "data": null
 * }
 */

/**
 * @api {delete} /api/v1/admin/locations/:id/amenities/:amenityId Admin Detach Amenity
 * @apiName AdminDetachLocationAmenity
 * @apiGroup Admin Locations
 * @apiVersion 1.0.0
 *
 * @apiHeader {String} Authorization Bearer token (JWT)
 * @apiPermission admin
 *
 * @apiDescription Admin endpoint. Detaches a specific amenity from a location.
 *
 * @apiParam {Number} id Location id
 * @apiParam {Number} amenityId Amenity id
 *
 * @apiSampleRequest /api/v1/admin/locations/1/amenities/1
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Amenity detached successfully",
 *   "data": null
 * }
 */
