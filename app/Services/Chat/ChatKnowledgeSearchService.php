<?php

namespace App\Services\Chat;

use App\Enums\TourScheduleBookingAvailability;
use App\Models\BlogPost;
use App\Models\ChatKnowledgeBase;
use App\Models\Location;
use App\Models\Tour;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Knowledge Search Service
 *
 * Thực hiện SQL Search (Tours, Locations, Blogs) và Vector Search.
 * Việc merge/rank/deduplicate được tách sang ChatRecommendationBuilderService.
 */
final class ChatKnowledgeSearchService
{
    // Giới hạn cứng cho từng loại search
    private const SQL_TOUR_LIMIT = 50;

    private const SQL_LOCATION_LIMIT = 50;

    private const SQL_BLOG_LIMIT = 20;

    private const VECTOR_LIMIT = 20;

    /**
     * Khởi tạo dịch vụ tìm kiếm tri thức.
     *
     * @param  ChatVectorSearchService  $vectorSearch  Dịch vụ tìm kiếm vector tri thức
     */
    public function __construct(
        private readonly ChatVectorSearchService $vectorSearch
    ) {}

    /**
     * Tìm kiếm thông tin du lịch từ nhiều nguồn dữ liệu (SQL, Vector) dựa trên từ khóa và tham số ý định.
     *
     * @param  string  $question  Câu hỏi gốc của người dùng
     * @param  string  $intent  Ý định nghiệp vụ đã phân tích
     * @param  int  $limit  Giới hạn kết quả tối đa cần lấy ra
     * @param  array<string,mixed>  $understanding  Kết quả phân tích chi tiết của câu hỏi
     * @return array{
     *     sql_results: array{tours: Collection, locations: Collection, blogs: Collection},
     *     vector_results: Collection,
     *     context: array<int,array<string,mixed>>,
     *     center: array<int,float>,
     *     zoom: int
     * }
     */
    public function search(string $question, string $intent, int $limit, array $understanding = []): array
    {
        $limit = max(1, min($limit, 10));
        $query = (string) ($understanding['normalized_question'] ?? $this->normalize($question));
        $contentTypes = $this->resolveContentTypes($intent, $understanding);
        $keywords = (array) ($understanding['keywords'] ?? []);
        $topics = (array) ($understanding['topics'] ?? []);

        $priceMax = $understanding['max_price'] ?? $this->extractMaxPrice($query);
        $priceMin = $understanding['min_price'] ?? null;
        $cheapest = (bool) ($understanding['cheapest_first'] ?? $this->isCheapestQuery($query));

        // === SQL Search theo content_types ===
        $tours = in_array('tour', $contentTypes)
            ? $this->searchTours($query, $intent, self::SQL_TOUR_LIMIT, $priceMax, $priceMin, $cheapest, $understanding)
            : collect();

        $locations = in_array('location', $contentTypes)
            ? $this->searchLocations($query, $intent, self::SQL_LOCATION_LIMIT, $understanding, $topics)
            : collect();

        $blogs = in_array('blog', $contentTypes)
            ? $this->searchBlogs($query, $intent, self::SQL_BLOG_LIMIT, $understanding, $keywords)
            : collect();

        // === Fallback nếu không có kết quả ===
        if ($tours->isEmpty() && in_array('tour', $contentTypes)) {
            $tours = $this->hasNoHardTourConstraints($understanding, $priceMax, $priceMin)
                ? $this->fallbackTours(self::SQL_TOUR_LIMIT)
                : collect();
        }

        if ($locations->isEmpty() && in_array('location', $contentTypes) && $this->hasNoHardLocationConstraints($understanding)) {
            $locations = $this->fallbackLocations($intent, self::SQL_LOCATION_LIMIT, $topics, $understanding);
        }

        if ($blogs->isEmpty() && in_array($intent, ['blog', 'schedule'], true)) {
            $blogs = $this->fallbackBlogs(self::SQL_BLOG_LIMIT);
        }

        // === Vector Search ===
        $vectorKnowledge = $this->vectorSearch->search(
            $query ?: (string) ($understanding['normalized_question'] ?? $question),
            $intent,
            self::VECTOR_LIMIT
        );

        // === Policy Context ===
        $policies = $this->policyContext($intent);

        // === Build RAG Context (cho Response Generator) ===
        $context = $this->buildRagContext($tours, $locations, $blogs, $vectorKnowledge, $policies, $limit + 4);

        // === Map center cho bản đồ ===
        $firstLocation = $locations->first();
        $center = $firstLocation instanceof Location
            ? [(float) $firstLocation->latitude, (float) $firstLocation->longitude]
            : [16.0544, 108.2022];

        return [
            'sql_results' => [
                'tours' => $tours,
                'locations' => $locations,
                'blogs' => $blogs,
            ],
            'vector_results' => $vectorKnowledge,
            'context' => $context,
            'center' => $center,
            'zoom' => $firstLocation instanceof Location ? 13 : 12,
        ];
    }

    /**
     * Xác định danh sách loại nội dung (content_types) cần tìm kiếm dựa trên ý định nghiệp vụ và gợi ý.
     *
     * @param  string  $intent  Ý định nghiệp vụ
     * @param  array<string,mixed>  $understanding  Kết quả phân tích chi tiết
     * @return array<int,string> Mảng chứa các loại nội dung phù hợp (tour, location, blog, policy...)
     */
    private function resolveContentTypes(string $intent, array $understanding): array
    {
        // Nếu là policy intent, chỉ search policy, bypass mọi hints
        if (in_array($intent, ['payment', 'refund', 'loyalty', 'account', 'contact'], true)) {
            return ['policy'];
        }

        // AI NLU đã set content_types → dùng luôn
        $fromAi = (array) ($understanding['content_types'] ?? []);
        if (! empty($fromAi)) {
            return $fromAi;
        }

        // Rule-based hints
        $fromHints = (array) ($understanding['content_type_hints'] ?? []);
        if (! empty($fromHints)) {
            return $fromHints;
        }

        // Default theo intent
        return match ($intent) {
            'tour', 'booking', 'schedule' => ['tour', 'blog'],
            'location', 'food', 'hotel' => ['location', 'blog'],
            'blog' => ['blog'],
            'payment', 'refund', 'loyalty' => ['policy'],
            'account', 'contact' => ['policy'],
            'greeting' => ['tour', 'location', 'blog'],
            default => ['tour', 'location', 'blog'],
        };
    }

    /**
     * Thực hiện tìm kiếm các địa điểm du lịch bằng truy vấn SQL dựa trên từ khóa, khu vực, chủ đề.
     *
     * @param  string  $query  Truy vấn đã chuẩn hóa
     * @param  string  $intent  Ý định nghiệp vụ
     * @param  int  $limit  Giới hạn số lượng bản ghi
     * @param  array<string,mixed>  $understanding  Thực thể đã trích xuất
     * @param  array<int,string>  $topics  Danh sách chủ đề cần lọc
     */
    private function searchLocations(
        string $query,
        string $intent,
        int $limit,
        array $understanding = [],
        array $topics = []
    ): Collection {
        $topic = (string) ($understanding['location_topic'] ?? '');
        $region = (string) ($understanding['region'] ?? '');

        // Nếu AI đã đặt topics, ưu tiên dùng topics (chính xác hơn location_topic rule-based)
        $aiTopics = (array) ($understanding['topics'] ?? []);

        $locations = Location::query()
            ->where('status', 'active')
            ->when($region !== '', fn (Builder $builder) => $this->applyLocationRegion($builder, $region))
            ->when(! empty($aiTopics), fn (Builder $builder) => $this->applyTopicFilter($builder, $aiTopics))
            ->when(empty($aiTopics) && $topic !== '', fn (Builder $builder) => $this->applyLocationTopic($builder, $topic))
            ->when($query !== '' && $topic === '' && empty($aiTopics), fn (Builder $builder) => $this->applyLike($builder, $query, [
                'name', 'short_description', 'description', 'address', 'district',
            ]))
            ->when(! empty($understanding['keywords']), function (Builder $builder) use ($understanding) {
                $kwQuery = implode(' ', (array) $understanding['keywords']);
                if ($kwQuery !== '') {
                    $this->applyLike($builder, $kwQuery, ['name', 'short_description', 'description'], 'or');
                }
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();

        return $locations;
    }

    /**
     * Thực hiện tìm kiếm các tour du lịch bằng truy vấn SQL dựa trên các ràng buộc về giá, số người, ngày khởi hành, địa danh.
     *
     * @param  string  $query  Truy vấn đã chuẩn hóa
     * @param  string  $intent  Ý định nghiệp vụ
     * @param  int  $limit  Giới hạn kết quả
     * @param  int|null  $priceMax  Giá lớn nhất
     * @param  int|null  $priceMin  Giá nhỏ nhất
     * @param  bool  $cheapestFirst  Ưu tiên giá rẻ nhất
     * @param  array<string,mixed>  $understanding  Các thực thể trích xuất
     */
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
        $keywords = (array) ($understanding['keywords'] ?? []);
        $durationDays = $understanding['duration_days'] ?? null;
        $destinationId = isset($understanding['destination_id']) ? (int) $understanding['destination_id'] : null;
        $destinationName = (string) ($understanding['destination'] ?? '');

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
            ->when($durationDays !== null, function (Builder $builder) use ($durationDays): void {
                $builder->where(function (Builder $nested) use ($durationDays): void {
                    $nested->whereRaw('LOWER(duration) LIKE ?', ["%{$durationDays} day%"])
                        ->orWhereRaw('LOWER(duration) LIKE ?', ["%{$durationDays} ngày%"])
                        ->orWhereRaw('LOWER(duration) LIKE ?', ["%{$durationDays} %"])
                        ->orWhereRaw('LOWER(duration) LIKE ?', ["%{$durationDays}d%"]);
                });
            })
            // === DESTINATION FILTER (ưu tiên destination_id → rồi mới đến LIKE) ===
            ->when($destinationId !== null, function (Builder $builder) use ($destinationId, $destinationName): void {
                // Nếu có destination_id đã được normalize → filter qua pivot tour_locations
                $builder->where(function (Builder $nested) use ($destinationId, $destinationName): void {
                    $nested->whereHas('locations', fn (Builder $loc) => $loc->where('locations.id', $destinationId));
                    // Fallback: name/desc chứa tên địa điểm (đề phòng tour không có location liên kết)
                    if ($destinationName !== '') {
                        $nameLike = '%'.$this->escapeLike(mb_strtolower($destinationName)).'%';
                        $nested
                            ->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$nameLike])
                            ->orWhereRaw('LOWER(CAST(short_desc AS TEXT)) LIKE ?', [$nameLike])
                            ->orWhereRaw('LOWER(CAST(description AS TEXT)) LIKE ?', [$nameLike]);
                    }
                });
            })
            ->when($destinationId === null && $destinationName !== '', function (Builder $builder) use ($destinationName): void {
                // Không có destination_id → fallback LIKE theo destination name
                $nameLike = '%'.$this->escapeLike(mb_strtolower($destinationName)).'%';
                $builder->where(function (Builder $nested) use ($nameLike): void {
                    $nested
                        ->whereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$nameLike])
                        ->orWhereRaw('LOWER(CAST(short_desc AS TEXT)) LIKE ?', [$nameLike])
                        ->orWhereRaw('LOWER(CAST(description AS TEXT)) LIKE ?', [$nameLike]);
                });
            })
            // === KEYWORD SEARCH (chỉ áp dụng khi không có destination cụ thể) ===
            ->when($destinationId === null && $destinationName === '' && $query !== '', fn (Builder $builder) => $this->applyLike($builder, $query, [
                'name', 'short_desc', 'description', 'duration', 'meeting_point',
            ]))
            ->when(! empty($keywords) && $destinationId === null && $destinationName === '', function (Builder $builder) use ($keywords) {
                $kwQuery = implode(' ', $keywords);
                if ($kwQuery !== '') {
                    $this->applyLike($builder, $kwQuery, ['name', 'short_desc', 'description'], 'or');
                }
            });

        if ($cheapestFirst) {
            $builder->orderBy('price_adult')->orderByDesc('rating_avg')->orderByDesc('booking_count');
        } else {
            $builder->orderByDesc('booking_count')->orderByDesc('rating_avg')->orderByDesc('is_hot');
        }

        return $builder->limit($limit)->get();
    }

    /**
     * Tìm kiếm bài viết (blog) liên quan đến chủ đề du lịch, địa điểm hoặc từ khóa.
     *
     * @param  string  $query  Truy vấn đã chuẩn hóa
     * @param  string  $intent  Ý định nghiệp vụ
     * @param  int  $limit  Giới hạn kết quả
     * @param  array<string,mixed>  $understanding  Các thực thể trích xuất
     * @param  array<int,string>  $keywords  Danh sách từ khóa
     */
    private function searchBlogs(
        string $query,
        string $intent,
        int $limit,
        array $understanding = [],
        array $keywords = []
    ): Collection {
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
            ->when(! empty($keywords), function (Builder $builder) use ($keywords) {
                $kwQuery = implode(' ', $keywords);
                $this->applyLike($builder, $kwQuery, ['title', 'excerpt', 'content'], 'or');
            })
            ->when($query !== '' && $topic === '' && empty($keywords), fn (Builder $builder) => $this->applyLike($builder, $query, [
                'title', 'excerpt', 'content',
            ], 'or'))
            ->orderByDesc('published_at')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Trả về danh sách bài viết mặc định khi không tìm thấy kết quả tìm kiếm theo từ khóa.
     *
     * @param  int  $limit  Giới hạn kết quả
     */
    private function fallbackBlogs(int $limit): Collection
    {
        return BlogPost::query()
            ->where('status', 'published')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Trả về danh sách địa điểm mặc định khi tìm kiếm chính xác không có kết quả.
     *
     * @param  string  $intent  Ý định nghiệp vụ
     * @param  int  $limit  Giới hạn kết quả
     * @param  array<int,string>  $topics  Danh sách chủ đề
     * @param  array<string,mixed>  $understanding  Các thực thể trích xuất
     */
    private function fallbackLocations(string $intent, int $limit, array $topics = [], array $understanding = []): Collection
    {
        $region = (string) ($understanding['region'] ?? '');

        return Location::query()
            ->where('status', 'active')
            ->when($region !== '', fn (Builder $builder) => $this->applyLocationRegion($builder, $region))
            ->when($intent === 'food' || in_array('local_food', $topics, true) || in_array('restaurant', $topics, true) || in_array('cafe', $topics, true), function (Builder $builder): void {
                $builder->whereIn('category_id', [1, 2, 3, 4, 15, 31, 32, 65])
                    ->where(fn (Builder $nested) => $this->applyLike($nested, 'ăn nhà hàng quán hải sản đặc sản cafe cà phê ẩm thực', [
                        'name', 'short_description', 'description',
                    ], 'or'));
            })
            ->when($intent === 'hotel' || in_array('hotel', $topics, true) || in_array('resort', $topics, true) || in_array('homestay', $topics, true), function (Builder $builder): void {
                $builder->whereIn('category_id', [5, 6])
                    ->where(fn (Builder $nested) => $this->applyLike($nested, 'khách sạn hotel resort homestay lưu trú', [
                        'name', 'short_description', 'description',
                    ], 'or'));
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('avg_rating')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Trả về danh sách các tour nổi bật/mặc định khi không có kết quả tìm kiếm chính xác.
     *
     * @param  int  $limit  Giới hạn kết quả
     */
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

    /**
     * Xây dựng ngữ cảnh RAG (Retrieval-Augmented Generation) từ tất cả các nguồn dữ liệu để gửi cho AI Response Generator.
     *
     * @param  Collection  $tours  Tập hợp các tour tìm được
     * @param  Collection  $locations  Tập hợp các địa điểm tìm được
     * @param  Collection  $blogs  Tập hợp các bài viết tìm được
     * @param  Collection  $vectorKnowledge  Kết quả từ Vector search
     * @param  Collection  $policies  Nội dung chính sách phù hợp
     * @param  int  $limit  Giới hạn số lượng ngữ cảnh tối đa
     * @return array<int,array<string,mixed>> Mảng chứa các ngữ cảnh thông tin phẳng
     */
    private function buildRagContext(
        Collection $tours,
        Collection $locations,
        Collection $blogs,
        Collection $vectorKnowledge,
        Collection $policies,
        int $limit
    ): array {
        return collect()
            ->merge($this->tourContext($tours))
            ->merge($this->locationContext($locations))
            ->merge($this->blogContext($blogs))
            ->merge($this->vectorContext($vectorKnowledge))
            ->merge($policies)
            ->unique(fn (array $item): string => (string) ($item['type'] ?? '').':'.(string) ($item['id'] ?? $item['slug'] ?? md5((string) ($item['content'] ?? ''))))
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Chuyển đổi dữ liệu Eloquent model Tour thành dạng ngữ cảnh phẳng thích hợp cho RAG.
     * Đồng thời nạp trước (eager load) lịch khởi hành khả dụng của tour.
     *
     * @param  Collection  $tours  Tập hợp các tour
     * @return Collection Mảng ngữ cảnh tour
     */
    private function tourContext(Collection $tours): Collection
    {
        \Illuminate\Database\Eloquent\Collection::make($tours)->load(['schedules' => function ($query): void {
            $query->where('status', 'available')
                ->where('booking_availability', TourScheduleBookingAvailability::OPEN->value)
                ->where('start_date', '>=', now()->startOfDay())
                ->orderBy('start_date')
                ->limit(5);
        }]);

        return $tours->map(function (Tour $tour) {
            $scheduleDates = $tour->schedules
                ->map(fn ($s) => $s->start_date instanceof Carbon || $s->start_date instanceof CarbonImmutable
                    ? $s->start_date->format('d/m/Y')
                    : Carbon::parse($s->start_date)->format('d/m/Y')
                )
                ->unique()
                ->implode(', ');

            return [
                'type' => 'tour',
                'id' => $tour->id,
                'title' => $tour->name,
                'slug' => $tour->slug,
                'content' => trim(implode("\n", array_filter([
                    "Tên tour: {$tour->name}",
                    'Giá người lớn: '.number_format((float) $tour->price_adult, 0, ',', '.').' VND',
                    $tour->duration ? "Thời lượng: {$tour->duration}" : null,
                    $tour->meeting_point ? "Điểm đón: {$tour->meeting_point}" : null,
                    $scheduleDates ? "Lịch khởi hành: {$scheduleDates}" : 'Lịch khởi hành: Liên hệ để biết thêm chi tiết',
                    $tour->short_desc ?: Str::limit(strip_tags((string) $tour->description), 260),
                ]))),
            ];
        });
    }

    /**
     * Chuyển đổi thông tin địa điểm thành ngữ cảnh phẳng thích hợp cho RAG.
     *
     * @param  Collection  $locations  Tập hợp các địa điểm
     * @return Collection Mảng ngữ cảnh địa điểm
     */
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

    /**
     * Chuyển đổi thông tin bài viết thành ngữ cảnh phẳng thích hợp cho RAG.
     *
     * @param  Collection  $blogs  Tập hợp các bài viết
     * @return Collection Mảng ngữ cảnh bài viết
     */
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

    /**
     * Định dạng kết quả từ Vector Search tri thức thành ngữ cảnh phẳng thích hợp cho RAG.
     *
     * @param  Collection  $knowledgeItems  Tập hợp kết quả vector tri thức
     * @return Collection Mảng ngữ cảnh phẳng
     */
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

    /**
     * Trả về ngữ cảnh chính sách (payment, refund, loyalty, account, contact...) thích hợp với ý định hiện tại.
     *
     * @param  string  $intent  Ý định nghiệp vụ hiện tại
     * @return Collection Mảng chứa ngữ cảnh chính sách hoặc rỗng
     */
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
                'Nếu đánh giá được duyệt có ít nhất một ảnh đính kèm đã lưu thành công, người dùng được cộng thêm 3 điểm.',
                'Khi một người dùng khác đánh dấu đánh giá đã duyệt là hữu ích, chủ đánh giá được cộng 1 điểm cho mỗi lượt hợp lệ.',
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

    /**
     * Tự động lọc các từ nhiễu (stop words) và áp dụng tìm kiếm chứa chuỗi (LIKE) trên các cột chỉ định.
     *
     * @param  Builder  $builder  Eloquent Builder
     * @param  string  $query  Truy vấn tìm kiếm thô
     * @param  array<int,string>  $columns  Các cột cơ sở dữ liệu cần tìm kiếm
     * @param  string  $boolean  Phép toán logic khi gộp các từ khóa ('and' hoặc 'or')
     * @return Builder Eloquent Builder đã được áp dụng điều kiện tìm kiếm LIKE
     */
    private function applyLike(Builder $builder, string $query, array $columns, string $boolean = 'and'): Builder
    {
        $stopWords = [
            'có', 'co', 'nào', 'nao', 'không', 'khong', 'bao', 'nhiêu', 'nhieu', 'là', 'la',
            'cho', 'tôi', 'toi', 'em', 'mình', 'minh', 'bạn', 'ban', 'với', 'voi', 'và', 'va',
            'ở', 'o', 'tại', 'tai', 'đến', 'den', 'cần', 'can', 'tìm', 'tim', 'hỏi', 'hoi',
            'xin', 'chào', 'chao', 'giúp', 'giup', 'được', 'duoc', 'xem', 'muốn', 'muon',
            'nhé', 'nhe', 'nha', 'ạ', 'a', 'ơi', 'oi', 'gì', 'gi', 'đâu', 'dau',
            'dưới', 'duoi', 'trên', 'tren', 'trong', 'khoảng', 'khoang', 'tầm', 'tam',
            'từ', 'tu', 'đồng', 'dong', 'vnd', 'nghìn', 'nghin', 'k', 'triệu', 'trieu',
            'tr', 'm', 'hộ', 'ho', 'giùm', 'gium', 'chỉ', 'chi',
            'tốt', 'tot', 'nhất', 'nhat', 'hay', 'đẹp', 'dep', 'nổi', 'noi', 'bật', 'bat',
            'đánh', 'danh', 'giá', 'gia', 'cao', 'tiếng', 'tieng', 'best', 'top', 'highly',
            'rated', 'popular', 'ngon', 'bổ', 'bo', 'rẻ', 're', 'gợi', 'goi', 'ý', 'y',
            'gần', 'gan', 'nên', 'nen', 'thể', 'the', 'nhận', 'nhan', 'như', 'nhu',
            'thế', 'the', 'các', 'cac', 'ta', 'đây', 'day', 'đó', 'do',
            'hiện', 'hien', 'nay',
            'đi', 'di', 'lên', 'len', 'xuống', 'xuong', 'tới', 'toi', 'qua', 'về', 've',
            'tour', 'tua', 'du', 'lịch', 'dulich', 'chơi', 'choi', 'tham', 'quan',
            'ăn', 'an', 'uống', 'uong', 'món', 'mon', 'quán', 'quan', 'nhà', 'nha', 'hàng', 'hang',
            'đặc', 'dac', 'sản', 'san',
        ];

        // Loại bỏ dấu câu cơ bản trước khi tách từ
        $cleanQuery = preg_replace('/[?,.!;:""\'\'()]/u', ' ', $query) ?? $query;

        $keywords = collect(explode(' ', $cleanQuery))
            ->map(fn (string $word) => mb_strtolower(trim($word)))
            ->filter(fn (string $word) => mb_strlen($word) >= 2 && ! in_array($word, $stopWords, true))
            ->filter(function (string $word) {
                if (is_numeric($word) && (float) $word > 10) {
                    return false;
                }
                if (preg_match('/\d+/', $word, $m)) {
                    $num = (float) $m[0];
                    if ($num > 10 || preg_match('/(?:k|triệu|trieu|nghìn|nghin|tr|đồng|dong|vnd)/u', $word)) {
                        return false;
                    }
                }

                return true;
            })
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

    /**
     * Lọc địa điểm trong database theo danh sách chủ đề được NLU phân tích.
     *
     * @param  Builder  $builder  Eloquent Builder của bảng địa điểm
     * @param  array<int,string>  $topics  Mảng chứa tên chủ đề
     * @return Builder Eloquent Builder đã được áp dụng bộ lọc chủ đề
     */
    private function applyTopicFilter(Builder $builder, array $topics): Builder
    {
        $topicTermMap = [
            'local_food' => ['ẩm thực', 'nhà hàng', 'quán ăn', 'đặc sản', 'food', 'restaurant'],
            'restaurant' => ['nhà hàng', 'restaurant', 'quán ăn'],
            'cafe' => ['cafe', 'cà phê', 'coffee'],
            'seafood' => ['hải sản', 'seafood', 'tôm', 'cua'],
            'hotel' => ['khách sạn', 'hotel'],
            'resort' => ['resort'],
            'homestay' => ['homestay'],
            'beach' => ['bãi biển', 'beach'],
            'mountain' => ['núi', 'mountain'],
            'temple' => ['chùa', 'đền', 'temple', 'tâm linh'],
            'museum' => ['bảo tàng', 'museum', 'di tích'],
            'market' => ['chợ', 'market'],
            'park' => ['công viên', 'vườn'],
        ];

        $allTerms = [];
        foreach ($topics as $topic) {
            $allTerms = array_merge($allTerms, $topicTermMap[$topic] ?? []);
        }
        $allTerms = array_unique($allTerms);

        if (empty($allTerms)) {
            return $builder;
        }

        return $builder->where(function (Builder $nested) use ($allTerms): void {
            foreach ($allTerms as $term) {
                $like = '%'.$this->escapeLike($term).'%';
                $nested
                    ->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(short_description AS TEXT)) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(description AS TEXT)) LIKE ?', [$like]);
            }
        });
    }

    /**
     * Phân tích và trích xuất mức giá tối đa từ chuỗi truy vấn thô (fallback cho rule-based).
     *
     * @param  string  $query  Truy vấn cần phân tích
     * @return int|null Giá tối đa dạng integer VNĐ, hoặc null
     */
    private function extractMaxPrice(string $query): ?int
    {
        if (! preg_match('/(?:dưới|duoi|nhỏ hơn|nho hon|<|không quá)\s*([\d\.,]+)\s*(triệu|trieu|nghìn|nghin|k)?/u', $query, $matches)) {
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

    /**
     * Lọc địa điểm trong database theo chủ đề địa điểm (location topic) đơn lẻ được phát hiện.
     *
     * @param  Builder  $builder  Eloquent Builder địa điểm
     * @param  string  $topic  Tên chủ đề
     */
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
            'cafe' => ['cafe', 'cà phê', 'coffee'],
            default => [],
        };

        if ($terms === []) {
            return $builder;
        }

        // Apply category restrictions for specific topics to avoid cross-contamination
        if ($topic === 'cafe') {
            $builder->whereIn('category_id', [3]); // Category 3: Cà phê & Trà sữa
        } elseif ($topic === 'food') {
            $builder->whereIn('category_id', [1, 2, 3, 4, 15, 31, 32, 65]);
        } elseif ($topic === 'hotel') {
            $builder->whereIn('category_id', [5, 6]); // Category 5, 6: Resort, Villa, Hotel, Homestay
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

    /**
     * Tìm kiếm từ khóa chứa chuỗi (LIKE) trên tên của mối quan hệ liên kết (như tags, category).
     *
     * @param  Builder  $builder  Builder của quan hệ
     * @param  array<int,string>  $terms  Các từ khóa tìm kiếm
     */
    private function applyRelationNameTerms(Builder $builder, array $terms): Builder
    {
        foreach ($terms as $term) {
            $builder->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', ['%'.$this->escapeLike($term).'%']);
        }

        return $builder;
    }

    /**
     * Lọc địa điểm du lịch theo khu vực địa lý (như đà nẵng, hội an, huế...).
     *
     * @param  Builder  $builder  Eloquent Builder địa điểm
     * @param  string  $region  Khu vực địa lý cần lọc
     */
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

    /**
     * Kiểm tra xem câu hỏi có chứa bất kỳ ràng buộc cứng nào về tour (giá, người, ngày, địa danh) hay không.
     *
     * @param  array<string,mixed>  $understanding  Kết quả phân tích
     * @param  int|null  $priceMax  Giá tối đa
     * @param  int|null  $priceMin  Giá tối thiểu
     * @return bool Trả về true nếu không có ràng buộc cứng nào
     */
    private function hasNoHardTourConstraints(array $understanding, ?int $priceMax, ?int $priceMin): bool
    {
        return $priceMax === null
            && $priceMin === null
            && empty($understanding['destination'])
            && empty($understanding['people'])
            && empty($understanding['date']);
    }

    /**
     * Kiểm tra xem câu hỏi có chứa các ràng buộc cứng về địa điểm hay không.
     *
     * @param  array<string,mixed>  $understanding  Kết quả phân tích
     * @return bool Trả về true nếu không có ràng buộc
     */
    private function hasNoHardLocationConstraints(array $understanding): bool
    {
        return empty($understanding['location_topic'])
            && empty($understanding['topics']);
    }

    /**
     * Chuẩn hóa văn bản: loại bỏ khoảng trắng dư thừa và chuyển về chữ thường.
     *
     * @param  string  $value  Chuỗi văn bản gốc
     * @return string Văn bản chuẩn hóa
     */
    private function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return mb_strtolower(is_string($normalized) ? $normalized : trim($value));
    }

    /**
     * Kiểm tra xem câu hỏi có chứa các từ khóa liên quan đến việc tìm đồ rẻ nhất hay không.
     *
     * @param  string  $query  Truy vấn cần kiểm tra
     * @return bool Trả về true nếu chứa từ khóa giá rẻ
     */
    private function isCheapestQuery(string $query): bool
    {
        foreach (['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price', 'affordable'] as $keyword) {
            if (str_contains($query, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Escape các ký tự đặc biệt trong câu lệnh LIKE SQL để tránh lỗi cú pháp hoặc SQL injection qua LIKE.
     *
     * @param  string  $value  Giá trị cần escape
     * @return string Giá trị an toàn
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Xây dựng ngữ cảnh RAG được căn chỉnh, trong đó ưu tiên hiển thị các gợi ý (recommendations)
     * đã được gợi ý lên trước, sau đó điền thêm các thông tin phụ từ SQL & Vector cho đủ giới hạn.
     *
     * @param  array<int,array{type:string,data:array<string,mixed>}>  $recommendations  Danh sách gợi ý từ builder
     * @param  array{tours:Collection,locations:Collection,blogs:Collection}  $sqlResults  Kết quả tìm kiếm SQL
     * @param  Collection<int,ChatKnowledgeBase>  $vectorResults  Kết quả từ Vector search
     * @param  string  $intent  Ý định nghiệp vụ hiện tại
     * @param  int  $limit  Giới hạn số lượng ngữ cảnh tối đa
     * @return array<int,array<string,mixed>> Ngữ cảnh RAG đã được căn chỉnh và sắp xếp
     */
    public function buildAlignedContext(
        array $recommendations,
        array $sqlResults,
        Collection $vectorResults,
        string $intent,
        int $limit
    ): array {
        $primaryContext = collect();

        // 1. Group recommended items by type
        $recTourIds = [];
        $recLocationIds = [];
        $recBlogIds = [];

        foreach ($recommendations as $rec) {
            $type = $rec['type'] ?? '';
            $id = $rec['data']['id'] ?? null;
            if ($id !== null) {
                if ($type === 'tour') {
                    $recTourIds[] = $id;
                } elseif ($type === 'location') {
                    $recLocationIds[] = $id;
                } elseif ($type === 'blog') {
                    $recBlogIds[] = $id;
                }
            }
        }

        // 2. Fetch/hydrate the models for recommendations to build primary context
        $recContextMap = [];

        if (! empty($recTourIds)) {
            $tours = Tour::query()->whereIn('id', $recTourIds)->get();
            $tourContexts = $this->tourContext($tours);
            foreach ($tourContexts as $ctx) {
                $recContextMap["tour:{$ctx['id']}"] = $ctx;
            }
        }

        if (! empty($recLocationIds)) {
            $locations = Location::query()->whereIn('id', $recLocationIds)->get();
            $locationContexts = $this->locationContext($locations);
            foreach ($locationContexts as $ctx) {
                $recContextMap["location:{$ctx['id']}"] = $ctx;
            }
        }

        if (! empty($recBlogIds)) {
            $blogs = BlogPost::query()->whereIn('id', $recBlogIds)->get();
            $blogContexts = $this->blogContext($blogs);
            foreach ($blogContexts as $ctx) {
                $recContextMap["blog:{$ctx['id']}"] = $ctx;
            }
        }

        // Add recommendations to primary context in order
        foreach ($recommendations as $rec) {
            $key = "{$rec['type']}:".($rec['data']['id'] ?? '');
            if (isset($recContextMap[$key])) {
                $primaryContext->push($recContextMap[$key]);
            }
        }

        // 3. Build secondary context
        $secondaryContext = collect();

        // Policies have high priority
        $policies = $this->policyContext($intent);
        $secondaryContext = $secondaryContext->merge($policies);

        // Vector results
        $vectorCtx = $this->vectorContext($vectorResults);
        $secondaryContext = $secondaryContext->merge($vectorCtx);

        // Other SQL results (excluding recommendations)
        $otherTours = collect($sqlResults['tours'] ?? [])
            ->reject(fn ($t) => in_array($t->id, $recTourIds))
            ->take(5);
        if ($otherTours->isNotEmpty()) {
            $secondaryContext = $secondaryContext->merge($this->tourContext($otherTours));
        }

        $otherLocations = collect($sqlResults['locations'] ?? [])
            ->reject(fn ($l) => in_array($l->id, $recLocationIds))
            ->take(5);
        if ($otherLocations->isNotEmpty()) {
            $secondaryContext = $secondaryContext->merge($this->locationContext($otherLocations));
        }

        $otherBlogs = collect($sqlResults['blogs'] ?? [])
            ->reject(fn ($b) => in_array($b->id, $recBlogIds))
            ->take(5);
        if ($otherBlogs->isNotEmpty()) {
            $secondaryContext = $secondaryContext->merge($this->blogContext($otherBlogs));
        }

        // 4. Merge primary and secondary, deduplicate, and take up to limit
        return collect()
            ->merge($primaryContext)
            ->merge($secondaryContext)
            ->unique(fn (array $item): string => (string) ($item['type'] ?? '').':'.(string) ($item['id'] ?? $item['slug'] ?? md5((string) ($item['content'] ?? ''))))
            ->take($limit)
            ->values()
            ->all();
    }
}
