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
    /** @return array{tour:int,location:int,blog:int,policy:int} */
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

    public function syncModel(Model $model): ?ChatKnowledgeBase
    {
        return match (true) {
            $model instanceof Tour => $this->syncTour($model),
            $model instanceof Location => $this->syncLocation($model),
            $model instanceof BlogPost => $this->syncBlogPost($model),
            default => null,
        };
    }

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

    public function syncPolicies(): int
    {
        $policies = [
            [
                'slug' => 'payment',
                'title' => 'Chính sách thanh toán DanangTrip',
                'content' => 'DanangTrip hỗ trợ thanh toán bằng QR chuyển khoản SePay. Sau khi khách chuyển khoản đúng số tiền và đúng nội dung, hệ thống sẽ tự xác nhận đơn khi nhận IPN.',
            ],
            [
                'slug' => 'refund',
                'title' => 'Chính sách hủy tour và hoàn tiền DanangTrip',
                'content' => 'Chính sách hủy tour và hoàn tiền phụ thuộc thời điểm hủy, điều kiện tour và trạng thái thanh toán. Khách nên kiểm tra chính sách trên màn đặt tour hoặc liên hệ hỗ trợ trước khi hủy.',
            ],
            [
                'slug' => 'account',
                'title' => 'Tài khoản người dùng DanangTrip',
                'content' => 'Người dùng có thể đăng ký, đăng nhập, cập nhật hồ sơ, đổi mật khẩu, xem lịch sử đặt tour và quản lý đánh giá trong tài khoản DanangTrip.',
            ],
            [
                'slug' => 'loyalty-points',
                'title' => 'Điểm thưởng và voucher DanangTrip',
                'content' => implode(' ', [
                    'DanangTrip có hệ thống điểm thưởng dành cho người dùng đã đăng ký.',
                    'Người dùng được cộng 10 điểm khi thanh toán đơn tour thành công.',
                    'Người dùng được cộng 5 điểm khi đánh giá tour hoặc địa điểm được duyệt.',
                    'Nếu đánh giá được duyệt có ít nhất một ảnh đính kèm đã lưu thành công, người dùng được cộng thêm 3 điểm. Hệ thống hiện chỉ kiểm tra ảnh đính kèm, chưa tự động xác minh ảnh có phải ảnh thật hay không.',
                    'Khi một người dùng khác đánh dấu đánh giá đã duyệt là hữu ích, chủ đánh giá được cộng 1 điểm cho mỗi lượt hợp lệ. Mỗi người chỉ được đánh dấu một lần cho một đánh giá và không được tự đánh dấu đánh giá của mình.',
                    'Điểm nhận từ lượt hữu ích được giới hạn tối đa 10 điểm mỗi ngày cho mỗi chủ đánh giá. Lượt hữu ích vẫn được ghi nhận sau khi đạt giới hạn nhưng không cộng thêm điểm trong ngày đó.',
                    'Mỗi đánh giá được thưởng thêm một lần 5 điểm khi đạt đúng mốc 5 lượt hữu ích và một lần 10 điểm khi đạt đúng mốc 10 lượt hữu ích. Điểm thưởng mốc được tính riêng với giới hạn điểm hữu ích hằng ngày.',
                    'Đánh giá chỉ được nhận điểm sau khi quản trị viên duyệt. Nội dung bị từ chối không được cộng điểm; hệ thống hiện chưa tự động chấm toàn bộ nội dung spam, quá ngắn hoặc trùng lặp bằng AI.',
                    'Điểm thưởng có thể đổi thành voucher giảm giá tour trong trang Ví điểm.',
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

    /** @param array<string,mixed> $identity @param array<string,mixed> $payload */
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

    /** @param array<string,mixed> $payload */
    private function contentHash(array $payload): string
    {
        return hash('sha256', json_encode([
            'type' => $payload['type'] ?? null,
            'title' => $payload['title'] ?? null,
            'content' => $payload['content'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function supportPolicyContent(): string
    {
        $hotline = Setting::query()->where('key', 'general.hotline')->value('value') ?: '1900 1800';
        $email = Setting::query()->where('key', 'general.email')->value('value') ?: 'info@danangtrip.com';
        $hours = Setting::query()->where('key', 'general.support_hours')->value('value') ?: '08:00 - 22:00';

        return "Khách hàng có thể gửi yêu cầu liên hệ qua form Liên hệ. Hotline: {$hotline}. Email: {$email}. Thời gian hỗ trợ: {$hours}.";
    }

    private function jsonText(string $label, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $label.': '.$this->valueToText($value);
    }

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

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return is_string($text) ? $text : '';
    }
}
