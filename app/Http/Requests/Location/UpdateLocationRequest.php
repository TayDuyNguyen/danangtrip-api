<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:locations,id',
            ],
            'name' => [
                'sometimes',
                'string',
                'max:200',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:220',
                Rule::unique('locations', 'slug')->ignore($this->route('id')),
            ],
            'category_id' => [
                'sometimes',
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
                'sometimes',
                'string',
            ],
            'address' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'district' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'latitude' => [
                'sometimes',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'sometimes',
                'numeric',
                'between:-180,180',
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
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
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
            'address.required' => 'The address is required. (Địa chỉ là bắt buộc.)',
            'address.max' => 'The address must not exceed 255 characters. (Địa chỉ không được vượt quá 255 ký tự.)',
            'district.required' => 'The district is required. (Quận/Huyện là bắt buộc.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/Huyện không được vượt quá 50 ký tự.)',
            'latitude.required' => 'The latitude is required. (Vĩ độ là bắt buộc.)',
            'latitude.numeric' => 'The latitude must be a number. (Vĩ độ phải là số.)',
            'latitude.between' => 'The latitude must be between -90 and 90. (Vĩ độ phải nằm trong khoảng -90 đến 90.)',
            'longitude.required' => 'The longitude is required. (Kinh độ là bắt buộc.)',
            'longitude.numeric' => 'The longitude must be a number. (Kinh độ phải là số.)',
            'longitude.between' => 'The longitude must be between -180 and 180. (Kinh độ phải nằm trong khoảng -180 đến 180.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'Status is required. (Trạng thái là bắt buộc.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'is_featured.required' => 'The is_featured field is required. (Trường nổi bật là bắt buộc.)',
        ];
    }
}
