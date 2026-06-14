<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // General settings
            [
                'key' => 'general.hotline',
                'value' => '1900 1800',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'general.email',
                'value' => 'info@danangtrip.com',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'general.address',
                'value' => '123 Bach Dang, Hai Chau, Da Nang',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'general.support_hours',
                'value' => '08:00 - 22:00',
                'value_type' => 'string',
                'is_public' => true,
            ],

            // Brand settings
            [
                'key' => 'brand.website_name',
                'value' => 'DaNangTrip',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'brand.logo',
                'value' => 'https://res.cloudinary.com/dmukxquza/image/upload/v1781012077/danangtrip/branding/logo/danangtrip-logo.png',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'brand.favicon',
                'value' => 'https://res.cloudinary.com/dmukxquza/image/upload/v1781012079/danangtrip/branding/favicon/danangtrip-favicon.png',
                'value_type' => 'string',
                'is_public' => true,
            ],

            // Social media links
            [
                'key' => 'social.facebook',
                'value' => 'https://facebook.com/danangtrip',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'social.instagram',
                'value' => 'https://instagram.com/danangtrip',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'social.youtube',
                'value' => 'https://youtube.com/danangtrip',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'social.tiktok',
                'value' => 'https://tiktok.com/@danangtrip',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'social.zalo',
                'value' => 'https://zalo.me/danangtrip',
                'value_type' => 'string',
                'is_public' => true,
            ],

            // Payment settings
            [
                'key' => 'payment.sepay',
                'value' => 'true',
                'value_type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'payment.cod',
                'value' => 'true',
                'value_type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'payment.vnpay',
                'value' => 'false',
                'value_type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'payment.momo',
                'value' => 'false',
                'value_type' => 'boolean',
                'is_public' => true,
            ],
            [
                'key' => 'payment.zalopay',
                'value' => 'false',
                'value_type' => 'boolean',
                'is_public' => true,
            ],

            // Policies
            [
                'key' => 'policy.terms',
                'value' => 'https://danangtrip.com/terms',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'policy.privacy',
                'value' => 'https://danangtrip.com/privacy',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'policy.data_protection',
                'value' => 'https://danangtrip.com/data-protection',
                'value_type' => 'string',
                'is_public' => true,
            ],

            // Default SEO tags
            [
                'key' => 'seo.meta_title',
                'value' => 'DaNangTrip - Du lịch Đà Nẵng trọn vẹn',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'seo.meta_description',
                'value' => 'Đặt tour du lịch Đà Nẵng giá rẻ, khám phá các địa danh nổi tiếng Bà Nà Hills, Hội An, Ngũ Hành Sơn cùng DaNangTrip.',
                'value_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'seo.og_image',
                'value' => 'https://res.cloudinary.com/dmukxquza/image/upload/v1781012083/danangtrip/branding/og/danangtrip-og-image.jpg',
                'value_type' => 'string',
                'is_public' => true,
            ],
            // Chatbot settings
            [
                'key' => 'chatbot.enabled',
                'value' => 'true',
                'value_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'chatbot.clarification_attempt_limit',
                'value' => '2',
                'value_type' => 'number',
                'is_public' => false,
            ],
            [
                'key' => 'chatbot.cache_ttl_seconds',
                'value' => '86400',
                'value_type' => 'number',
                'is_public' => false,
            ],
            [
                'key' => 'chatbot.cache',
                'value' => '{"threshold_transactional":0.97,"threshold_faq":0.92}',
                'value_type' => 'json',
                'is_public' => false,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
