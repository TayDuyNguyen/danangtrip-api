<?php

namespace App\Services\Chat;

use App\Models\BlogPost;
use App\Models\ChatKnowledgeBase;
use App\Models\Location;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ChatKnowledgeSearchService
{
    public function __construct(
        private readonly ChatVectorSearchService $vectorSearch
    ) {}

    /** @return array{context:array<int,array<string,mixed>>,recommendations:array<int,array<string,mixed>>,center:?array<int,float>,zoom:int} */
    public function search(string $question, string $intent, int $limit, array $understanding = []): array
    {
        $limit = max(1, min($limit, 8));
        $query = (string) ($understanding['normalized_question'] ?? $this->applyAliases($this->normalize($question)));
        $searchQuery = $this->buildSearchQuery($query, $intent, $understanding);
        $priceMax = $understanding['max_price'] ?? $this->extractMaxPrice($query);
        $priceMin = $understanding['min_price'] ?? null;
        $cheapestFirst = (bool) ($understanding['cheapest_first'] ?? $this->isCheapestQuery($query));

        $locations = in_array($intent, ['location', 'food', 'hotel', 'schedule', 'greeting'], true)
            ? $this->searchLocations($searchQuery, $intent, $limit, $understanding)
            : collect();

        $tours = in_array($intent, ['tour', 'booking', 'payment', 'refund', 'schedule', 'greeting'], true)
            ? $this->searchTours($searchQuery, $intent, $limit, $priceMax, $priceMin, $cheapestFirst, $understanding)
            : collect();

        if (
            $locations->isEmpty()
            && $tours->isEmpty()
            && in_array($intent, ['food', 'hotel', 'location'], true)
            && empty($understanding['location_topic'])
            && empty($understanding['region'])
        ) {
            $locations = $this->fallbackLocations($intent, $limit);
        }

        if ($tours->isEmpty() && in_array($intent, ['tour', 'booking', 'schedule', 'greeting'], true)) {
            $tours = $this->searchTours('', $intent, $limit, $priceMax, $priceMin, $cheapestFirst, $understanding);
        }

        if ($tours->isEmpty() && in_array($intent, ['tour', 'booking', 'schedule', 'greeting'], true) && $this->hasNoHardTourConstraints($understanding, $priceMax, $priceMin)) {
            $tours = $this->fallbackTours($limit);
        }

        $blogs = $this->searchBlogs(
            $searchQuery ?: $query,
            $intent,
            $intent === 'blog' ? $limit : max(1, $limit - 2),
            $understanding
        );
        if ($blogs->isEmpty() && $intent === 'blog') {
            $blogs = $this->fallbackBlogs($limit);
        }
        $vectorKnowledge = $this->vectorSearch->search(
            $searchQuery ?: (string) ($understanding['normalized_question'] ?? $question),
            $intent,
            (int) config('chatbot.vector_context_limit', 5)
        );
        $policies = $this->policyContext($intent);

        $context = collect()
            ->merge($this->tourContext($tours))
            ->merge($this->locationContext($locations))
            ->merge($this->blogContext($blogs))
            ->merge($this->vectorContext($vectorKnowledge))
            ->merge($policies)
            ->unique(fn (array $item): string => (string) ($item['type'] ?? '').':'.(string) ($item['id'] ?? $item['slug'] ?? md5((string) ($item['content'] ?? ''))))
            ->take($limit + 4)
            ->values()
            ->all();

        $recommendations = collect()
            ->merge($tours->take($limit)->map(fn (Tour $tour) => ['type' => 'tour', 'data' => $this->tourPayload($tour)]))
            ->merge($locations->take($limit)->map(fn (Location $location) => ['type' => 'location', 'data' => $this->locationPayload($location)]))
            ->merge($blogs->take($limit)->map(fn (BlogPost $blog) => ['type' => 'blog', 'data' => $this->blogPayload($blog)]))
            ->take($limit)
            ->values()
            ->all();

        $firstLocation = $locations->first();
        $center = $firstLocation instanceof Location
            ? [(float) $firstLocation->latitude, (float) $firstLocation->longitude]
            : [16.0544, 108.2022];

        return [
            'context' => $context,
            'recommendations' => $recommendations,
            'center' => $center,
            'zoom' => $firstLocation instanceof Location ? 13 : 12,
        ];
    }

    private function searchLocations(string $query, string $intent, int $limit, array $understanding = []): Collection
    {
        $topic = (string) ($understanding['location_topic'] ?? '');
        $region = (string) ($understanding['region'] ?? '');

        $locations = Location::query()
            ->where('status', 'active')
            ->when($topic !== '', fn (Builder $builder) => $this->applyLocationTopic($builder, $topic))
            ->when($region !== '', fn (Builder $builder) => $this->applyLocationRegion($builder, $region))
            ->when($query !== '' && $topic === '', fn (Builder $builder) => $this->applyLike($builder, $query, [
                'name', 'short_description', 'description', 'address', 'district',
            ]))
            ->orderByDesc('is_featured')
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit($limit * 3)
            ->get();

        return $this->rankLocations($locations, $understanding)->take($limit)->values();
    }

    private function searchTours(
        string $query,
        string $intent,
        int $limit,
        ?int $priceMax,
        ?int $priceMin,
        bool $cheapestFirst,
        array $understanding = []
    ): Collection {
        $date = $understanding['date'] ?? null;
        $people = $understanding['people'] ?? null;

        $builder = Tour::query()
            ->where('status', 'active')
            ->when($priceMax !== null, fn (Builder $builder) => $builder->where('price_adult', '<=', $priceMax))
            ->when($priceMin !== null, fn (Builder $builder) => $builder->where('price_adult', '>=', $priceMin))
            ->when($people !== null, function (Builder $builder) use ($people): void {
                $builder
                    ->where('min_people', '<=', $people)
                    ->where(function (Builder $nested) use ($people): void {
                        $nested->where('max_people', 0)->orWhere('max_people', '>=', $people);
                    });
            })
            ->when($date !== null, function (Builder $builder) use ($date): void {
                $builder
                    ->where(function (Builder $nested) use ($date): void {
                        $nested->whereNull('available_from')->orWhere('available_from', '<=', $date);
                    })
                    ->where(function (Builder $nested) use ($date): void {
                        $nested->whereNull('available_to')->orWhere('available_to', '>=', $date);
                    });
            })
            ->when($query !== '', fn (Builder $builder) => $this->applyLike($builder, $query, [
                'name', 'short_desc', 'description', 'duration', 'meeting_point',
            ]));

        if ($cheapestFirst) {
            $builder
                ->orderBy('price_adult')
                ->orderByDesc('rating_avg')
                ->orderByDesc('booking_count');
        } else {
            $builder
                ->orderByDesc('booking_count')
                ->orderByDesc('rating_avg')
                ->orderByDesc('is_hot');
        }

        return $this->rankTours($builder->limit($limit * 4)->get(), $understanding, $cheapestFirst)
            ->take($limit)
            ->values();
    }

    private function searchBlogs(string $query, string $intent, int $limit, array $understanding = []): Collection
    {
        if (! in_array($intent, ['blog', 'location', 'food', 'hotel', 'schedule', 'tour', 'greeting'], true)) {
            return collect();
        }

        $topic = (string) ($understanding['location_topic'] ?? '');
        $region = (string) ($understanding['region'] ?? '');

        return BlogPost::query()
            ->where('status', 'published')
            ->when($topic === 'beach', fn (Builder $builder) => $this->applyLike($builder, 'bãi biển beach', [
                'title', 'excerpt', 'content',
            ], 'or'))
            ->when($region !== '', fn (Builder $builder) => $this->applyLike($builder, $region, [
                'title', 'slug',
            ]))
            ->when($query !== '' && $topic === '', fn (Builder $builder) => $this->applyLike($builder, $query, [
                'title', 'excerpt', 'content',
            ]))
            ->orderByDesc('published_at')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    private function fallbackBlogs(int $limit): Collection
    {
        return BlogPost::query()
            ->where('status', 'published')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    private function fallbackLocations(string $intent, int $limit): Collection
    {
        return Location::query()
            ->where('status', 'active')
            ->when($intent === 'food', fn (Builder $builder) => $this->applyLike($builder, 'ăn nhà hàng quán hải sản đặc sản cafe cà phê ẩm thực', [
                'name', 'short_description', 'description',
            ], 'or'))
            ->when($intent === 'hotel', fn (Builder $builder) => $this->applyLike($builder, 'khách sạn hotel resort homestay lưu trú', [
                'name', 'short_description', 'description',
            ], 'or'))
            ->orderByDesc('is_featured')
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    private function fallbackTours(int $limit): Collection
    {
        return Tour::query()
            ->where('status', 'active')
            ->orderByDesc('booking_count')
            ->orderByDesc('is_hot')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get();
    }

    private function rankTours(Collection $tours, array $understanding, bool $cheapestFirst): Collection
    {
        return $tours
            ->map(function (Tour $tour) use ($understanding, $cheapestFirst): array {
                $score = 0.0;
                $destination = (string) ($understanding['destination'] ?? '');
                $priceMax = $understanding['max_price'] ?? null;
                $people = $understanding['people'] ?? null;
                $durationDays = $understanding['duration_days'] ?? null;
                $haystack = mb_strtolower(implode(' ', [
                    $tour->name,
                    $tour->short_desc,
                    $tour->description,
                    $tour->meeting_point,
                    $tour->duration,
                ]));
                $availability = $tour->booking_availability;
                $availabilityValue = $availability instanceof \BackedEnum
                    ? $availability->value
                    : (string) $availability;

                if ($cheapestFirst) {
                    $price = (float) $tour->price_adult;
                    $score = ($availabilityValue === 'open' ? 100000000 : 0) - $price;
                    if ($destination !== '' && str_contains($haystack, $destination)) {
                        $score += 1000000;
                    }
                    $score += ((float) ($tour->rating_avg ?? 0)) * 10;
                    $score += min((int) $tour->booking_count, 100);

                    return ['tour' => $tour, 'score' => $score];
                }

                if ($availabilityValue === 'open') {
                    $score += 120;
                }
                if ($destination !== '' && str_contains($haystack, $destination)) {
                    $score += 100;
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

                $score += ((float) ($tour->rating_avg ?? 0)) * 8;
                $score += min((int) $tour->booking_count, 100) * 0.5;
                $score += $tour->is_hot ? 8 : 0;
                $score += $tour->is_featured ? 6 : 0;

                return ['tour' => $tour, 'score' => $score];
            })
            ->sortByDesc('score')
            ->pluck('tour')
            ->values();
    }

    private function rankLocations(Collection $locations, array $understanding): Collection
    {
        return $locations
            ->map(function (Location $location) use ($understanding): array {
                $score = 0.0;
                $destination = (string) ($understanding['destination'] ?? '');
                $haystack = mb_strtolower(implode(' ', [
                    $location->name,
                    $location->short_description,
                    $location->description,
                    $location->address,
                    $location->district,
                ]));

                if ($destination !== '' && str_contains($haystack, $destination)) {
                    $score += 100;
                }

                $score += $location->is_featured ? 30 : 0;
                $score += ((float) $location->avg_rating) * 8;
                $score += min((int) $location->view_count, 1000) * 0.02;
                $score += min((int) $location->review_count, 300) * 0.05;

                return ['location' => $location, 'score' => $score];
            })
            ->sortByDesc('score')
            ->pluck('location')
            ->values();
    }

    /** @return Collection<int,array<string,mixed>> */
    private function tourContext(Collection $tours): Collection
    {
        return $tours->map(fn (Tour $tour) => [
            'type' => 'tour',
            'id' => $tour->id,
            'title' => $tour->name,
            'slug' => $tour->slug,
            'content' => trim(implode("\n", array_filter([
                "Tên tour: {$tour->name}",
                'Giá người lớn: '.number_format((float) $tour->price_adult, 0, ',', '.').' VND',
                $tour->duration ? "Thời lượng: {$tour->duration}" : null,
                $tour->meeting_point ? "Điểm đón: {$tour->meeting_point}" : null,
                $tour->short_desc ?: Str::limit(strip_tags((string) $tour->description), 260),
            ]))),
        ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    private function locationContext(Collection $locations): Collection
    {
        return $locations->map(fn (Location $location) => [
            'type' => 'location',
            'id' => $location->id,
            'title' => $location->name,
            'slug' => $location->slug,
            'content' => trim(implode("\n", array_filter([
                "Địa điểm: {$location->name}",
                "Địa chỉ: {$location->address}",
                $location->district ? "Khu vực: {$location->district}" : null,
                $location->price_min ? 'Giá tham khảo từ: '.number_format((float) $location->price_min, 0, ',', '.').' VND' : null,
                $location->short_description ?: Str::limit(strip_tags((string) $location->description), 260),
            ]))),
        ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    private function blogContext(Collection $blogs): Collection
    {
        return $blogs->map(fn (BlogPost $blog) => [
            'type' => 'blog',
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'content' => $blog->excerpt ?: Str::limit(strip_tags((string) $blog->content), 320),
        ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    private function vectorContext(Collection $knowledgeItems): Collection
    {
        return $knowledgeItems->map(fn (ChatKnowledgeBase $item) => [
            'type' => 'vector_'.$item->type,
            'id' => $item->reference_id,
            'title' => $item->title,
            'slug' => $item->reference_slug,
            'content' => mb_substr($item->content, 0, 1200),
            'metadata' => $item->metadata,
        ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    private function policyContext(string $intent): Collection
    {
        $items = [
            'payment' => 'DanangTrip hỗ trợ thanh toán bằng QR chuyển khoản SePay. Sau khi khách chuyển khoản đúng số tiền và đúng nội dung, hệ thống sẽ tự xác nhận đơn khi nhận IPN.',
            'refund' => 'Chính sách hủy tour và hoàn tiền phụ thuộc thời điểm hủy, điều kiện tour và trạng thái thanh toán. Khách nên kiểm tra chính sách trên màn đặt tour hoặc liên hệ hỗ trợ trước khi hủy.',
            'account' => 'Người dùng có thể đăng ký, đăng nhập, cập nhật hồ sơ, đổi mật khẩu, xem lịch sử đặt tour và quản lý đánh giá trong tài khoản DanangTrip.',
            'contact' => 'Khách hàng có thể gửi yêu cầu liên hệ qua form Liên hệ. Ban quản trị DanangTrip sẽ phản hồi qua email hoặc số điện thoại đã cung cấp.',
            'booking' => 'Khi đặt tour, khách cần chọn lịch khởi hành, số lượng khách và thông tin liên hệ. Đơn sẽ được xác nhận sau khi thanh toán thành công hoặc admin xử lý theo trạng thái đơn.',
            'loyalty' => implode(' ', [
                'DanangTrip có hệ thống điểm thưởng và voucher cho người dùng đã đăng ký.',
                'Người dùng được cộng 10 điểm khi thanh toán đơn tour thành công.',
                'Người dùng được cộng 5 điểm khi đánh giá tour hoặc địa điểm được duyệt.',
                'Nếu đánh giá được duyệt có ít nhất một ảnh đính kèm đã lưu thành công, người dùng được cộng thêm 3 điểm. Hệ thống hiện chỉ kiểm tra ảnh đính kèm, chưa tự động xác minh ảnh có phải ảnh thật hay không.',
                'Khi một người dùng khác đánh dấu đánh giá đã duyệt là hữu ích, chủ đánh giá được cộng 1 điểm cho mỗi lượt hợp lệ. Mỗi người chỉ được đánh dấu một lần cho một đánh giá và không được tự đánh dấu đánh giá của mình.',
                'Điểm nhận từ lượt hữu ích được giới hạn tối đa 10 điểm mỗi ngày cho mỗi chủ đánh giá. Lượt hữu ích vẫn được ghi nhận sau khi đạt giới hạn nhưng không cộng thêm điểm trong ngày đó.',
                'Mỗi đánh giá được thưởng thêm một lần 5 điểm khi đạt đúng mốc 5 lượt hữu ích và một lần 10 điểm khi đạt đúng mốc 10 lượt hữu ích. Điểm thưởng mốc được tính riêng với giới hạn điểm hữu ích hằng ngày.',
                'Đánh giá chỉ được nhận điểm sau khi quản trị viên duyệt. Nội dung bị từ chối không được cộng điểm; hệ thống hiện chưa tự động chấm toàn bộ nội dung spam, quá ngắn hoặc trùng lặp bằng AI.',
                'Điểm thưởng có thể dùng để đổi voucher giảm giá tour trong trang Ví điểm.',
            ]),
        ];

        if (! isset($items[$intent])) {
            return collect();
        }

        return collect([[
            'type' => 'policy',
            'id' => null,
            'title' => 'Chính sách DanangTrip',
            'slug' => null,
            'content' => $items[$intent],
        ]]);
    }

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
            'location_ids' => null,
            'status' => $tour->status,
            'is_featured' => (bool) $tour->is_featured,
            'is_hot' => (bool) $tour->is_hot,
            'view_count' => (int) $tour->view_count,
            'booking_count' => (int) $tour->booking_count,
            'avg_rating' => (string) ($tour->rating_avg ?? '0.00'),
            'review_count' => (int) ($tour->rating_count ?? 0),
            'created_by' => $tour->created_by,
            'created_at' => optional($tour->created_at)->toISOString(),
            'updated_at' => optional($tour->updated_at)->toISOString(),
        ];
    }

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
            'email' => $location->email,
            'website' => $location->website,
            'opening_hours' => $location->opening_hours,
            'price_min' => $location->price_min ? (float) $location->price_min : null,
            'price_max' => $location->price_max ? (float) $location->price_max : null,
            'price_level' => $location->price_level,
            'thumbnail' => $location->thumbnail,
            'images' => $location->images,
            'video_url' => $location->video_url,
            'status' => $location->status,
            'is_featured' => (bool) $location->is_featured,
            'view_count' => (int) $location->view_count,
            'favorite_count' => (int) $location->favorite_count,
            'avg_rating' => (string) $location->avg_rating,
            'review_count' => (int) $location->review_count,
            'created_at' => optional($location->created_at)->toISOString(),
            'updated_at' => optional($location->updated_at)->toISOString(),
        ];
    }

    private function blogPayload(BlogPost $blog): array
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $blog->content,
            'featured_image' => $blog->featured_image,
            'author_id' => $blog->author_id,
            'view_count' => (int) $blog->view_count,
            'status' => $blog->status,
            'published_at' => optional($blog->published_at)->toISOString(),
            'created_at' => optional($blog->created_at)->toISOString(),
            'updated_at' => optional($blog->updated_at)->toISOString(),
            'author' => null,
            'categories' => [],
        ];
    }

    private function applyLike(Builder $builder, string $query, array $columns, string $boolean = 'and'): Builder
    {
        $keywords = collect(explode(' ', $query))
            ->map(fn (string $word) => mb_strtolower(trim($word)))
            ->filter(fn (string $word) => mb_strlen($word) >= 2)
            ->take(8)
            ->values();

        if ($keywords->isEmpty()) {
            return $builder;
        }

        if ($boolean === 'or') {
            return $builder->where(function (Builder $nested) use ($keywords, $columns): void {
                foreach ($keywords as $keyword) {
                    $like = '%'.$this->escapeLike($keyword).'%';
                    foreach ($columns as $column) {
                        $nested->orWhereRaw("LOWER(CAST({$column} AS TEXT)) LIKE ?", [$like]);
                    }
                }
            });
        }

        return $builder->where(function (Builder $nested) use ($keywords, $columns): void {
            foreach ($keywords as $keyword) {
                $like = '%'.$this->escapeLike($keyword).'%';
                $nested->where(function (Builder $or) use ($columns, $like): void {
                    foreach ($columns as $column) {
                        $or->orWhereRaw("LOWER(CAST({$column} AS TEXT)) LIKE ?", [$like]);
                    }
                });
            }
        });
    }

    private function extractMaxPrice(string $query): ?int
    {
        if (! preg_match('/(?:dưới|duoi|nhỏ hơn|nho hon|<)\s*([\d\.,]+)\s*(triệu|trieu|nghìn|nghin|k)?/u', $query, $matches)) {
            return null;
        }

        $number = (float) str_replace(',', '.', str_replace('.', '', $matches[1]));
        $unit = $matches[2] ?? '';

        return match ($unit) {
            'triệu', 'trieu' => (int) ($number * 1000000),
            'nghìn', 'nghin', 'k' => (int) ($number * 1000),
            default => (int) $number,
        };
    }

    private function buildSearchQuery(string $query, string $intent, array $understanding): string
    {
        if (! empty($understanding['destination'])) {
            return (string) $understanding['destination'];
        }

        $search = $query;
        $patterns = [
            '/(?:dưới|duoi|trên|tren|từ|tu|nhỏ hơn|nho hon|tối đa|toi da)\s*[\d\.,]+\s*(triệu|trieu|nghìn|nghin|k)?/u',
            '/\d{1,2}\s*(người|nguoi|khách|khach|pax)/u',
            '/\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{4})?/u',
        ];

        foreach ($patterns as $pattern) {
            $search = preg_replace($pattern, ' ', $search) ?? $search;
        }

        $stopWords = [
            'tour', 'du lịch', 'hiện tại', 'hien tai', 'rẻ nhất', 'giá rẻ',
            'tốt nhất', 'nổi bật', 'còn chỗ', 'ngày mai', 'hôm nay',
            'cho', 'mình', 'tôi', 'có', 'không', 'nào', 'đi',
            'rẻ', 'đồng', 'vnd', 'giá', 'phải chăng',
            'gợi ý', 'goi y', 'bài viết', 'bai viet', 'cẩm nang', 'cam nang',
            'kinh nghiệm', 'kinh nghiem', 'blog', 'tin tức', 'tin tuc', 'về', 've',
            'đẹp', 'ở', 'tại', 'khu vực', 'đà nẵng', 'danang',
        ];

        $search = str_replace($stopWords, ' ', $search);
        $search = preg_replace('/\s+/u', ' ', trim((string) $search));

        if (is_string($search) && $search !== '') {
            return $search;
        }

        return in_array($intent, ['tour', 'booking', 'schedule'], true) ? '' : $query;
    }

    private function applyLocationTopic(Builder $builder, string $topic): Builder
    {
        if ($topic === 'beach') {
            return $builder
                ->where(function (Builder $beaches): void {
                    $beaches
                        ->whereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', ['%bãi biển%'])
                        ->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', ['% beach'])
                        ->orWhereHas('tags', function (Builder $tag): void {
                            $tag->where(function (Builder $names): void {
                                $names
                                    ->whereRaw('LOWER(CAST(name AS TEXT)) = ?', ['beach'])
                                    ->orWhereRaw('LOWER(CAST(name AS TEXT)) = ?', ['bãi biển']);
                            });
                        });
                })
                ->whereRaw('LOWER(CAST(name AS TEXT)) NOT LIKE ?', ['%hotel%'])
                ->whereRaw('LOWER(CAST(name AS TEXT)) NOT LIKE ?', ['%resort%'])
                ->whereRaw('LOWER(CAST(name AS TEXT)) NOT LIKE ?', ['%spa%']);
        }

        $terms = match ($topic) {
            'food' => ['ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản'],
            'hotel' => ['khách sạn', 'hotel', 'resort', 'homestay'],
            'spiritual' => ['chùa', 'nhà thờ', 'tâm linh'],
            'nature' => ['thiên nhiên', 'núi', 'hang động', 'thác'],
            'park' => ['công viên', 'vườn hoa'],
            'museum' => ['bảo tàng', 'di tích'],
            'market' => ['chợ', 'mua sắm'],
            default => [],
        };

        if ($terms === []) {
            return $builder;
        }

        return $builder->where(function (Builder $nested) use ($terms, $topic): void {
            foreach ($terms as $term) {
                $like = '%'.$this->escapeLike($term).'%';
                $nested->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$like]);

                if ($topic !== 'beach') {
                    $nested
                        ->orWhereRaw('LOWER(CAST(short_description AS TEXT)) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(CAST(description AS TEXT)) LIKE ?', [$like]);
                }
            }

            $nested
                ->orWhereHas('category', function (Builder $category) use ($terms): void {
                    $category->where(fn (Builder $names) => $this->applyRelationNameTerms($names, $terms));
                })
                ->orWhereHas('subcategory', function (Builder $subcategory) use ($terms): void {
                    $subcategory->where(fn (Builder $names) => $this->applyRelationNameTerms($names, $terms));
                })
                ->orWhereHas('tags', function (Builder $tag) use ($terms): void {
                    $tag->where(fn (Builder $names) => $this->applyRelationNameTerms($names, $terms));
                });
        });
    }

    /** @param array<int,string> $terms */
    private function applyRelationNameTerms(Builder $builder, array $terms): Builder
    {
        foreach ($terms as $term) {
            $builder->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', ['%'.$this->escapeLike($term).'%']);
        }

        return $builder;
    }

    private function applyLocationRegion(Builder $builder, string $region): Builder
    {
        $terms = match ($region) {
            'đà nẵng' => ['đà nẵng', 'da nang', 'danang'],
            'hội an' => ['hội an', 'hoi an'],
            'huế' => ['huế', 'hue'],
            'quảng nam' => ['quảng nam', 'quang nam'],
            default => [$region],
        };

        return $builder->where(function (Builder $nested) use ($terms): void {
            foreach ($terms as $term) {
                $like = '%'.$this->escapeLike($term).'%';
                $nested
                    ->orWhereRaw('LOWER(CAST(address AS TEXT)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(district AS TEXT)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(ward AS TEXT)) LIKE ?', [$like]);
            }
        });
    }

    private function hasNoHardTourConstraints(array $understanding, ?int $priceMax, ?int $priceMin): bool
    {
        return $priceMax === null
            && $priceMin === null
            && empty($understanding['destination'])
            && empty($understanding['people'])
            && empty($understanding['date']);
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
            're nhat' => 'rẻ nhất',
            'gia re' => 'giá rẻ',
            'duoi' => 'dưới',
            'ba na' => 'bà nà',
            'hoi an' => 'hội an',
            'da nang' => 'đà nẵng',
        ];

        return strtr($text, $aliases);
    }

    private function isCheapestQuery(string $query): bool
    {
        foreach (['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price'] as $keyword) {
            if (str_contains($query, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
