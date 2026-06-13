<?php

namespace App\Services\Chat;

use App\Models\Location;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ChatQueryUnderstandingService
{
    /** @return array<string,mixed> */
    public function understand(string $question, string $locale = 'vi'): array
    {
        $normalized = $this->applyAliases($this->normalize($question));

        $entities = [
            'original_question' => $question,
            'normalized_question' => $normalized,
            'destination' => $this->extractDestination($normalized),
            'region' => $this->extractRegion($normalized),
            'location_topic' => $this->extractLocationTopic($normalized),
            'max_price' => $this->extractPrice($normalized, ['dưới', 'duoi', '<', 'nhỏ hơn', 'nho hon', 'tối đa', 'toi da']),
            'min_price' => $this->extractPrice($normalized, ['trên', 'tren', '>', 'từ', 'tu', 'ít nhất', 'it nhat']),
            'people' => $this->extractPeople($normalized),
            'date' => $this->extractDate($normalized, $locale),
            'duration_days' => $this->extractDurationDays($normalized),
            'cheapest_first' => $this->containsAny($normalized, ['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price']),
            'best_first' => $this->containsAny($normalized, ['tốt nhất', 'hay nhất', 'đẹp', 'nổi bật', 'đánh giá cao', 'best', 'top']),
        ];

        $entities['confidence'] = $this->calculateConfidence($entities);

        return $entities;
    }

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

        return $totalWeight > 0 ? (float) ($score / $totalWeight) : 1.0;
    }

    private function getDynamicDestinations(): array
    {
        try {
            return Cache::remember('chatbot:dynamic_destinations', 3600, function (): array {
                $locations = Location::query()
                    ->where('status', 'active')
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->toArray();

                $tours = Tour::query()
                    ->where('status', 'active')
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->toArray();

                $dbDestinations = array_merge($locations, $tours);

                $dictionary = [
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

    private function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($value));
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
            'duoi' => 'dưới',
            'tren' => 'trên',
            'hom nay' => 'hôm nay',
            'ngay mai' => 'ngày mai',
            'thanh toan' => 'thanh toán',
            'hoan tien' => 'hoàn tiền',
            'dat tour' => 'đặt tour',
            'ba na' => 'bà nà',
            'bana' => 'bà nà',
            'hoi an' => 'hội an',
            'hue' => 'huế',
            'da nang' => 'đà nẵng',
            'ks' => 'khách sạn',
            'nha hang' => 'nhà hàng',
            'an uong' => 'ăn uống',
        ];

        return strtr($text, $aliases);
    }

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

    private function extractRegion(string $query): ?string
    {
        $regions = [
            'đà nẵng' => ['đà nẵng', 'danang'],
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

    private function extractLocationTopic(string $query): ?string
    {
        $topics = [
            'beach' => ['bãi biển', 'tắm biển', 'ven biển', 'beach'],
            'food' => ['ăn uống', 'ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản'],
            'hotel' => ['khách sạn', 'resort', 'homestay', 'lưu trú'],
            'spiritual' => ['chùa', 'tâm linh', 'nhà thờ'],
            'nature' => ['thiên nhiên', 'núi', 'hang động', 'thác'],
            'park' => ['công viên', 'vườn hoa'],
            'museum' => ['bảo tàng', 'di tích'],
            'market' => ['chợ', 'mua sắm'],
        ];

        foreach ($topics as $topic => $aliases) {
            if ($this->containsAny($query, $aliases)) {
                return $topic;
            }
        }

        return null;
    }

    /** @param array<int,string> $markers */
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

    private function extractPeople(string $query): ?int
    {
        if (preg_match('/(\d{1,2})\s*(người|nguoi|khách|khach|pax)/u', $query, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    private function extractDate(string $query, string $locale): ?string
    {
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();

        if ($this->containsAny($query, ['hôm nay', 'today'])) {
            return $today->toDateString();
        }

        if ($this->containsAny($query, ['ngày mai', 'mai', 'tomorrow'])) {
            return $today->addDay()->toDateString();
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

    private function extractDurationDays(string $query): ?int
    {
        if (preg_match('/(\d{1,2})\s*(ngày|ngay|day|days)/u', $query, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    /** @param array<int,string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
