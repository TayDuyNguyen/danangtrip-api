<?php

namespace App\Services\Chat;

use App\Models\BlogPost;
use App\Models\ChatKnowledgeBase;
use App\Models\Location;
use App\Models\Tour;
use Illuminate\Support\Collection;

/**
 * Recommendation Builder Service
 *
 * Gom kết quả từ SQL Search và Vector Search,
 * deduplication, scoring, ranking, lấy Top N.
 */
final class ChatRecommendationBuilderService
{
    /**
     * Tổng hợp, tính điểm, xếp hạng và chọn lọc ra danh sách các đề xuất cuối cùng.
     * Hỗ trợ gộp kết quả tìm kiếm SQL và Vector Search, loại bỏ trùng lặp.
     *
     * @param array{tours:Collection,locations:Collection,blogs:Collection} $sqlResults Kết quả tìm kiếm SQL
     * @param Collection<int,ChatKnowledgeBase> $vectorResults Kết quả từ Vector search
     * @param array<string,mixed> $understanding Kết quả phân tích câu hỏi
     * @param int $limit Giới hạn số lượng đề xuất tối đa trả về (mặc định là 5)
     * @return array<int,array{type:string,data:array<string,mixed>}> Danh sách các đề xuất đã xếp hạng
     */
    public function build(
        array $sqlResults,
        Collection $vectorResults,
        array $understanding,
        int $limit = 5
    ): array {
        $pool = collect();
        $vecKeys = $this->buildVectorKeyMap($vectorResults);
        $addedKeys = [];

        // === Tours ===
        foreach (($sqlResults['tours'] ?? collect()) as $tour) {
            $key = "tour:{$tour->id}";
            $addedKeys[$key] = true;
            $score = $this->scoreTour($tour, $understanding);

            // Vector boost nếu cùng item có trong vector search
            if (isset($vecKeys[$key])) {
                $score += $vecKeys[$key] * 20; // similarity_score * 20
            }

            $pool->push([
                'type' => 'tour',
                'key' => $key,
                'data' => $this->tourPayload($tour),
                'score' => $score,
                'source' => isset($vecKeys[$key]) ? 'sql+vector' : 'sql',
            ]);
        }

        // === Locations ===
        foreach (($sqlResults['locations'] ?? collect()) as $location) {
            $key = "location:{$location->id}";
            $addedKeys[$key] = true;
            $score = $this->scoreLocation($location, $understanding);

            if (isset($vecKeys[$key])) {
                $score += $vecKeys[$key] * 20;
            }

            $pool->push([
                'type' => 'location',
                'key' => $key,
                'data' => $this->locationPayload($location),
                'score' => $score,
                'source' => isset($vecKeys[$key]) ? 'sql+vector' : 'sql',
            ]);
        }

        // === Blogs ===
        foreach (($sqlResults['blogs'] ?? collect()) as $blog) {
            $key = "blog:{$blog->id}";
            $addedKeys[$key] = true;
            $score = $this->scoreBlog($blog, $understanding);

            if (isset($vecKeys[$key])) {
                $score += $vecKeys[$key] * 15;
            }

            $pool->push([
                'type' => 'blog',
                'key' => $key,
                'data' => $this->blogPayload($blog),
                'score' => $score,
                'source' => isset($vecKeys[$key]) ? 'sql+vector' : 'sql',
            ]);
        }

        // === Hydrate and add vector-only results ===
        $missingTours = [];
        $missingLocations = [];
        $missingBlogs = [];

        foreach ($vectorResults as $item) {
            $key = $item->type.':'.$item->reference_id;
            if (empty($addedKeys[$key])) {
                if ($item->type === 'tour') {
                    $missingTours[] = $item->reference_id;
                } elseif ($item->type === 'location') {
                    $missingLocations[] = $item->reference_id;
                } elseif ($item->type === 'blog') {
                    $missingBlogs[] = $item->reference_id;
                }
            }
        }

        if (! empty($missingTours)) {
            $tours = Tour::query()->whereIn('id', $missingTours)->where('status', 'active')->get();
            foreach ($tours as $tour) {
                $key = "tour:{$tour->id}";
                $score = $this->scoreTour($tour, $understanding);
                if (isset($vecKeys[$key])) {
                    $score += $vecKeys[$key] * 20;
                }
                $pool->push([
                    'type' => 'tour',
                    'key' => $key,
                    'data' => $this->tourPayload($tour),
                    'score' => $score,
                    'source' => 'vector',
                ]);
            }
        }

        if (! empty($missingLocations)) {
            $locations = Location::query()->whereIn('id', $missingLocations)->where('status', 'active')->get();
            foreach ($locations as $location) {
                $key = "location:{$location->id}";
                $score = $this->scoreLocation($location, $understanding);
                if (isset($vecKeys[$key])) {
                    $score += $vecKeys[$key] * 20;
                }
                $pool->push([
                    'type' => 'location',
                    'key' => $key,
                    'data' => $this->locationPayload($location),
                    'score' => $score,
                    'source' => 'vector',
                ]);
            }
        }

        if (! empty($missingBlogs)) {
            $blogs = BlogPost::query()->whereIn('id', $missingBlogs)->where('status', 'published')->get();
            foreach ($blogs as $blog) {
                $key = "blog:{$blog->id}";
                $score = $this->scoreBlog($blog, $understanding);
                if (isset($vecKeys[$key])) {
                    $score += $vecKeys[$key] * 15;
                }
                $pool->push([
                    'type' => 'blog',
                    'key' => $key,
                    'data' => $this->blogPayload($blog),
                    'score' => $score,
                    'source' => 'vector',
                ]);
            }
        }

        // Deduplicate → Sort → Take
        return $pool
            ->unique('key')
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $item) => [
                'type' => $item['type'],
                'data' => $item['data'],
            ])
            ->values()
            ->all();
    }

    /**
     * Xây dựng bản đồ lookup nhanh từ Vector tri thức (khóa định dạng type:reference_id -> điểm tương đồng).
     *
     * @param Collection<int,ChatKnowledgeBase> $vectorResults Kết quả từ Vector search
     * @return array<string,float> Bản đồ tra cứu nhanh
     */
    private function buildVectorKeyMap(Collection $vectorResults): array
    {
        $map = [];
        foreach ($vectorResults as $item) {
            $key = $item->type.':'.$item->reference_id;
            $map[$key] = (float) ($item->similarity_score ?? 0.0);
        }

        return $map;
    }

    /**
     * Tính toán điểm xếp hạng cho tour du lịch dựa trên các tiêu chí (khoảng giá, điểm đến, độ khả dụng, lượt đặt...).
     *
     * @param Tour $tour Đối tượng tour cần tính điểm
     * @param array<string,mixed> $understanding Các thực thể phân tích ý định
     * @return float Điểm xếp hạng tour
     */
    private function scoreTour(Tour $tour, array $understanding): float
    {
        $score = 0.0;
        $destination = mb_strtolower((string) ($understanding['destination'] ?? ''));
        $priceMax = $understanding['max_price'] ?? null;
        $people = $understanding['people'] ?? null;
        $durationDays = $understanding['duration_days'] ?? null;
        $cheapest = (bool) ($understanding['cheapest_first'] ?? false);
        $keywords = array_map('mb_strtolower', (array) ($understanding['keywords'] ?? []));
        $topics = (array) ($understanding['topics'] ?? []);

        $haystack = mb_strtolower(implode(' ', array_filter([
            $tour->name,
            $tour->short_desc,
            $tour->description,
            $tour->meeting_point,
            $tour->duration,
        ])));

        $availability = $tour->booking_availability;
        $isOpen = ($availability instanceof \BackedEnum ? $availability->value : (string) $availability) === 'open';

        if ($cheapest) {
            // Cheapest first: ưu tiên giá thấp làm trọng số tuyệt đối
            $price = (float) $tour->price_adult;
            $score = ($isOpen ? 100000000 : 0) - $price;

            // Dùng các yếu tố khác làm tie-breaker cực nhỏ (tổng tối đa < 1.0)
            // để đảm bảo không đảo lộn thứ tự giá (ngay cả khi chênh lệch chỉ 1đ)
            if ($destination !== '' && str_contains($haystack, $destination)) {
                $score += 0.5;
            }
            $score += ((float) ($tour->rating_avg ?? 0)) * 0.01;
            $score += min((int) $tour->booking_count, 100) * 0.0001;

            return $score;
        }

        // Normal ranking
        if ($isOpen) {
            $score += 120;
        }
        if ($destination !== '' && str_contains($haystack, $destination)) {
            $score += 100;
        }

        // Boost tours when intent is tour or booking
        $intent = (string) ($understanding['intent'] ?? '');
        if ($intent === 'tour' || $intent === 'booking') {
            $score += 150.0;
        }
        if ($priceMax !== null && (float) $tour->price_adult <= (float) $priceMax) {
            $score += 80;
        }
        if ($people !== null && (int) $tour->min_people <= (int) $people && ((int) $tour->max_people === 0 || (int) $tour->max_people >= (int) $people)) {
            $score += 35;
        }
        if ($durationDays !== null && str_contains((string) $tour->duration, (string) $durationDays)) {
            $score += 25;
        }

        // Keyword boost
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $score += 15;
            }
        }

        // Topic boost
        if (in_array('budget', $topics, true) && (float) $tour->price_adult < 1000000) {
            $score += 20;
        }
        if (in_array('luxury', $topics, true) && (float) $tour->price_adult > 3000000) {
            $score += 20;
        }

        $score += ((float) ($tour->rating_avg ?? 0)) * 8;
        $score += min((int) $tour->booking_count, 100) * 0.5;
        $score += $tour->is_hot ? 8 : 0;
        $score += $tour->is_featured ? 6 : 0;

        return $score;
    }

    /**
     * Tính toán điểm xếp hạng cho địa điểm du lịch dựa trên ý định, khoảng giá, đánh giá và từ khóa.
     *
     * @param Location $location Đối tượng địa điểm cần tính điểm
     * @param array<string,mixed> $understanding Các thực thể phân tích ý định
     * @return float Điểm xếp hạng địa điểm
     */
    private function scoreLocation(Location $location, array $understanding): float
    {
        $score = 0.0;
        $destination = mb_strtolower((string) ($understanding['destination'] ?? ''));
        $topics = (array) ($understanding['topics'] ?? []);
        $keywords = array_map('mb_strtolower', (array) ($understanding['keywords'] ?? []));
        $locationTopic = (string) ($understanding['location_topic'] ?? '');

        $haystack = mb_strtolower(implode(' ', array_filter([
            $location->name,
            $location->short_description,
            $location->description,
            $location->address,
            $location->district,
        ])));

        if ($destination !== '' && str_contains($haystack, $destination)) {
            $score += 100;
        }

        // Boost locations when intent matches location types
        $intent = (string) ($understanding['intent'] ?? '');
        if ($intent === 'location' || $intent === 'food' || $intent === 'hotel') {
            $score += 150.0;
        }

        // Category-based boosting / deboosting based on intent & topics
        $catId = (int) $location->category_id;
        if ($intent === 'food' || $locationTopic === 'cafe' || $locationTopic === 'food') {
            // Food/dining categories: 1, 2, 3, 4, 15, 31, 32, 65
            if (in_array($catId, [1, 2, 3, 4, 15, 31, 32, 65], true)) {
                $score += 300.0;
                // Extra boost if topic is cafe and category is specifically café (3)
                if (($locationTopic === 'cafe' || in_array('cafe', $topics, true)) && $catId === 3) {
                    $score += 200.0;
                }
            } elseif (in_array($catId, [5, 6], true)) {
                // Deboost hotels when looking for food/café
                $score -= 300.0;
            }
        } elseif ($intent === 'hotel' || $locationTopic === 'hotel') {
            // Hotel categories: 5, 6
            if (in_array($catId, [5, 6], true)) {
                $score += 300.0;
            } elseif (in_array($catId, [1, 2, 3, 4], true)) {
                // Deboost restaurants/cafes when looking for accommodation
                $score -= 300.0;
            }
        }

        // Intent-specific topic boosts (Dining vs Accommodation)
        if ($intent === 'food') {
            $foodKeywords = ['ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản', 'food', 'restaurant', 'cafe', 'cà phê', 'coffee', 'ăn uống', 'hải sản', 'seafood', 'bún', 'mì', 'bánh'];
            foreach ($foodKeywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    $score += 100.0;
                    break;
                }
            }
        } elseif ($intent === 'hotel') {
            $hotelKeywords = ['khách sạn', 'hotel', 'resort', 'homestay', 'lưu trú', 'chỗ ở', 'villa', 'guesthouse'];
            foreach ($hotelKeywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    $score += 100.0;
                    break;
                }
            }
        }

        // Topic matching
        $topicKeywords = [
            'local_food' => ['ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản', 'food'],
            'restaurant' => ['nhà hàng', 'restaurant', 'quán ăn'],
            'cafe' => ['cafe', 'cà phê', 'coffee'],
            'seafood' => ['hải sản', 'seafood'],
            'hotel' => ['khách sạn', 'hotel', 'resort'],
            'homestay' => ['homestay', 'guesthouse'],
            'beach' => ['bãi biển', 'beach'],
            'mountain' => ['núi', 'mountain'],
            'temple' => ['chùa', 'đền', 'temple'],
            'museum' => ['bảo tàng', 'museum'],
            'market' => ['chợ', 'market'],
            'family_friendly' => ['gia đình', 'family'],
        ];

        foreach ($topics as $topic) {
            foreach ($topicKeywords[$topic] ?? [] as $kw) {
                if (str_contains($haystack, $kw)) {
                    $score += 40;
                    break;
                }
            }
        }

        // Keyword boost
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $score += 20;
            }
        }

        $score += $location->is_featured ? 30 : 0;
        $score += ((float) $location->avg_rating) * 8;
        $score += min((int) $location->view_count, 1000) * 0.02;
        $score += min((int) $location->review_count, 300) * 0.05;

        return $score;
    }

    /**
     * Tính toán điểm xếp hạng cho bài viết (blog) dựa trên từ khóa, tiêu đề và mức độ phổ biến.
     *
     * @param BlogPost $blog Đối tượng bài viết cần tính điểm
     * @param array<string,mixed> $understanding Các thực thể phân tích ý định
     * @return float Điểm xếp hạng bài viết
     */
    private function scoreBlog(BlogPost $blog, array $understanding): float
    {
        $score = 0.0;
        $intent = (string) ($understanding['intent'] ?? '');
        $keywords = array_map('mb_strtolower', (array) ($understanding['keywords'] ?? []));

        $haystack = mb_strtolower(implode(' ', array_filter([
            $blog->title,
            $blog->excerpt,
        ])));

        // Blog relevance by keywords
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                $score += 30;
            }
        }

        // Boost blogs when intent is schedule or blog
        if ($intent === 'schedule' || $intent === 'blog') {
            $score += 150.0;
        }

        // Extra boost for itinerary keywords when intent is schedule
        if ($intent === 'schedule') {
            $titleLower = mb_strtolower($blog->title);
            if (str_contains($titleLower, 'itinerary') || str_contains($titleLower, 'lịch trình') || str_contains($titleLower, 'days') || str_contains($titleLower, 'ngày')) {
                $score += 100.0;
            }
        }

        // Recency + popularity
        $score += min((int) $blog->view_count, 10000) * 0.005;
        if ($blog->published_at && now()->diffInDays($blog->published_at) <= 30) {
            $score += 15; // Recent blog boost
        }

        return $score;
    }

    /**
     * Chuẩn bị payload thông tin chi tiết cho Tour du lịch để trả về giao diện.
     *
     * @param Tour $tour Đối tượng Tour du lịch
     * @return array<string,mixed> Mảng payload được định dạng chi tiết
     */
    private function tourPayload(Tour $tour): array
    {
        return [
            'id' => $tour->id,
            'name' => $tour->name,
            'slug' => $tour->slug,
            'tour_category_id' => $tour->tour_category_id,
            'description' => $tour->description,
            'short_desc' => $tour->short_desc,
            'itinerary' => $tour->itinerary,
            'inclusions' => is_array($tour->inclusions) ? implode("\n", $tour->inclusions) : $tour->inclusions,
            'exclusions' => is_array($tour->exclusions) ? implode("\n", $tour->exclusions) : $tour->exclusions,
            'price_adult' => (string) $tour->price_adult,
            'price_child' => (string) $tour->price_child,
            'price_infant' => (string) $tour->price_infant,
            'discount_percent' => (int) $tour->discount_percent,
            'duration' => $tour->duration,
            'start_time' => $tour->start_time,
            'meeting_point' => $tour->meeting_point,
            'max_people' => (int) $tour->max_people,
            'min_people' => (int) $tour->min_people,
            'available_from' => $tour->available_from?->toDateString(),
            'available_to' => $tour->available_to?->toDateString(),
            'thumbnail' => $tour->thumbnail,
            'images' => $tour->images,
            'video_url' => $tour->video_url,
            'status' => $tour->status,
            'is_featured' => (bool) $tour->is_featured,
            'is_hot' => (bool) $tour->is_hot,
            'view_count' => (int) $tour->view_count,
            'booking_count' => (int) $tour->booking_count,
            'avg_rating' => (string) ($tour->rating_avg ?? '0.00'),
            'review_count' => (int) ($tour->rating_count ?? 0),
        ];
    }

    /**
     * Chuẩn bị payload thông tin địa điểm để trả về giao diện.
     *
     * @param Location $location Đối tượng địa điểm du lịch
     * @return array<string,mixed> Mảng payload địa điểm được định dạng chi tiết
     */
    private function locationPayload(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'slug' => $location->slug,
            'category_id' => $location->category_id,
            'subcategory_id' => $location->subcategory_id,
            'description' => $location->description,
            'short_description' => $location->short_description,
            'address' => $location->address,
            'district' => $location->district,
            'ward' => $location->ward,
            'latitude' => (string) $location->latitude,
            'longitude' => (string) $location->longitude,
            'phone' => $location->phone,
            'opening_hours' => $location->opening_hours,
            'price_min' => $location->price_min ? (float) $location->price_min : null,
            'price_max' => $location->price_max ? (float) $location->price_max : null,
            'price_level' => $location->price_level,
            'thumbnail' => $location->thumbnail,
            'images' => $location->images,
            'status' => $location->status,
            'is_featured' => (bool) $location->is_featured,
            'view_count' => (int) $location->view_count,
            'avg_rating' => (string) $location->avg_rating,
            'review_count' => (int) $location->review_count,
        ];
    }

    /**
     * Chuẩn bị payload thông tin bài viết (blog) để trả về giao diện.
     *
     * @param BlogPost $blog Đối tượng bài viết
     * @return array<string,mixed> Mảng payload bài viết được định dạng chi tiết
     */
    private function blogPayload(BlogPost $blog): array
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'featured_image' => $blog->featured_image,
            'view_count' => (int) $blog->view_count,
            'status' => $blog->status,
            'published_at' => optional($blog->published_at)->toISOString(),
        ];
    }
}
