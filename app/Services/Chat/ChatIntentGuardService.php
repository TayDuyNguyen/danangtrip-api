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
            'bệnh viện', 'thuốc', 'triệu chứng', 'chữa bệnh', 'y tế',
            'pháp luật', 'luật sư', 'tòa án', 'thể thao', 'bóng đá',
        ];

        foreach ($blocked as $keyword) {
            if (str_contains($text, $keyword)) {
                return ['intent' => 'out_of_scope', 'is_in_scope' => false, 'reason' => 'blocked_keyword'];
            }
        }

        $intents = [
            // === Greeting — ALWAYS in_scope, fast path ===
            'greeting' => [
                // Có dấu
                'xin chào', 'chào bạn', 'chào buổi', 'bạn là ai', 'bạn là gì',
                'bạn có thể', 'bạn giúp', 'tôi cần giúp',
                // Không dấu (người dùng hay gõ tắt)
                'xin chao', 'chao ban', 'chao buoi', 'ban la ai',
                'ban co the', 'ban giup', 'toi can giup',
                // English
                'hello', 'hi ', 'hi!', 'hey', 'alo', 'howdy',
                'good morning', 'good afternoon', 'good evening', 'good day',
                'what are you', 'who are you', 'help me', 'i need help',
            ],

            // === Loyalty & Vouchers ===
            'loyalty' => [
                'điểm thưởng', 'diem thuong', 'tích điểm', 'tich diem',
                'nhận điểm', 'nhan diem', 'cộng điểm', 'cong diem',
                'ví điểm', 'vi diem', 'point', 'points', 'loyalty',
                'voucher', 'mã giảm giá', 'ma giam gia', 'khuyến mãi', 'khuyen mai',
                'đánh giá hữu ích', 'danh gia huu ich', 'bình luận hữu ích',
            ],

            // === Human Handoff / Support ===
            'handoff' => [
                'hoá đơn đỏ', 'hóa đơn đỏ', 'vat', 'xuất vat', 'xuất hóa đơn',
                'khiếu nại', 'phàn nàn', 'than phiền', 'complaint', 'complaints',
                'gặp nhân viên', 'nhân viên hỗ trợ', 'gặp tư vấn viên', 'gặp người thật',
                'hotline', 'tổng đài', 'số điện thoại hỗ trợ', 'zalo chat', 'chat zalo',
                'chuyển giao', 'nhân viên trực', 'gap nguoi that', 'gap tu van vien',
                'gap nhan vien', 'khieu nai', 'phan nan',
            ],

            // === Payment & Booking ===
            'payment' => [
                'thanh toán', 'qr', 'chuyển khoản', 'sepay',
                'đã trả tiền', 'pay', 'payment',
            ],
            'refund' => [
                'hoàn tiền', 'hủy tour', 'huỷ tour',
                'chính sách hủy', 'chính sách huỷ', 'đổi lịch', 'refund', 'cancel',
            ],
            'booking' => [
                'đặt tour', 'booking', 'đơn hàng', 'đặt chỗ',
                'giữ chỗ', 'xác nhận đơn', 'book', 'reserve', 'đặt vé',
            ],

            // === Blog / Articles ===
            'blog' => [
                'bài viết', 'bai viet', 'cẩm nang', 'cam nang',
                'kinh nghiệm', 'kinh nghiem', 'blog', 'tin tức', 'tin tuc',
                'review', 'hướng dẫn', 'huong dan', 'article', 'guide',
                'đọc bài', 'xem bài', 'bài về', 'tips', 'mẹo',
            ],

            // === Itinerary & Schedule ===
            'schedule' => [
                'lịch trình', 'kế hoạch', 'mấy ngày', '3 ngày', '2 ngày',
                '1 ngày', 'itinerary', 'schedule', 'plan', 'lên kế hoạch',
                'ngày mấy', 'bao lâu', 'chuyến đi', 'hành trình',
            ],

            // === Tours ===
            'tour' => [
                'tour', 'tua', 'du lịch', 'du lich', 'rẻ nhất', 're nhat',
                'giá rẻ', 'gia re', 'bà nà', 'ba na', 'hội an', 'hoi an',
                'huế', 'hue', 'cù lao chàm', 'mỹ sơn', 'ngũ hành sơn',
                'excursion', 'trip', 'day trip', 'package',
                'cần mua tour', 'muốn đặt tour', 'tìm tour',
            ],

            // === Food & Dining ===
            'food' => [
                'ăn', 'uống', 'ẩm thực', 'đặc sản', 'nhà hàng', 'quán',
                'hải sản', 'mì quảng', 'bún chả cá', 'nên ăn gì', 'ăn gì',
                'food', 'restaurant', 'eat', 'drink', 'local food', 'cuisine',
                'quán ăn', 'seafood', 'cafe', 'cà phê', 'coffee',
                'món ngon', 'đặc sản', 'bánh', 'quán ngon',
            ],

            // === Hotels & Accommodation ===
            'hotel' => [
                'khách sạn', 'resort', 'homestay', 'lưu trú', 'chỗ ở', 'hotel',
                'accommodation', 'stay', 'sleep', 'ở đâu', 'nên ở đâu',
                'phòng', 'villa', 'hostel', 'motel', 'airbnb',
            ],

            // === Locations & Attractions ===
            'location' => [
                'địa điểm', 'đi đâu', 'tham quan', 'check-in', 'checkin',
                'cầu rồng', 'sơn trà', 'mỹ khê', 'biển', 'nên đi đâu',
                'có gì hay', 'gợi ý', 'recommend', 'attraction', 'place',
                'điểm đến', 'thắng cảnh', 'di tích', 'museum', 'bảo tàng',
                'bãi biển', 'beach', 'island', 'núi', 'mountain',
                'chùa', 'temple', 'market', 'chợ', 'vui chơi', 'giải trí',
            ],

            // === Account & Profile ===
            'account' => [
                'tài khoản', 'đăng nhập', 'đăng ký', 'mật khẩu',
                'profile', 'hồ sơ', 'login', 'register', 'password',
                'account', 'sign in', 'sign up',
            ],

            // === Contact & Support ===
            'contact' => [
                'liên hệ', 'hotline', 'email', 'hỗ trợ', 'tư vấn',
                'contact', 'support', 'help', 'chat với người', 'gặp nhân viên',
            ],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return ['intent' => $intent, 'is_in_scope' => true, 'reason' => null];
                }
            }
        }

        // Default: assume travel-related if no blocked keywords found
        // Many short/vague travel queries won't match exact keywords
        return ['intent' => 'location', 'is_in_scope' => true, 'reason' => 'default_travel'];
    }

    private function normalize(string $message): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($message));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($message));
    }

    private function applyAliases(string $text): string
    {
        $aliases = [
            'toà'           => 'tour',
            'toa'           => 'tour',
            'tuor'          => 'tour',
            'tua '          => 'tour ',
            'tua du lich'   => 'tour du lich',
            'du lich'       => 'du lịch',
            're nhat'       => 'rẻ nhất',
            'gia re'        => 'giá rẻ',
            'thanh toan'    => 'thanh toán',
            'hoan tien'     => 'hoàn tiền',
            'dat tour'      => 'đặt tour',
            'diem thuong'   => 'điểm thưởng',
            'tich diem'     => 'tích điểm',
            'nhan diem'     => 'nhận điểm',
            'cong diem'     => 'cộng điểm',
            'vi diem'       => 'ví điểm',
            'ma giam gia'   => 'mã giảm giá',
            'khuyen mai'    => 'khuyến mãi',
            'danh gia huu ich' => 'đánh giá hữu ích',
            'ba na'         => 'bà nà',
            'bana'          => 'bà nà',
            'banahill'      => 'bà nà',
            'ba na hills'   => 'bà nà',
            'hoi an'        => 'hội an',
            'da nang'       => 'đà nẵng',
            'danang'        => 'đà nẵng',
            'ks'            => 'khách sạn',
            'nha hang'      => 'nhà hàng',
            'an uong'       => 'ăn uống',
            'di dau'        => 'đi đâu',
            'can biet'      => 'cần biết',
            'lịch trình'    => 'itinerary',
            'lich trinh'    => 'itinerary',
            'ngày'          => 'days',
            'ngay'          => 'days',
            'đêm'           => 'nights',
            'dem'           => 'nights',
        ];

        return strtr($text, $aliases);
    }
}
