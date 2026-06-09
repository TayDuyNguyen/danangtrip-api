<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);

        if (is_array($settings) && isset($settings['payment']) && is_array($settings['payment'])) {
            if (! array_key_exists('sepay', $settings['payment']) && array_key_exists('payos', $settings['payment'])) {
                $settings['payment']['sepay'] = $settings['payment']['payos'];
            }

            unset($settings['payment']['payos']);
            $this->merge(['settings' => $settings]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth guard is already handled by routing middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',

            // General settings
            'settings.general' => 'required|array',
            'settings.general.hotline' => ['required', 'string', 'regex:/^(0[35789])[0-9]{8}$|^(1900|1800)\s?[0-9]{4}$/'],
            'settings.general.email' => 'required|email',
            'settings.general.address' => 'required|string|max:255',
            'settings.general.support_hours' => 'required|string|max:100',

            // Brand settings
            'settings.brand' => 'required|array',
            'settings.brand.website_name' => 'required|string|max:50',
            'settings.brand.logo' => 'required|string|max:2048',
            'settings.brand.favicon' => 'required|string|max:2048',

            // Social links
            'settings.social' => 'required|array',
            'settings.social.facebook' => 'nullable|url|max:2048',
            'settings.social.instagram' => 'nullable|url|max:2048',
            'settings.social.youtube' => 'nullable|url|max:2048',
            'settings.social.tiktok' => 'nullable|url|max:2048',
            'settings.social.zalo' => 'nullable|url|max:2048',

            // Payment settings
            'settings.payment' => 'required|array',
            'settings.payment.sepay' => 'required|boolean',
            'settings.payment.cod' => 'required|boolean',
            'settings.payment.vnpay' => 'required|boolean',
            'settings.payment.momo' => 'required|boolean',
            'settings.payment.zalopay' => 'required|boolean',

            // Policies
            'settings.policy' => 'required|array',
            'settings.policy.terms' => 'nullable|url|max:2048',
            'settings.policy.privacy' => 'nullable|url|max:2048',
            'settings.policy.data_protection' => 'nullable|url|max:2048',

            // default SEO
            'settings.seo' => 'required|array',
            'settings.seo.meta_title' => 'required|string|min:10|max:100',
            'settings.seo.meta_description' => 'required|string|min:20|max:200',
            'settings.seo.og_image' => 'nullable|string|max:2048',
        ];
    }

    /**
     * Add after-validation constraints.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $payment = $this->input('settings.payment', []);
            $anyEnabled = false;

            foreach (['sepay', 'cod', 'vnpay', 'momo', 'zalopay'] as $gateway) {
                if (isset($payment[$gateway]) && filter_var($payment[$gateway], FILTER_VALIDATE_BOOLEAN) === true) {
                    $anyEnabled = true;
                    break;
                }
            }

            if (! $anyEnabled) {
                $validator->errors()->add('settings.payment', 'At least one payment method must be enabled.');
            }
        });
    }

    /**
     * Custom validation messages in Vietnamese.
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Dữ liệu cấu hình không được để trống.',
            'settings.general.hotline.regex' => 'Định dạng hotline hoặc số điện thoại không hợp lệ.',
            'settings.general.email.email' => 'Địa chỉ email không hợp lệ.',
            'settings.payment.required' => 'Thông tin cổng thanh toán bắt buộc nhập.',
        ];
    }
}
