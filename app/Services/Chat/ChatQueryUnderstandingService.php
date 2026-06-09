<?php

namespace App\Services\Chat;

use Carbon\CarbonImmutable;

final class ChatQueryUnderstandingService
{
    /** @return array<string,mixed> */
    public function understand(string $question, string $locale = 'vi'): array
    {
        $normalized = $this->applyAliases($this->normalize($question));

        return [
            'original_question' => $question,
            'normalized_question' => $normalized,
            'destination' => $this->extractDestination($normalized),
            'max_price' => $this->extractPrice($normalized, ['dưới', 'duoi', '<', 'nhỏ hơn', 'nho hon', 'tối đa', 'toi da']),
            'min_price' => $this->extractPrice($normalized, ['trên', 'tren', '>', 'từ', 'tu', 'ít nhất', 'it nhat']),
            'people' => $this->extractPeople($normalized),
            'date' => $this->extractDate($normalized, $locale),
            'duration_days' => $this->extractDurationDays($normalized),
            'cheapest_first' => $this->containsAny($normalized, ['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price']),
            'best_first' => $this->containsAny($normalized, ['tốt nhất', 'hay nhất', 'đẹp nhất', 'nổi bật', 'đánh giá cao', 'best', 'top']),
        ];
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
        $destinations = [
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

        foreach ($destinations as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($query, $alias)) {
                    return $canonical;
                }
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
