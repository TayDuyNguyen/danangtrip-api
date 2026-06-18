<?php

namespace App\Services\Chat;

use App\Models\Location;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class ChatQueryUnderstandingService
{
    /**
     * Phân tích câu hỏi của người dùng để trích xuất các thực thể rule-based.
     *
     * @param  string  $question  Câu hỏi gốc của người dùng
     * @param  string  $locale  Ngôn ngữ hiện tại (mặc định là 'vi')
     * @return array<string,mixed> Mảng chứa các thực thể trích xuất được và độ tin cậy
     */
    public function understand(string $question, string $locale = 'vi'): array
    {
        $normalized = $this->applyAliases($this->normalize($question));

        $entities = [
            'original_question' => $question,
            'normalized_question' => $normalized,
            'destination' => $this->extractDestination($normalized),
            'region' => $this->extractRegion($normalized),
            'location_topic' => $this->extractLocationTopic($normalized),
            'max_price' => $this->extractMaxPrice($normalized),
            'min_price' => $this->extractPrice($normalized, ['trên', 'tren', '>', 'từ', 'tu', 'ít nhất', 'it nhat', 'tối thiểu']),
            'people' => $this->extractPeople($normalized),
            'date' => $this->extractDate($normalized, $locale),
            'duration_days' => $this->extractDurationDays($normalized),
            'cheapest_first' => $this->containsAny($normalized, ['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price', 'affordable', 'budget']),
            'best_first' => $this->containsAny($normalized, ['tốt nhất', 'hay nhất', 'đẹp', 'nổi bật', 'đánh giá cao', 'best', 'top', 'highly rated', 'popular', 'nổi tiếng']),
            // NEW: content_type_hints — rule-based gợi ý loại nội dung cần tìm
            'content_type_hints' => $this->extractContentTypeHints($normalized),
            // NEW: topic_hints — chủ đề cụ thể hơn
            'topic_hints' => $this->extractTopicHints($normalized),
        ];

        $entities['confidence'] = $this->calculateConfidence($entities);

        return $entities;
    }

    /**
     * Gợi ý loại nội dung cần tìm kiếm dựa trên từ khóa rule-based.
     * AI NLU sẽ ghi đè hoặc bổ sung phần này nếu được kích hoạt.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return array<int,string> Mảng các loại nội dung gợi ý
     */
    private function extractContentTypeHints(string $query): array
    {
        $hints = [];

        // Tour hints
        if ($this->containsAny($query, ['tour', 'tua', 'du lịch', 'excursion', 'day trip', 'tham quan', 'đặt vé'])) {
            $hints[] = 'tour';
        }

        // Location hints
        if ($this->containsAny($query, [
            'địa điểm', 'đi đâu', 'ở đâu', 'nơi', 'quán', 'nhà hàng', 'cafe', 'cà phê',
            'khách sạn', 'resort', 'bãi biển', 'beach', 'chùa', 'bảo tàng',
            'check-in', 'checkin', 'gần biển', 'trung tâm', 'ăn gì', 'uống gì',
            'ăn', 'uống', 'món', 'ẩm thực',
        ])) {
            $hints[] = 'location';
        }

        // Blog hints
        if ($this->containsAny($query, [
            'bài viết', 'cẩm nang', 'kinh nghiệm', 'blog', 'hướng dẫn',
            'mẹo', 'tips', 'review', 'guide', 'lịch trình', 'kế hoạch',
            'đọc bài', 'xem thêm', 'itinerary', 'schedule', 'plan',
        ])) {
            $hints[] = 'blog';
        }

        // Policy hints
        if ($this->containsAny($query, [
            'chính sách', 'điều khoản', 'hoàn tiền', 'hủy', 'thanh toán', 'điểm thưởng',
        ])) {
            $hints[] = 'policy';
        }

        // Default: nếu không rõ, gợi ý cả tour lẫn location
        if (empty($hints)) {
            $hints = ['tour', 'location', 'blog'];
        }

        return array_unique($hints);
    }

    /**
     * Trích xuất chủ đề cụ thể (như ẩm thực, khách sạn, bãi biển, núi...) dựa trên các từ khóa.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return array<int,string> Mảng các chủ đề gợi ý
     */
    private function extractTopicHints(string $query): array
    {
        $topics = [];

        $topicMap = [
            'local_food' => ['ăn gì', 'đặc sản', 'món ngon', 'ẩm thực', 'mì quảng', 'bún chả cá', 'hải sản', 'local food'],
            'restaurant' => ['nhà hàng', 'quán ăn', 'restaurant', 'quán'],
            'cafe' => ['cafe', 'cà phê', 'coffee', 'quán cà phê'],
            'seafood' => ['hải sản', 'seafood', 'tôm', 'cua', 'cá'],
            'hotel' => ['khách sạn', 'hotel', 'accommodation'],
            'resort' => ['resort', 'villa'],
            'homestay' => ['homestay', 'guesthouse'],
            'beach' => ['bãi biển', 'beach', 'tắm biển', 'ven biển', 'view biển'],
            'mountain' => ['núi', 'mountain', 'đèo'],
            'temple' => ['chùa', 'đền', 'temple', 'tâm linh'],
            'museum' => ['bảo tàng', 'museum', 'di tích'],
            'market' => ['chợ', 'market', 'mua sắm', 'shopping'],
            'family_friendly' => ['gia đình', 'trẻ em', 'trẻ nhỏ', 'family', 'kids', 'children'],
            'romantic' => ['lãng mạn', 'cặp đôi', 'romantic', 'couple', 'honeymoon'],
            'budget' => ['tiết kiệm', 'giá rẻ', 'budget', 'rẻ', 'ít tiền'],
            'luxury' => ['sang trọng', 'luxury', '5 sao', 'vip', 'cao cấp'],
        ];

        foreach ($topicMap as $topic => $keywords) {
            if ($this->containsAny($query, $keywords)) {
                $topics[] = $topic;
            }
        }

        return $topics;
    }

    /**
     * Tính toán điểm tin cậy (confidence score) cho kết quả phân tích rule-based.
     *
     * @param  array<string,mixed>  $entities  Các thực thể đã trích xuất được
     * @return float Điểm số tin cậy trong khoảng [0.0, 1.0]
     */
    private function calculateConfidence(array $entities): float
    {
        $weights = (array) config('chatbot.nlu.weights', [
            'destination' => 35,
            'price' => 25,
            'people' => 20,
            'date' => 20,
        ]);

        $score = 0;
        $totalWeight = (float) array_sum($weights);

        if (! empty($entities['destination']) || ! empty($entities['region'])) {
            $score += $weights['destination'] ?? 35;
        }

        if (! empty($entities['max_price']) || ! empty($entities['min_price'])) {
            $score += $weights['price'] ?? 25;
        }

        if (! empty($entities['people'])) {
            $score += $weights['people'] ?? 20;
        }

        if (! empty($entities['date'])) {
            $score += $weights['date'] ?? 20;
        }

        return $totalWeight > 0 ? (float) ($score / $totalWeight) : 0.0;
    }

    /**
     * Lấy danh sách điểm đến động từ cơ sở dữ liệu để làm từ điển tra cứu.
     * Kết quả được lưu vào bộ nhớ đệm cache để tối ưu hiệu năng.
     *
     * @return array<string,array<int,string>> Từ điển ánh xạ từ khóa điểm đến sang các từ đồng nghĩa
     */
    private function getDynamicDestinations(): array
    {
        try {
            return Cache::remember('chatbot:dynamic_destinations', 3600, function (): array {
                $locations = [];
                if (Schema::hasTable('locations')) {
                    $locations = Location::query()
                        ->where('status', 'active')
                        ->pluck('name')
                        ->filter()
                        ->unique()
                        ->toArray();
                }

                $tours = [];
                if (Schema::hasTable('tours')) {
                    $tours = Tour::query()
                        ->where('status', 'active')
                        ->pluck('name')
                        ->filter()
                        ->unique()
                        ->toArray();
                }

                $dbDestinations = array_merge($locations, $tours);

                $dictionary = [
                    'bà nà hills' => ['bà nà hills', 'bà nà', 'ba na hills', 'bana hills', 'ba na'],
                    'hội an' => ['hội an', 'phố cổ hội an', 'hoi an'],
                    'huế' => ['huế', 'cố đô huế', 'hue'],
                    'cù lao chàm' => ['cù lao chàm', 'cu lao cham', 'cham island'],
                    'mỹ sơn' => ['mỹ sơn', 'my son', 'thánh địa mỹ sơn'],
                    'ngũ hành sơn' => ['ngũ hành sơn', 'ngu hanh son', 'marble mountains'],
                    'sơn trà' => ['sơn trà', 'son tra', 'bán đảo sơn trà'],
                    'mỹ khê' => ['mỹ khê', 'my khe', 'bãi biển mỹ khê'],
                    'cầu rồng' => ['cầu rồng', 'cau rong', 'dragon bridge'],
                ];

                foreach ($dbDestinations as $name) {
                    $normalized = mb_strtolower(trim((string) $name));
                    if ($normalized === '') {
                        continue;
                    }

                    $alreadyExists = false;
                    foreach ($dictionary as $canonical => $aliases) {
                        if ($canonical === $normalized || in_array($normalized, $aliases, true)) {
                            $alreadyExists = true;
                            break;
                        }
                    }

                    if (! $alreadyExists) {
                        $ascii = $this->removeVietnameseTones($normalized);
                        $aliases = [$normalized];
                        if ($ascii !== $normalized) {
                            $aliases[] = $ascii;
                        }
                        $dictionary[$normalized] = $aliases;
                    }
                }

                return $dictionary;
            });
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_DYNAMIC_DICTIONARY_FAILED', ['message' => $e->getMessage()]);

            return [
                'bà nà hills' => ['bà nà hills', 'bà nà', 'ba na hills'],
                'hội an' => ['hội an', 'phố cổ hội an'],
                'huế' => ['huế', 'cố đô huế'],
                'cù lao chàm' => ['cù lao chàm', 'cu lao cham'],
                'mỹ sơn' => ['mỹ sơn', 'my son'],
                'ngũ hành sơn' => ['ngũ hành sơn', 'ngu hanh son'],
                'sơn trà' => ['sơn trà', 'son tra'],
                'mỹ khê' => ['mỹ khê', 'my khe'],
                'cầu rồng' => ['cầu rồng', 'cau rong'],
            ];
        }
    }

    /**
     * Loại bỏ các dấu tiếng Việt khỏi chuỗi văn bản.
     *
     * @param  string  $str  Chuỗi có dấu
     * @return string Chuỗi không dấu
     */
    private function removeVietnameseTones(string $str): string
    {
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ần|ẩ|ẫ|ậ|å',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ|Å',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|E|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];

        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/({$uni})/i", $nonUnicode, $str) ?? $str;
        }

        return $str;
    }

    /**
     * Chuẩn hóa văn bản: loại bỏ khoảng trắng dư thừa và chuyển về chữ thường.
     *
     * @param  string  $value  Chuỗi tin nhắn gốc
     * @return string Tin nhắn đã được chuẩn hóa
     */
    private function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($value));
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
            'duoi' => 'dưới',
            'tren' => 'trên',
            'hom nay' => 'hôm nay',
            'ngay mai' => 'ngày mai',
            'thanh toan' => 'thanh toán',
            'hoan tien' => 'hoàn tiền',
            'dat tour' => 'đặt tour',
            'ba na' => 'bà nà',
            'bana' => 'bà nà',
            'banahill' => 'bà nà',
            'hoi an' => 'hội an',
            'hue' => 'huế',
            'da nang' => 'đà nẵng',
            'danang' => 'đà nẵng',
            'ks' => 'khách sạn',
            'nha hang' => 'nhà hàng',
            'an uong' => 'ăn uống',
            'cu lao cham' => 'cù lao chàm',
            'my son' => 'mỹ sơn',
            'son tra' => 'sơn trà',
            'my khe' => 'mỹ khê',
            'lịch trình' => 'itinerary',
            'lich trinh' => 'itinerary',
            'ngày' => 'days',
            'ngay' => 'days',
            'đêm' => 'nights',
            'dem' => 'nights',
        ];

        return strtr($text, $aliases);
    }

    /**
     * Trích xuất tên địa danh/điểm đến từ câu hỏi người dùng dựa trên từ điển.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return string|null Tên địa danh canonical, hoặc null nếu không tìm thấy
     */
    private function extractDestination(string $query): ?string
    {
        $destinations = $this->getDynamicDestinations();

        foreach ($destinations as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($query, $alias)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Trích xuất vùng miền/thành phố du lịch từ câu hỏi.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return string|null Tên vùng miền canonical, hoặc null nếu không tìm thấy
     */
    private function extractRegion(string $query): ?string
    {
        $regions = [
            'đà nẵng' => ['đà nẵng', 'danang', 'da nang'],
            'hội an' => ['hội an'],
            'huế' => ['huế'],
            'quảng nam' => ['quảng nam'],
        ];

        foreach ($regions as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($query, $alias)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Phân tích và trích xuất chủ đề địa điểm (ẩm thực, lưu trú, bãi biển, bảo tàng...) từ câu hỏi.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return string|null Tên chủ đề canonical, hoặc null nếu không tìm thấy
     */
    private function extractLocationTopic(string $query): ?string
    {
        $topics = [
            'beach' => ['bãi biển', 'tắm biển', 'ven biển', 'beach', 'gần biển'],
            'food' => ['ăn uống', 'ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản', 'ăn gì'],
            'hotel' => ['khách sạn', 'resort', 'homestay', 'lưu trú', 'chỗ ở'],
            'spiritual' => ['chùa', 'tâm linh', 'nhà thờ', 'đền'],
            'nature' => ['thiên nhiên', 'núi', 'hang động', 'thác', 'rừng'],
            'park' => ['công viên', 'vườn hoa'],
            'museum' => ['bảo tàng', 'di tích', 'museum'],
            'market' => ['chợ', 'mua sắm', 'market'],
            'cafe' => ['cafe', 'cà phê', 'coffee'],
        ];

        foreach ($topics as $topic => $aliases) {
            if ($this->containsAny($query, $aliases)) {
                return $topic;
            }
        }

        return null;
    }

    /**
     * Hỗ trợ trích xuất số tiền và quy đổi đơn vị (VND, triệu, nghìn, k) dựa trên các marker từ khóa.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @param  array<int,string>  $markers  Các từ khóa nhận diện đứng trước số tiền
     * @return int|null Giá trị số tiền dạng integer VNĐ, hoặc null nếu không tìm thấy
     */
    private function extractPrice(string $query, array $markers): ?int
    {
        $markerPattern = implode('|', array_map(fn (string $marker) => preg_quote($marker, '/'), $markers));
        if (! preg_match('/(?:'.$markerPattern.')\s*([\d\.,]+)\s*(triệu|trieu|nghìn|nghin|k)?/u', $query, $matches)) {
            return null;
        }

        $rawNumber = str_replace(',', '.', $matches[1]);
        $number = (float) preg_replace('/(?<=\d)\.(?=\d{3}(\D|$))/u', '', $rawNumber);
        $unit = $matches[2] ?? '';

        return match ($unit) {
            'triệu', 'trieu' => (int) round($number * 1000000),
            'nghìn', 'nghin', 'k' => (int) round($number * 1000),
            default => (int) round($number),
        };
    }

    /**
     * Trích xuất số lượng khách/người từ tin nhắn.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return int|null Số lượng người, hoặc null nếu không tìm thấy
     */
    private function extractPeople(string $query): ?int
    {
        if (preg_match('/(\d{1,2})\s*(người|nguoi|khách|khach|pax|adults?)/u', $query, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    /**
     * Trích xuất ngân sách tối đa — hỗ trợ nhiều pattern tiếng Việt thực tế.
     * Các pattern được nhận dạng:
     *  - "dưới/không quá/tối đa X triệu"     => giới hạn trên rõ ràng
     *  - "ngân sách khoảng/tầm X triệu"    => ước tính => coi là max
     *  - "khoảng X triệu" (standalone)        => ước tính
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return int|null Giá trị tối đa VNĐ, hoặc null
     */
    private function extractMaxPrice(string $query): ?int
    {
        // Ưu tiên 1: marker tường minh
        $explicit = $this->extractPrice($query, [
            'dưới', 'duoi', '<', 'nhỏ hơn', 'nho hon', 'tối đa', 'toi da', 'không quá', 'khong qua',
        ]);
        if ($explicit !== null) {
            return $explicit;
        }

        // Ưu tiên 2: ngân sách + khoảng/tầm + số
        if (preg_match(
            '/(?:ngân sách|ngan sach|budget|chi phí|chi phi)\s*(?:khoảng|khoang|tầm|tam|~)?\s*([\d\.,]+)\s*(triệu|trieu|nghìn|nghin|k)?/u',
            $query,
            $matches
        )) {
            $rawNumber = str_replace(',', '.', $matches[1]);
            $number = (float) preg_replace('/(?<=\d)\.(?=\d{3}(\D|$))/u', '', $rawNumber);
            $unit = $matches[2] ?? '';

            return match ($unit) {
                'triệu', 'trieu' => (int) round($number * 1000000),
                'nghìn', 'nghin', 'k' => (int) round($number * 1000),
                default => (int) round($number),
            };
        }

        // Ưu tiên 3: "khoảng/tầm X triệu" standalone
        if (preg_match(
            '/(?:khoảng|khoang|tầm|tam)\s+([\d\.,]+)\s*(triệu|trieu|nghìn|nghin|k)/u',
            $query,
            $matches
        )) {
            $rawNumber = str_replace(',', '.', $matches[1]);
            $number = (float) preg_replace('/(?<=\d)\.(?=\d{3}(\D|$))/u', '', $rawNumber);
            $unit = $matches[2] ?? '';

            return match ($unit) {
                'triệu', 'trieu' => (int) round($number * 1000000),
                'nghìn', 'nghin', 'k' => (int) round($number * 1000),
                default => (int) round($number),
            };
        }

        return null;
    }

    /**
     * Trích xuất thời điểm khởi hành du lịch từ câu hỏi người dùng (hôm nay, ngày mai, cuối tuần, tuần sau, d/m/y).
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @param  string  $locale  Ngôn ngữ hiện tại
     * @return string|null Chuỗi định dạng Y-m-d, hoặc null nếu không tìm thấy
     */
    private function extractDate(string $query, string $locale): ?string
    {
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();

        if ($this->containsAny($query, ['hôm nay', 'today'])) {
            return $today->toDateString();
        }

        if ($this->containsAny($query, ['ngày mai', 'tomorrow'])) {
            return $today->addDay()->toDateString();
        }

        // Tuần sau → thứ 2 tuần sau
        if ($this->containsAny($query, ['tuần sau', 'tuan sau', 'next week', 'tuần tới', 'tuan toi'])) {
            $daysUntilNextMonday = (8 - $today->dayOfWeek) % 7;
            $daysUntilNextMonday = $daysUntilNextMonday === 0 ? 7 : $daysUntilNextMonday;

            return $today->addDays($daysUntilNextMonday)->toDateString();
        }

        // Cuối tuần này (thứ 7 hoặc chủ nhật gần nhất)
        if ($this->containsAny($query, ['cuối tuần này', 'weekend này', 'this weekend'])) {
            $daysUntilSaturday = (6 - $today->dayOfWeek + 7) % 7;
            $daysUntilSaturday = $daysUntilSaturday === 0 ? 7 : $daysUntilSaturday;

            return $today->addDays($daysUntilSaturday)->toDateString();
        }

        // Cuối tuần sau
        if ($this->containsAny($query, ['cuối tuần sau', 'weekend sau', 'next weekend'])) {
            $daysUntilSaturday = (6 - $today->dayOfWeek + 7) % 7;
            $daysUntilSaturday = $daysUntilSaturday === 0 ? 14 : $daysUntilSaturday + 7;

            return $today->addDays($daysUntilSaturday)->toDateString();
        }

        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?/u', $query, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = isset($matches[3]) ? (int) $matches[3] : (int) $today->year;

            if (checkdate($month, $day, $year)) {
                return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'Asia/Ho_Chi_Minh')->toDateString();
            }
        }

        return null;
    }

    /**
     * Trích xuất thời lượng chuyến đi (số ngày) từ câu hỏi.
     *
     * @param  string  $query  Truy vấn đã được chuẩn hóa
     * @return int|null Số ngày hành trình du lịch, hoặc null
     */
    private function extractDurationDays(string $query): ?int
    {
        if (preg_match('/(\d{1,2})\s*(ngày|ngay|day|days)/u', $query, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    /**
     * Kiểm tra xem chuỗi văn bản có chứa bất kỳ từ khóa nào trong danh sách hay không.
     *
     * @param  string  $haystack  Chuỗi văn bản nguồn
     * @param  array<int,string>  $needles  Danh sách các từ khóa cần tìm
     * @return bool Trả về true nếu có ít nhất một từ khóa khớp
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($this->containsWord($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra xem một từ khóa cụ thể có xuất hiện trong chuỗi văn bản hay không.
     * Hỗ trợ tìm kiếm khớp biên từ (word-boundary) đối với một số từ tiếng Việt ngắn nhạy cảm để tránh nhận diện nhầm.
     *
     * @param  string  $haystack  Chuỗi văn bản nguồn
     * @param  string  $needle  Từ khóa cần kiểm tra
     * @return bool Trả về true nếu từ khóa tồn tại
     */
    private function containsWord(string $haystack, string $needle): bool
    {
        $sensitive = ['cá', 'rẻ', 'đẹp', 'tua', 'né', 'mai', 'tour', 'ks', 'ngày', 'đêm'];
        if (in_array($needle, $sensitive, true)) {
            $pattern = '/(?<=\s|^)'.preg_quote($needle, '/').'(?=\s|$|[.,!?\-])/u';

            return (bool) preg_match($pattern, $haystack);
        }

        return str_contains($haystack, $needle);
    }

    /**
     * Dọn dẹp mềm các thực thể không liên quan dựa trên intent nghiệp vụ mới.
     * Giúp lọc bỏ bớt nhiễu trong câu lệnh tìm kiếm SQL/Vector.
     *
     * @param  array<string,mixed>  $understanding  Mảng thực thể hiện có
     * @param  string  $intent  Ý định nghiệp vụ mới
     * @return array<string,mixed> Mảng thực thể sau khi lọc sạch
     */
    public function normalizeEntitiesForIntent(array $understanding, string $intent): array
    {
        $allowedFields = [];

        switch ($intent) {
            case 'tour':
            case 'booking':
                // Giữ tất cả
                return $understanding;

            case 'schedule':
                $allowedFields = [
                    'destination',
                    'region',
                    'duration_days',
                    'date',
                    'people',
                    'max_price',
                    'min_price',
                ];
                break;

            case 'location':
            case 'food':
            case 'hotel':
                $allowedFields = [
                    'destination',
                    'region',
                    'location_topic',
                    'people',
                    'date',
                    'max_price',
                    'min_price',
                ];
                break;

            case 'blog':
                $allowedFields = [
                    'destination',
                    'region',
                    'topics',
                    'topic_hints',
                ];
                break;

            default:
                // Các intent khác chỉ giữ lại thông tin địa điểm tối thiểu
                $allowedFields = [
                    'destination',
                    'region',
                ];
                break;
        }

        $allPossibleEntityFields = [
            'destination',
            'region',
            'location_topic',
            'max_price',
            'min_price',
            'people',
            'date',
            'duration_days',
            'topics',
            'topic_hints',
            'content_types',
            'content_type_hints',
        ];

        foreach ($allPossibleEntityFields as $field) {
            if (! in_array($field, $allowedFields, true)) {
                if (in_array($field, ['topics', 'topic_hints', 'content_types', 'content_type_hints'], true)) {
                    $understanding[$field] = [];
                } else {
                    $understanding[$field] = null;
                }
            }
        }

        return $understanding;
    }
}
