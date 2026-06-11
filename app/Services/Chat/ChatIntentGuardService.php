<?php

namespace App\Services\Chat;

final class ChatIntentGuardService
{
    /** @return array{intent:string,is_in_scope:bool,reason:string|null} */
    public function classify(string $message): array
    {
        $text = $this->normalize($message);
        $text = $this->applyAliases($text);

        if ($text === '') {
            return ['intent' => 'empty', 'is_in_scope' => false, 'reason' => 'empty'];
        }

        $blocked = [
            'viet code', 'viết code', 'code game', 'debug code', 'lập trình',
            'chính trị', 'bầu cử', 'đảng phái', 'crypto', 'chứng khoán',
            'giải toán', 'bài tập', 'dịch hộ', 'viết luận', 'hack', 'crack',
        ];

        foreach ($blocked as $keyword) {
            if (str_contains($text, $keyword)) {
                return ['intent' => 'out_of_scope', 'is_in_scope' => false, 'reason' => 'blocked_keyword'];
            }
        }

        $intents = [
            'loyalty' => [
                'điểm thưởng', 'diem thuong', 'tích điểm', 'tich diem', 'nhận điểm', 'nhan diem',
                'cộng điểm', 'cong diem', 'ví điểm', 'vi diem', 'point', 'points', 'loyalty',
                'voucher', 'mã giảm giá', 'ma giam gia', 'khuyến mãi', 'khuyen mai',
                'đánh giá hữu ích', 'danh gia huu ich', 'bình luận hữu ích', 'binh luan huu ich',
            ],
            'payment' => ['thanh toán', 'qr', 'chuyển khoản', 'sepay', 'hoá đơn', 'hóa đơn', 'đã trả tiền'],
            'refund' => ['hoàn tiền', 'hủy tour', 'huỷ tour', 'chính sách hủy', 'chính sách huỷ', 'đổi lịch'],
            'booking' => ['đặt tour', 'booking', 'đơn hàng', 'đặt chỗ', 'giữ chỗ', 'xác nhận đơn'],
            'blog' => ['bài viết', 'bai viet', 'cẩm nang', 'cam nang', 'kinh nghiệm', 'kinh nghiem', 'blog', 'tin tức', 'tin tuc', 'review', 'hướng dẫn', 'huong dan'],
            'tour' => ['tour', 'tua', 'du lịch', 'du lich', 'rẻ nhất', 're nhat', 'giá rẻ', 'gia re', 'bà nà', 'ba na', 'hội an', 'hoi an', 'huế', 'hue', 'cù lao chàm', 'mỹ sơn', 'ngũ hành sơn'],
            'food' => ['ăn', 'uống', 'ẩm thực', 'đặc sản', 'nhà hàng', 'quán', 'hải sản', 'mì quảng', 'bún chả cá'],
            'hotel' => ['khách sạn', 'resort', 'homestay', 'lưu trú', 'chỗ ở', 'hotel'],
            'location' => ['địa điểm', 'đi đâu', 'tham quan', 'check-in', 'cầu rồng', 'sơn trà', 'mỹ khê', 'biển'],
            'schedule' => ['lịch trình', 'kế hoạch', 'mấy ngày', '3 ngày', '2 ngày', '1 ngày', 'itinerary'],
            'account' => ['tài khoản', 'đăng nhập', 'đăng ký', 'mật khẩu', 'profile', 'hồ sơ'],
            'contact' => ['liên hệ', 'hotline', 'email', 'hỗ trợ', 'tư vấn'],
            'greeting' => ['xin chào', 'hello', 'hi ', 'chào bạn', 'alo'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return ['intent' => $intent, 'is_in_scope' => true, 'reason' => null];
                }
            }
        }

        return ['intent' => 'out_of_scope', 'is_in_scope' => false, 'reason' => 'no_travel_intent'];
    }

    private function normalize(string $message): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($message));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($message));
    }

    private function applyAliases(string $text): string
    {
        $aliases = [
            'toà' => 'tour',
            'toa' => 'tour',
            'tuor' => 'tour',
            'tua ' => 'tour ',
            'tua du lich' => 'tour du lich',
            'du lich' => 'du lịch',
            're nhat' => 'rẻ nhất',
            'gia re' => 'giá rẻ',
            'thanh toan' => 'thanh toán',
            'hoan tien' => 'hoàn tiền',
            'dat tour' => 'đặt tour',
            'diem thuong' => 'điểm thưởng',
            'tich diem' => 'tích điểm',
            'nhan diem' => 'nhận điểm',
            'cong diem' => 'cộng điểm',
            'vi diem' => 'ví điểm',
            'ma giam gia' => 'mã giảm giá',
            'khuyen mai' => 'khuyến mãi',
            'danh gia huu ich' => 'đánh giá hữu ích',
            'binh luan huu ich' => 'bình luận hữu ích',
            'ba na' => 'bà nà',
            'hoi an' => 'hội an',
            'da nang' => 'đà nẵng',
            'ks' => 'khách sạn',
        ];

        return strtr($text, $aliases);
    }
}
