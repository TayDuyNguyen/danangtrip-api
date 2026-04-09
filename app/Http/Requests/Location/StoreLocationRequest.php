<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:200',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:220',
                'unique:locations,slug',
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            'subcategory_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:subcategories,id',
            ],
            'description' => [
                'required',
                'string',
            ],
            'short_description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'address' => [
                'required',
                'string',
                'max:255',
            ],
            'district' => [
                'required',
                'string',
                'max:50',
            ],
            'ward' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:100',
            ],
            'website' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            'opening_hours' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'price_min' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_max' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_level' => [
                'sometimes',
                'nullable',
                'integer',
                'between:1,4',
            ],
            'thumbnail' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'images' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'video_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'in:active,inactive,pending',
            ],
            'is_featured' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The location name is required. (Tên địa điểm là bắt buộc.)',
            'name.string' => 'The location name must be a string. (Tên địa điểm phải là chuỗi ký tự.)',
            'name.max' => 'The location name must not exceed 200 characters. (Tên địa điểm không được vượt quá 200 ký tự.)',
            'slug.unique' => 'This slug is already taken. (Slug này đã tồn tại.)',
            'slug.max' => 'The slug must not exceed 220 characters. (Slug không được vượt quá 220 ký tự.)',
            'category_id.required' => 'The primary category ID is required. (Mã danh mục chính là bắt buộc.)',
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
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
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'website.url' => 'Please provide a valid website URL. (Vui lòng cung cấp URL trang web hợp lệ.)',
            'website.max' => 'The website URL must not exceed 255 characters. (URL trang web không được vượt quá 255 ký tự.)',
            'opening_hours.array' => 'Opening hours must be an array. (Giờ mở cửa phải là một mảng.)',
            'price_min.numeric' => 'The minimum price must be a number. (Giá tối thiểu phải là số.)',
            'price_min.min' => 'The minimum price must be at least 0. (Giá tối thiểu phải từ 0 trở lên.)',
            'price_max.numeric' => 'The maximum price must be a number. (Giá tối đa phải là số.)',
            'price_max.min' => 'The maximum price must be at least 0. (Giá tối đa phải từ 0 trở lên.)',
            'price_level.integer' => 'The price level must be an integer. (Mức giá phải là số nguyên.)',
            'price_level.between' => 'The price level must be between 1 and 4. (Mức giá phải nằm trong khoảng từ 1 đến 4.)',
            'thumbnail.max' => 'The thumbnail URL must not exceed 255 characters. (URL ảnh thu nhỏ không được vượt quá 255 ký tự.)',
            'images.array' => 'Images must be an array. (Hình ảnh phải là một mảng.)',
            'video_url.url' => 'Please provide a valid video URL. (Vui lòng cung cấp URL video hợp lệ.)',
            'video_url.max' => 'The video URL must not exceed 255 characters. (URL video không được vượt quá 255 ký tự.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'Status is required. (Trạng thái là bắt buộc.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'is_featured.required' => 'The is_featured field is required. (Trường nổi bật là bắt buộc.)',
        ];
    }
}
