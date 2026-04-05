<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class LocationValidation
 * Provides centralized validation logic for location management.
 */
final class LocationValidation
{
    /**
     * Validate show location request.
     * (Xác thực yêu cầu chi tiết địa điểm)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:locations,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate location ratings request.
     * (Xác thực yêu cầu đánh giá địa điểm)
     */
    public static function validateRatings(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'sort_by' => 'sometimes|in:created_at,rating',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate featured locations request
     * (Xác thực yêu cầu địa điểm nổi bật)
     */
    public static function validateFeatured(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'limit' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate delete location request.
     * (Xác thực yêu cầu xóa địa điểm)
     */
    public static function validateDelete(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:locations,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate list locations request
     * (Xác thực yêu cầu danh sách địa điểm)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'category_id' => 'sometimes|integer|exists:categories,id',
                'subcategory_id' => 'sometimes|integer|exists:subcategories,id',
                'district' => 'sometimes|string|max:50',
                'search' => 'sometimes|string|max:100',
                'price_level' => 'sometimes|integer|between:1,4',
                'is_featured' => 'sometimes|boolean',
                'sort_by' => 'sometimes|in:avg_rating,review_count,view_count,created_at,price_min',
                'sort_order' => 'sometimes|in:asc,desc',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate nearby locations request
     * (Xác thực yêu cầu địa điểm lân cận)
     */
    public static function validateNearby(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'radius' => 'sometimes|numeric|min:0.1|max:50', // km
                'limit' => 'sometimes|integer|min:1|max:100',
                'sort_by' => 'sometimes|in:avg_rating,review_count,view_count,created_at,price_min',
                'sort_order' => 'sometimes|in:asc,desc',

            ],
            self::messages()
        );
    }

    /**
     * Validate store location request
     * (Xác thực yêu cầu tạo địa điểm)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:200',
                'slug' => 'sometimes|nullable|string|max:220|unique:locations,slug',
                'category_id' => 'required|integer|exists:categories,id',
                'subcategory_id' => 'sometimes|nullable|integer|exists:subcategories,id',
                'description' => 'required|string',
                'short_description' => 'sometimes|nullable|string|max:500',
                'address' => 'required|string|max:255',
                'district' => 'required|string|max:50',
                'ward' => 'sometimes|nullable|string|max:50',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'phone' => 'sometimes|nullable|string|max:20',
                'email' => 'sometimes|nullable|email|max:100',
                'website' => 'sometimes|nullable|url|max:255',
                'opening_hours' => 'sometimes|nullable|array',
                'price_min' => 'sometimes|nullable|numeric|min:0',
                'price_max' => 'sometimes|nullable|numeric|min:0',
                'price_level' => 'sometimes|nullable|integer|between:1,4',
                'thumbnail' => 'sometimes|nullable|string|max:255',
                'images' => 'sometimes|nullable|array',
                'video_url' => 'sometimes|nullable|url|max:255',
                'status' => 'sometimes|in:active,inactive,pending',
                'is_featured' => 'sometimes|boolean',
            ],
            self::messages()
        );
    }

    /**
     * Validate update location request
     * (Xác thực yêu cầu cập nhật địa điểm)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'name' => 'sometimes|string|max:200',
                'slug' => 'sometimes|nullable|string|max:220|unique:locations,slug,'.$id.',id',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'subcategory_id' => 'sometimes|nullable|integer|exists:subcategories,id',
                'description' => 'sometimes|string',
                'address' => 'sometimes|string|max:255',
                'district' => 'sometimes|string|max:50',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180',
                'status' => 'sometimes|in:active,inactive,pending',
                'is_featured' => 'sometimes|boolean',
            ],
            self::messages()
        );
    }

    /**
     * Validate status update
     * (Xác thực yêu cầu cập nhật trạng thái)
     */
    public static function validatePatchStatus(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'status' => 'required|in:active,inactive,pending',
            ],
            self::messages()
        );
    }

    /**
     * Validate featured toggle
     * (Xác thực yêu cầu đánh dấu địa điểm nổi bật)
     */
    public static function validatePatchFeatured(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'is_featured' => 'required|boolean',
            ],
            self::messages()
        );
    }

    /**
     * Validate record view request
     * (Xác thực yêu cầu ghi lại lượt xem)
     */
    public static function validateRecordView(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'session_id' => 'sometimes|nullable|string|max:255',
            ],
            self::messages()
        );
    }

    /**
     * Validation destroy
     * (Xác thực yêu cầu xóa địa điểm)
     */
    public static function validateDestroy(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:locations,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate rating stats request.
     * (Xác thực yêu cầu thống kê đánh giá)
     */
    public static function validateRatingStats(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:locations,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate nearby locations by ID request.
     * (Xác thực yêu cầu địa điểm lân cận theo ID)
     */
    public static function validateNearbyById(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'limit' => 'sometimes|integer|min:1|max:50',
            ],
            self::messages()
        );
    }

    /**
     * Validate attach tags request.
     * (Xác thực yêu cầu gán tags)
     */
    public static function validateAttachTags(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'tag_ids' => 'required|array|min:1',
                'tag_ids.*' => 'integer|exists:tags,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate attach amenities request.
     * (Xác thực yêu cầu gán tiện ích)
     */
    public static function validateAttachAmenities(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:locations,id',
                'amenity_ids' => 'required|array|min:1',
                'amenity_ids.*' => 'integer|exists:amenities,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate detach tag request.
     * (Xác thực yêu cầu bỏ gán tag)
     */
    public static function validateDetachTag(int $id, int $tagId): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id, 'tag_id' => $tagId],
            [
                'id' => 'required|integer|exists:locations,id',
                'tag_id' => 'required|integer|exists:tags,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate detach amenity request.
     * (Xác thực yêu cầu bỏ gán tiện ích)
     */
    public static function validateDetachAmenity(int $id, int $amenityId): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id, 'amenity_id' => $amenityId],
            [
                'id' => 'required|integer|exists:locations,id',
                'amenity_id' => 'required|integer|exists:amenities,id',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    protected static function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'category_id.required' => 'The primary category ID is required. (Mã danh mục chính là bắt buộc.)',
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'name.required' => 'The location name is required. (Tên địa điểm là bắt buộc.)',
            'name.string' => 'The location name must be a string. (Tên địa điểm phải là chuỗi ký tự.)',
            'name.max' => 'The location name must not exceed 200 characters. (Tên địa điểm không được vượt quá 200 ký tự.)',
            'slug.unique' => 'This slug is already taken. (Slug này đã tồn tại.)',
            'slug.max' => 'The slug must not exceed 220 characters. (Slug không được vượt quá 220 ký tự.)',
            'description.required' => 'The location description is required. (Mô tả địa điểm là bắt buộc.)',
            'short_description.max' => 'The short description must not exceed 500 characters. (Mô tả ngắn không được vượt quá 500 ký tự.)',
            'address.required' => 'The address is required. (Địa chỉ là bắt buộc.)',
            'address.max' => 'The address must not exceed 255 characters. (Địa chỉ không được vượt quá 255 ký tự.)',
            'district.required' => 'The district is required. (Quận/Huyện là bắt buộc.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/Huyện không được vượt quá 50 ký tự.)',
            'ward.max' => 'The ward must not exceed 50 characters. (Phường/Xã không được vượt quá 50 ký tự.)',
            'latitude.required' => 'The latitude is required. (Vĩ độ là bắt buộc.)',
            'latitude.numeric' => 'The latitude must be a number. (Vĩ độ phải là số.)',
            'latitude.between' => 'The latitude must be between -90 and 90. (Vĩ độ phải nằm trong khoảng -90 đến 90.)',
            'longitude.required' => 'The longitude is required. (Kinh độ là bắt buộc.)',
            'longitude.numeric' => 'The longitude must be a number. (Kinh độ phải là số.)',
            'longitude.between' => 'The longitude must be between -180 and 180. (Kinh độ phải nằm trong khoảng -180 đến 180.)',
            'lat.required' => 'Latitude is required for nearby search. (Vĩ độ là bắt buộc để tìm kiếm lân cận.)',
            'lat.numeric' => 'Latitude must be a number. (Vĩ độ phải là số.)',
            'lat.between' => 'Latitude must be between -90 and 90. (Vĩ độ phải nằm trong khoảng -90 đến 90.)',
            'lng.required' => 'Longitude is required for nearby search. (Kinh độ là bắt buộc để tìm kiếm lân cận.)',
            'lng.numeric' => 'Longitude must be a number. (Kinh độ phải là số.)',
            'lng.between' => 'Longitude must be between -180 and 180. (Kinh độ phải nằm trong khoảng -180 đến 180.)',
            'radius.numeric' => 'The radius must be a number. (Bán kính phải là số.)',
            'radius.min' => 'The radius must be at least 0.1 km. (Bán kính tối thiểu là 0.1 km.)',
            'radius.max' => 'The radius must not exceed 50 km. (Bán kính tối đa là 50 km.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'Status is required. (Trạng thái là bắt buộc.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'is_featured.required' => 'The is_featured field is required. (Trường nổi bật là bắt buộc.)',
            'price_level.integer' => 'The price level must be an integer. (Mức giá phải là số nguyên.)',
            'price_level.between' => 'The price level must be between 1 and 4. (Mức giá phải nằm trong khoảng từ 1 đến 4.)',
            'price_min.numeric' => 'The minimum price must be a number. (Giá tối thiểu phải là số.)',
            'price_min.min' => 'The minimum price must be at least 0. (Giá tối thiểu phải từ 0 trở lên.)',
            'price_max.numeric' => 'The maximum price must be a number. (Giá tối đa phải là số.)',
            'price_max.min' => 'The maximum price must be at least 0. (Giá tối đa phải từ 0 trở lên.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The selected sort order is invalid. (Thứ tự sắp xếp không hợp lệ.)',
            'page.integer' => 'The page number must be an integer. (Số trang phải là số nguyên.)',
            'page.min' => 'The page number must be at least 1. (Số trang tối thiểu là 1.)',
            'per_page.integer' => 'The items per page must be an integer. (Số lượng mỗi trang phải là số nguyên.)',
            'per_page.min' => 'The items per page must be at least 1. (Số lượng mỗi trang tối thiểu là 1.)',
            'per_page.max' => 'The items per page must not exceed 100. (Số lượng mỗi trang tối đa là 100.)',
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn tối thiểu là 1.)',
            'limit.max' => 'The limit must not exceed 100. (Giới hạn tối đa là 100.)',
            'search.max' => 'The search term must not exceed 100 characters. (Từ khóa tìm kiếm không được vượt quá 100 ký tự.)',
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'website.url' => 'Please provide a valid website URL. (Vui lòng cung cấp URL trang web hợp lệ.)',
            'website.max' => 'The website URL must not exceed 255 characters. (URL trang web không được vượt quá 255 ký tự.)',
            'video_url.url' => 'Please provide a valid video URL. (Vui lòng cung cấp URL video hợp lệ.)',
            'video_url.max' => 'The video URL must not exceed 255 characters. (URL video không được vượt quá 255 ký tự.)',
            'opening_hours.array' => 'Opening hours must be an array. (Giờ mở cửa phải là một mảng.)',
            'images.array' => 'Images must be an array. (Hình ảnh phải là một mảng.)',
            'thumbnail.max' => 'The thumbnail URL must not exceed 255 characters. (URL ảnh thu nhỏ không được vượt quá 255 ký tự.)',
            'session_id.required' => 'The session ID is required. (Mã phiên là bắt buộc.)',
            'session_id.string' => 'The session ID must be a string. (Mã phiên phải là chuỗi ký tự.)',
            'session_id.max' => 'The session ID must not exceed 255 characters. (Mã phiên không được vượt quá 255 ký tự.)',
            'tag_ids.required' => 'At least one tag ID is required. (Ít nhất một mã tag là bắt buộc.)',
            'tag_ids.array' => 'Tag IDs must be an array. (Danh sách mã tag phải là một mảng.)',
            'tag_ids.*.exists' => 'One or more tag IDs are invalid. (Một hoặc nhiều mã tag không hợp lệ.)',
            'amenity_ids.required' => 'At least one amenity ID is required. (Ít nhất một mã tiện ích là bắt buộc.)',
            'amenity_ids.array' => 'Amenity IDs must be an array. (Danh sách mã tiện ích phải là một mảng.)',
            'amenity_ids.*.exists' => 'One or more amenity IDs are invalid. (Một hoặc nhiều mã tiện ích không hợp lệ.)',
        ];
    }
}
