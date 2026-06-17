<?php

namespace App\Services\Chat;

use App\Models\BlogPost;
use App\Models\ChatKnowledgeBase;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ChatKnowledgeSyncService
{
    /**
     * Đồng bộ hóa toàn bộ cơ sở tri thức (tours, locations, blogs, policies) vào bảng tri thức chatbot.
     *
     * @return array{tour:int,location:int,blog:int,policy:int} Số lượng bản ghi đồng bộ thành công của mỗi loại
     */
    public function syncAll(): array
    {
        ChatKnowledgeBase::query()->update(['is_active' => false]);

        return [
            'tour' => $this->syncTours(),
            'location' => $this->syncLocations(),
            'blog' => $this->syncBlogPosts(),
            'policy' => $this->syncPolicies(),
        ];
    }

    /**
     * Đồng bộ hóa một Eloquent Model cụ thể vào cơ sở tri thức chatbot.
     *
     * @param Model $model Đối tượng Eloquent model cần đồng bộ (Tour, Location, BlogPost)
     * @return ChatKnowledgeBase|null Bản ghi cơ sở tri thức sau khi đồng bộ, hoặc null nếu không hỗ trợ
     */
    public function syncModel(Model $model): ?ChatKnowledgeBase
    {
        return match (true) {
            $model instanceof Tour => $this->syncTour($model),
            $model instanceof Location => $this->syncLocation($model),
            $model instanceof BlogPost => $this->syncBlogPost($model),
            default => null,
        };
    }

    /**
     * Đồng bộ hóa toàn bộ các tour đang hoạt động vào cơ sở tri thức.
     *
     * @return int Số lượng tour được đồng bộ
     */
    private function syncTours(): int
    {
        $count = 0;

        Tour::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($tours) use (&$count): void {
                foreach ($tours as $tour) {
                    $this->syncTour($tour);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Đồng bộ hóa toàn bộ các địa điểm đang hoạt động vào cơ sở tri thức.
     *
     * @return int Số lượng địa điểm được đồng bộ
     */
    private function syncLocations(): int
    {
        $count = 0;

        Location::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($locations) use (&$count): void {
                foreach ($locations as $location) {
                    $this->syncLocation($location);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Đồng bộ hóa toàn bộ các bài viết đã xuất bản vào cơ sở tri thức.
     *
     * @return int Số lượng bài viết được đồng bộ
     */
    private function syncBlogPosts(): int
    {
        $count = 0;

        BlogPost::query()
            ->where('status', 'published')
            ->orderBy('id')
            ->chunkById(100, function ($posts) use (&$count): void {
                foreach ($posts as $post) {
                    $this->syncBlogPost($post);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Đồng bộ hóa một tour cụ thể vào cơ sở tri thức.
     *
     * @param Tour $tour Đối tượng tour cần đồng bộ
     * @return ChatKnowledgeBase Bản ghi cơ sở tri thức của tour
     */
    private function syncTour(Tour $tour): ChatKnowledgeBase
    {
        return $this->upsertKnowledge(
            ['type' => 'tour', 'reference_id' => $tour->id],
            [
                'type' => 'tour',
                'title' => $tour->name,
                'content' => $this->cleanText(implode("\n", array_filter([
                    "Tên tour: {$tour->name}",
                    $tour->short_desc,
                    $tour->description,
                    $tour->duration ? "Thời lượng: {$tour->duration}" : null,
                    $tour->meeting_point ? "Điểm đón: {$tour->meeting_point}" : null,
                    'Giá người lớn: '.number_format((float) $tour->price_adult, 0, ',', '.').' VND',
                    $this->jsonText('Lịch trình', $tour->itinerary),
                    $this->jsonText('Bao gồm', $tour->inclusions),
                    $this->jsonText('Không bao gồm', $tour->exclusions),
                ]))),
                'reference_id' => $tour->id,
                'reference_slug' => $tour->slug,
                'metadata' => [
                    'price_adult' => (float) $tour->price_adult,
                    'duration' => $tour->duration,
                    'booking_availability' => $tour->booking_availability instanceof \BackedEnum
                        ? $tour->booking_availability->value
                        : $tour->booking_availability,
                    'rating_avg' => (float) ($tour->rating_avg ?? 0),
                    'booking_count' => (int) ($tour->booking_count ?? 0),
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Đồng bộ hóa một địa điểm cụ thể vào cơ sở tri thức.
     *
     * @param Location $location Đối tượng địa điểm cần đồng bộ
     * @return ChatKnowledgeBase Bản ghi cơ sở tri thức của địa điểm
     */
    private function syncLocation(Location $location): ChatKnowledgeBase
    {
        return $this->upsertKnowledge(
            ['type' => 'location', 'reference_id' => $location->id],
            [
                'type' => 'location',
                'title' => $location->name,
                'content' => $this->cleanText(implode("\n", array_filter([
                    "Địa điểm: {$location->name}",
                    $location->short_description,
                    $location->description,
                    $location->address ? "Địa chỉ: {$location->address}" : null,
                    $location->district ? "Khu vực: {$location->district}" : null,
                    $location->price_min ? 'Giá từ: '.number_format((float) $location->price_min, 0, ',', '.').' VND' : null,
                    $location->opening_hours ? 'Giờ mở cửa: '.$this->valueToText($location->opening_hours) : null,
                ]))),
                'reference_id' => $location->id,
                'reference_slug' => $location->slug,
                'metadata' => [
                    'category_id' => $location->category_id,
                    'district' => $location->district,
                    'price_min' => $location->price_min ? (float) $location->price_min : null,
                    'price_max' => $location->price_max ? (float) $location->price_max : null,
                    'avg_rating' => (float) $location->avg_rating,
                    'review_count' => (int) $location->review_count,
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Đồng bộ hóa một bài viết cụ thể vào cơ sở tri thức.
     *
     * @param BlogPost $post Đối tượng bài viết cần đồng bộ
     * @return ChatKnowledgeBase Bản ghi cơ sở tri thức của bài viết
     */
    private function syncBlogPost(BlogPost $post): ChatKnowledgeBase
    {
        return $this->upsertKnowledge(
            ['type' => 'blog', 'reference_id' => $post->id],
            [
                'type' => 'blog',
                'title' => $post->title,
                'content' => $this->cleanText(implode("\n", array_filter([
                    "Bài viết: {$post->title}",
                    $post->excerpt,
                    Str::limit(strip_tags((string) $post->content), 3000),
                ]))),
                'reference_id' => $post->id,
                'reference_slug' => $post->slug,
                'metadata' => [
                    'published_at' => optional($post->published_at)->toDateTimeString(),
                    'view_count' => (int) $post->view_count,
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Đồng bộ hóa các chính sách chung vào cơ sở tri thức.
     *
     * @return int Số lượng chính sách được đồng bộ
     */
    public function syncPolicies(): int
    {
        $policies = [
            [
                'slug' => 'payment',
                'title' => 'Chính sách thanh toán DanangTrip',
                'content' => implode(' ', [
                    'DanangTrip hỗ trợ thanh toán trực tuyến qua hình thức chuyển khoản ngân hàng quét mã QR tự động.',
                    'Khách hàng sử dụng ứng dụng ngân hàng quét mã QR hiển thị tại trang thanh toán để thực hiện giao dịch chuyển khoản với số tiền và nội dung chuyển khoản được nhập tự động (chính là mã đơn hàng).',
                    'Quý khách lưu ý không tự ý thay đổi số tiền hoặc nội dung chuyển khoản để tránh lỗi hệ thống.',
                    'Khi giao dịch thành công, hệ thống tự động ghi nhận thanh toán, chuyển trạng thái đơn hàng sang đã thanh toán và gửi email xác nhận đặt tour thành công trong vòng 1 phút.',
                ]),
            ],
            [
                'slug' => 'refund',
                'title' => 'Chính sách hủy tour và hoàn tiền DanangTrip',
                'content' => implode(' ', [
                    'Thời hạn và chính sách hoàn tiền khi hủy tour tại DanangTrip như sau:',
                    '- Hủy trước ngày khởi hành từ 7 ngày trở lên: Hoàn trả 100% số tiền đã thanh toán.',
                    '- Hủy trước ngày khởi hành từ 3 đến 6 ngày: Phí hủy là 50% tổng giá trị đơn hàng, hoàn trả 50% số tiền còn lại.',
                    '- Hủy trong vòng 48 giờ trước ngày khởi hành hoặc vắng mặt vào ngày khởi hành: Phí hủy là 100% tổng giá trị đơn hàng, không áp dụng hoàn tiền.',
                    'Đối với các tour vào ngày Lễ, Tết hoặc mùa cao điểm, quy định hoàn tiền chi tiết sẽ hiển thị cụ thể trên giao diện đặt tour.',
                    'Khách hàng thực hiện hủy đơn tại mục Lịch sử đặt tour hoặc liên hệ bộ phận hỗ trợ chăm sóc khách hàng để được xử lý hoàn tiền chuyển khoản.',
                ]),
            ],
            [
                'slug' => 'account',
                'title' => 'Tài khoản người dùng DanangTrip',
                'content' => implode(' ', [
                    'Người dùng đăng ký tài khoản DanangTrip bằng tên, email và mật khẩu hoặc đăng nhập nhanh bằng tài khoản Google.',
                    'Sau khi đăng nhập, quý khách có thể cập nhật thông tin cá nhân bao gồm số điện thoại, ảnh đại diện, ngày sinh và mật khẩu tại trang cài đặt hồ sơ.',
                    'Mục Lịch sử đặt tour cho phép khách hàng theo dõi chi tiết hành trình, tải hóa đơn thanh toán và kiểm tra trạng thái đơn hàng (chờ thanh toán, đã thanh toán, hoàn thành hoặc đã hủy).',
                    'Quý khách cũng có thể viết đánh giá, chấm điểm từ 1 đến 5 sao và đính kèm hình ảnh thực tế cho các tour hoặc địa điểm đã trải nghiệm sau khi được quản trị viên duyệt hiển thị.',
                ]),
            ],
            [
                'slug' => 'loyalty-points',
                'title' => 'Điểm thưởng và voucher DanangTrip',
                'content' => implode(' ', [
                    'Hệ thống tích điểm thành viên DanangTrip áp dụng quy định cụ thể như sau:',
                    '- Tặng 10 điểm khi đặt và hoàn thành chuyến đi thành công.',
                    '- Tặng 5 điểm cho mỗi bài đánh giá tour hoặc địa điểm được quản trị viên duyệt hiển thị.',
                    '- Tặng thêm 3 điểm (tổng cộng 8 điểm) cho bài đánh giá được duyệt có kèm hình ảnh thực tế.',
                    '- Tặng 1 điểm cho mỗi lượt người dùng khác đánh dấu đánh giá của bạn là Hữu ích (tối đa 10 điểm mỗi ngày). Thành viên không tự đánh dấu hữu ích cho chính mình.',
                    '- Tặng thêm 5 điểm khi bài đánh giá đạt mốc 5 lượt hữu ích và 10 điểm khi đạt mốc 10 lượt hữu ích (điểm thưởng mốc này không tính vào hạn mức ngày).',
                    'Đánh giá có nội dung ngắn, quảng cáo hoặc spam sẽ bị từ chối và không được cộng điểm.',
                    'Quý khách có thể sử dụng điểm tích lũy để đổi thành các mã giảm giá thanh toán tour tại mục Ví điểm.',
                ]),
            ],
            [
                'slug' => 'contact',
                'title' => 'Thông tin hỗ trợ DanangTrip',
                'content' => $this->supportPolicyContent(),
            ],
        ];

        foreach ($policies as $policy) {
            $this->upsertKnowledge(
                ['type' => 'policy', 'reference_slug' => $policy['slug']],
                [
                    'type' => 'policy',
                    'title' => $policy['title'],
                    'content' => $this->cleanText($policy['content']),
                    'reference_id' => null,
                    'reference_slug' => $policy['slug'],
                    'metadata' => ['slug' => $policy['slug']],
                    'is_active' => true,
                ]
            );
        }

        return count($policies);
    }

    /**
     * Thực hiện thêm mới hoặc cập nhật thông tin tri thức vào cơ sở dữ liệu.
     * Tự động xóa embedding nếu nội dung thay đổi.
     *
     * @param array<string,mixed> $identity Khóa định danh của bản ghi (type, reference_id...)
     * @param array<string,mixed> $payload Dữ liệu cập nhật
     * @return ChatKnowledgeBase
     */
    private function upsertKnowledge(array $identity, array $payload): ChatKnowledgeBase
    {
        $hash = $this->contentHash($payload);
        $knowledge = ChatKnowledgeBase::query()->firstOrNew($identity);
        $contentChanged = $knowledge->content_hash !== $hash;

        $knowledge->fill($payload);
        $knowledge->content_hash = $hash;

        if ($contentChanged) {
            $knowledge->embedding = null;
            $knowledge->embedding_model = null;
            $knowledge->embedding_dimension = null;
            $knowledge->last_embedded_at = null;
        }

        $knowledge->save();

        return $knowledge;
    }

    /**
     * Tạo mã hash SHA-256 từ nội dung bản ghi để phát hiện thay đổi.
     *
     * @param array<string,mixed> $payload Dữ liệu của bản ghi tri thức
     * @return string Chuỗi hash SHA-256
     */
    private function contentHash(array $payload): string
    {
        return hash('sha256', json_encode([
            'type' => $payload['type'] ?? null,
            'title' => $payload['title'] ?? null,
            'content' => $payload['content'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Trích xuất thông tin liên hệ và giờ làm việc từ cài đặt hệ thống để làm nội dung chính sách hỗ trợ.
     *
     * @return string Văn bản mô tả chính sách hỗ trợ liên hệ
     */
    private function supportPolicyContent(): string
    {
        $hotline = Setting::query()->where('key', 'general.hotline')->value('value') ?: '1900 1800';
        $email = Setting::query()->where('key', 'general.email')->value('value') ?: 'info@danangtrip.com';
        $hours = Setting::query()->where('key', 'general.support_hours')->value('value') ?: '08:00 - 22:00';

        return "Khách hàng có thể gửi yêu cầu liên hệ qua biểu mẫu Liên hệ trực tuyến trên website. Hotline hỗ trợ khẩn cấp: {$hotline}. Email tiếp nhận phản hồi: {$email}. Thời gian hỗ trợ: từ {$hours} tất cả các ngày trong tuần (bao gồm cả ngày lễ, Tết).";
    }

    /**
     * Định dạng dữ liệu thành chuỗi có nhãn (ví dụ: "Lịch trình: ...").
     *
     * @param string $label Nhãn của thông tin
     * @param mixed $value Giá trị thông tin cần chuyển đổi
     * @return string|null Chuỗi thông tin đã định dạng hoặc null
     */
    private function jsonText(string $label, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $label.': '.$this->valueToText($value);
    }

    /**
     * Chuyển đổi các kiểu dữ liệu phức tạp (mảng, object) thành chuỗi văn bản phẳng.
     *
     * @param mixed $value Giá trị cần chuyển đổi
     * @return string Chuỗi văn bản phẳng
     */
    private function valueToText(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(function (mixed $item): string {
                    if (is_array($item)) {
                        return implode(' - ', array_filter(array_map(
                            fn (mixed $part): string => is_scalar($part) ? (string) $part : json_encode($part, JSON_UNESCAPED_UNICODE),
                            $item
                        )));
                    }

                    return is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE);
                })
                ->filter()
                ->implode('; ');
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Làm sạch văn bản: giải mã thực thể HTML, loại bỏ thẻ HTML và khoảng trắng dư thừa.
     *
     * @param string $text Văn bản thô
     * @return string Văn bản đã được làm sạch
     */
    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return is_string($text) ? $text : '';
    }
}
