/**
 * @api {get} /api/v1/districts Get Districts
 * @apiName GetDistricts
 * @apiGroup Locations
 * @apiVersion 1.0.0
 *
 * @apiDescription Public endpoint. Returns a static list of districts in Da Nang.
 *
 * @apiSampleRequest /api/v1/districts
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *   "code": 200,
 *   "message": "Success",
 *   "data": [
 *     "Hải Châu",
 *     "Thanh Khê",
 *     "Sơn Trà",
 *     "Ngũ Hành Sơn",
 *     "Liên Chiểu",
 *     "Cẩm Lệ",
 *     "Hòa Vang",
 *     "Hoàng Sa"
 *   ]
 * }
 */
