<?php

namespace App\Services\Chat;

final class ChatIntentGuardService
{
    /**
     * Phân loại ý định (intent) của câu hỏi từ người dùng.
     * Đồng thời xác định xem câu hỏi có nằm trong phạm vi hỗ trợ (in-scope) hay không.
     *
     * @param  string  $message  Câu hỏi/thông điệp từ người dùng
     * @param  array<string,mixed>  $context  Ngữ cảnh trang hiện tại từ frontend
     * @return array{intent:string,is_in_scope:bool,reason:string|null,confidence:float,scores:array<string,float>,explicit_match:bool}
     */
    public function classify(string $message, array $context = []): array
    {
        $text = $this->normalize($message);
        $text = $this->applyAliases($text);

        if ($text === '') {
            return $this->result('empty', false, 'empty');
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
                return $this->result('out_of_scope', false, 'blocked_keyword');
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
                // English — chú ý: 'hi ' có dấu cách để tránh match 'khi', 'thi', 'chi'
                // Nhưng 'hi ' vẫn match 'khi ' → dùng prefix ^ hoặc điểu kiện bổ sung
                'hello', 'hey ', 'howdy',
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
                'nhân viên tư vấn', 'gặp nhân viên tư vấn', 'cho gặp nhân viên',
                'cần gặp nhân viên', 'muốn gặp nhân viên', 'tư vấn viên',
                'hotline', 'tổng đài', 'số điện thoại hỗ trợ', 'zalo chat', 'chat zalo',
                'chuyển giao', 'nhân viên trực', 'gap nguoi that', 'gap tu van vien',
                'nhan vien tu van', 'gap nhan vien tu van', 'cho gap nhan vien',
                'can gap nhan vien', 'muon gap nhan vien', 'gap nhan vien',
                'tu van vien', 'khieu nai', 'phan nan',
            ],

            // === Payment & Booking ===
            'payment' => [
                'thanh toán', 'qr', 'chuyển khoản', 'sepay',
                'đã trả tiền', 'pay', 'payment',
            ],
            'refund' => [
                'hoàn tiền', 'hủy tour', 'huỷ tour',
                'chính sách hủy', 'chính sách huỷ', 'đổi lịch', 'refund', 'cancel',
                'chính sách hoàn', 'hoàn trả', 'phí hủy', 'phí huỷ',
                'có được hoàn', 'muốn hủy', 'cần hủy',
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

            // === Itinerary & Schedule (MUST be before 'tour' to avoid 'du lịch' hijacking) ===
            'schedule' => [
                'lịch trình', 'kế hoạch', 'mấy ngày', '3 ngày', '2 ngày',
                '1 ngày', 'itinerary', 'schedule', 'plan', 'lên kế hoạch',
                'ngày mấy', 'bao lâu', 'chuyến đi', 'hành trình',
                '3 days', '2 days', '4 days', '5 days', '3 nights', '2 nights',
            ],

            // === Food & Dining ===
            'food' => [
                'ăn', 'uống', 'ẩm thực', 'đặc sản', 'nhà hàng', 'quán',
                'hải sản', 'mì quảng', 'bún chả cá', 'nên ăn gì', 'ăn gì',
                'food', 'restaurant', 'eat', 'drink', 'local food', 'cuisine',
                'quán ăn', 'seafood', 'cafe', 'cà phê', 'coffee', 'cà phê',
                'món ngon', 'bánh', 'quán ngon', 'quán cà phê', 'view biển',
                'ngàn ăn', 'nơi ăn', 'chỗ ăn', 'bữa ăn',
                // Thêm: dạng câu hỏi "nên ăn", "ăn ở đâu" phổ biến
                'nên ăn', 'ăn ở đâu', 'ăn món gì', 'món gì ngon',
            ],

            // === Hotels & Accommodation ===
            // LƯU Ý: Không dùng 'ở đâu' vì quá chung chung, gây nhầm với food/location
            'hotel' => [
                'khách sạn', 'resort', 'homestay', 'lưu trú', 'chỗ ở', 'hotel',
                'accommodation', 'stay', 'sleep', 'nên ở đâu', 'ở khách sạn',
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

            // === Tours ===
            'tour' => [
                'tour', 'tua', 'du lịch', 'du lich', 'rẻ nhất', 're nhat',
                'giá rẻ', 'gia re', 'excursion', 'trip', 'day trip', 'package',
                'tìm tour', 'tour nào', 'có tour', 'tour nào không',
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
                'contact', 'support', 'help',
            ],
        ];

        // ── Greeting: word-boundary check cho các từ ngắn dễ false positive ──
        // Chỉ check 'hi' và 'alo' nếu xuất hiện tại đầu câu hoặc là toàn bộ câu
        // để tránh match 'khi', 'chi', 'thi', 'nhà hàng', 'alo ngon'...
        $startsWithHi = (bool) preg_match('/^hi[!?.\s]?$/u', $text) || str_starts_with($text, 'hi ') && mb_strlen($text) <= 10;
        $startsWithAlo = (bool) preg_match('/^alo[!?.\s]?$/u', $text) || $text === 'alo';
        if ($startsWithHi || $startsWithAlo) {
            return $this->result('greeting', true, 'hi_alo_greeting', 1.0, ['greeting' => 4.0], true);
        }

        $strongKeywords = [
            'loyalty' => ['điểm thưởng', 'tích điểm', 'ví điểm', 'voucher', 'mã giảm giá', 'loyalty'],
            'handoff' => [
                'gặp nhân viên', 'gặp tư vấn viên', 'gặp người thật',
                'nhân viên tư vấn', 'gặp nhân viên tư vấn', 'cho gặp nhân viên',
                'cần gặp nhân viên', 'muốn gặp nhân viên', 'tư vấn viên',
                'khiếu nại', 'phàn nàn',
            ],
            'payment' => ['thanh toán', 'đã trả tiền', 'payment'],
            'refund' => ['hoàn tiền', 'chính sách hủy', 'chính sách huỷ', 'muốn hủy', 'cần hủy', 'refund'],
            'booking' => ['đặt tour', 'đặt chỗ', 'booking', 'giữ chỗ', 'đặt vé', 'reserve'],
            'blog' => ['bài viết', 'cẩm nang', 'đọc bài', 'xem bài', 'bài về', 'article', 'blog'],
            'schedule' => ['lịch trình', 'lên kế hoạch', 'itinerary', 'schedule'],
            // Thêm 'nên ăn', 'ăn ở đâu', 'ăn món gì' vào strong keywords để tăng điểm food
            'food' => ['quán ăn', 'nhà hàng', 'ẩm thực', 'ăn gì', 'món ngon', 'restaurant', 'nên ăn', 'ăn ở đâu', 'ăn món gì', 'món gì ngon'],
            'hotel' => ['khách sạn', 'resort', 'homestay', 'lưu trú', 'chỗ ở', 'hotel', 'ở khách sạn'],
            'location' => ['địa điểm', 'đi đâu', 'tham quan', 'check-in', 'điểm đến', 'thắng cảnh', 'attraction'],
            // Thêm 'có tour', 'tour nào' vào strong keywords để tăng điểm tour cho câu hỏi ngân sách
            'tour' => ['tour', 'tua', 'tìm tour', 'excursion', 'day trip', 'package', 'có tour', 'tour nào'],
            'account' => ['tài khoản', 'đăng nhập', 'đăng ký', 'mật khẩu', 'account'],
            'contact' => ['liên hệ', 'hotline', 'contact', 'support'],
        ];

        $scores = [];
        $explicitMatch = false;
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->containsKeyword($text, $keyword)) {
                    $weight = in_array($keyword, $strongKeywords[$intent] ?? [], true) ? 4.0 : 2.0;
                    if ($intent === 'tour' && in_array($keyword, ['tour', 'tua'], true)) {
                        $weight = 3.0;
                    }
                    $scores[$intent] = ($scores[$intent] ?? 0.0) + $weight;
                    $explicitMatch = true;
                }
            }
        }

        $contextIntent = $this->contextIntent($context);
        if ($contextIntent !== null) {
            $scores[$contextIntent] = ($scores[$contextIntent] ?? 0.0) + 1.25;
        }

        if ($scores === []) {
            return $this->result('unknown', true, 'no_intent_signal');
        }

        arsort($scores);
        $intentsByScore = array_keys($scores);
        $bestIntent = $intentsByScore[0];
        $bestScore = (float) $scores[$bestIntent];
        $secondScore = isset($intentsByScore[1]) ? (float) $scores[$intentsByScore[1]] : 0.0;
        $margin = $bestScore - $secondScore;

        // Chỉ có context nhưng không có tín hiệu câu chữ: chấp nhận với confidence vừa phải.
        if (! $explicitMatch && $contextIntent !== null) {
            return $this->result($contextIntent, true, 'page_context_only', 0.6, $scores, false);
        }

        // Hai nhóm ý định gần như ngang nhau thì hỏi lại thay vì đoán bừa.
        if ($secondScore > 0.0 && $margin < 0.5) {
            return $this->result('unknown', true, 'ambiguous_intent', 0.35, $scores, true);
        }

        $confidence = min(1.0, 0.55 + ($bestScore / 12.0) + min(0.25, $margin / 12.0));

        return $this->result($bestIntent, true, null, $confidence, $scores, $explicitMatch);
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function contextIntent(array $context): ?string
    {
        $pageType = (string) ($context['page_type'] ?? '');
        $entityType = (string) ($context['entity_type'] ?? '');
        $value = $entityType !== '' ? $entityType : $pageType;

        return match (true) {
            str_starts_with($value, 'tour') => 'tour',
            str_starts_with($value, 'location') => 'location',
            str_starts_with($value, 'blog') => 'blog',
            str_starts_with($value, 'food') => 'food',
            str_starts_with($value, 'hotel') => 'hotel',
            default => null,
        };
    }

    private function containsKeyword(string $text, string $keyword): bool
    {
        if (mb_strlen($keyword) <= 3) {
            return (bool) preg_match(
                '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u',
                $text
            );
        }

        return str_contains($text, $keyword);
    }

    /**
     * @param  array<string,float>  $scores
     * @return array{intent:string,is_in_scope:bool,reason:string|null,confidence:float,scores:array<string,float>,explicit_match:bool}
     */
    private function result(
        string $intent,
        bool $isInScope,
        ?string $reason,
        float $confidence = 0.0,
        array $scores = [],
        bool $explicitMatch = false
    ): array {
        return [
            'intent' => $intent,
            'is_in_scope' => $isInScope,
            'reason' => $reason,
            'confidence' => $confidence,
            'scores' => $scores,
            'explicit_match' => $explicitMatch,
        ];
    }

    /**
     * Chuẩn hóa văn bản: loại bỏ khoảng trắng dư thừa và chuyển về chữ thường.
     *
     * @param  string  $message  Chuỗi tin nhắn gốc
     * @return string Tin nhắn đã được chuẩn hóa
     */
    private function normalize(string $message): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($message));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($message));
    }

    /**
     * Áp dụng từ đồng nghĩa hoặc viết tắt (aliases) cho chuỗi văn bản đã chuẩn hóa.
     * Giúp cải thiện tỷ lệ khớp từ khóa.
     *
     * @param  string  $text  Tin nhắn đã được chuẩn hóa
     * @return string Tin nhắn sau khi áp dụng từ đồng nghĩa
     */
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
            'ba na' => 'bà nà',
            'bana' => 'bà nà',
            'banahill' => 'bà nà',
            'ba na hills' => 'bà nà',
            'hoi an' => 'hội an',
            'da nang' => 'đà nẵng',
            'danang' => 'đà nẵng',
            'ks' => 'khách sạn',
            'nha hang' => 'nhà hàng',
            'an uong' => 'ăn uống',
            'di dau' => 'đi đâu',
            'can biet' => 'cần biết',
            'lịch trình' => 'lịch trình',
            'lich trinh' => 'lịch trình',
            'ngày' => 'ngày',
            'ngay' => 'ngày',
            'đêm' => 'nights',
            'dem' => 'nights',
            'gap nguoi that' => 'gặp người thật',
            'gap tu van vien' => 'gặp tư vấn viên',
            'gap nhan vien' => 'gặp nhân viên',
            'nhan vien tu van' => 'nhân viên tư vấn',
            'tu van vien' => 'tư vấn viên',
            'cho gap nhan vien' => 'cho gặp nhân viên',
            'can gap nhan vien' => 'cần gặp nhân viên',
            'muon gap nhan vien' => 'muốn gặp nhân viên',
        ];

        return strtr($text, $aliases);
    }
}
