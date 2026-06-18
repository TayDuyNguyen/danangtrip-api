<?php

namespace App\Services\Chat;

use App\Models\ChatKnowledgeBase;
use App\Support\BooleanColumn;
use Illuminate\Support\Collection;

final class ChatVectorSearchService
{
    /**
     * Khởi tạo dịch vụ tìm kiếm vector.
     *
     * @param  ChatEmbeddingService  $embeddingService  Dịch vụ tạo vector nhúng
     */
    public function __construct(
        private readonly ChatEmbeddingService $embeddingService
    ) {}

    /**
     * Tìm kiếm các tài liệu tri thức tương đồng ngữ nghĩa bằng Vector.
     * Tối ưu hóa truy vấn cơ sở dữ liệu: chỉ tải id và embedding của các bản ghi,
     * tính toán độ tương đồng Cosine trên bộ nhớ PHP, lọc ra top các bản ghi tốt nhất,
     * và chỉ thực hiện nạp đầy đủ thông tin (Eloquent model hydration) cho những bản ghi được chọn.
     *
     * @param  string  $question  Câu hỏi hoặc truy vấn tìm kiếm của người dùng
     * @param  string  $intent  Ý định của người dùng (tour, location, blog...)
     * @param  int  $limit  Số lượng kết quả tối đa trả về
     * @return Collection<int,ChatKnowledgeBase>
     */
    public function search(string $question, string $intent, int $limit): Collection
    {
        // Kiểm tra xem tìm kiếm vector có được bật trong cấu hình không
        if (! (bool) config('chatbot.vector_enabled', false)) {
            return collect();
        }

        // Lấy danh sách các loại tri thức phù hợp với ý định người dùng
        $types = $this->typesForIntent($intent);
        $candidateLimit = max($limit * 4, (int) config('chatbot.vector_candidate_limit', 80));
        $minSimilarity = (float) config('chatbot.vector_min_similarity', 0.68);

        // Tạo vector nhúng cho câu hỏi
        $embedding = $this->embeddingService->embed($question, 'RETRIEVAL_QUERY');
        if (! $embedding || empty($embedding['values'])) {
            return collect();
        }

        $queryVector = $embedding['values'];

        // TỐI ƯU HÓA: Chỉ lấy id và embedding từ database để tránh tải các trường text/markdown dung lượng lớn
        $candidates = ChatKnowledgeBase::query()
            ->select(['id', 'embedding'])
            ->tap(fn ($query) => BooleanColumn::where($query, 'is_active', true))
            ->whereNotNull('embedding')
            ->when($types !== [], fn ($query) => $query->whereIn('type', $types))
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        // Tính độ tương đồng Cosine trong bộ nhớ PHP
        $scored = $candidates->map(function (ChatKnowledgeBase $item) use ($queryVector): array {
            return [
                'id' => $item->id,
                'score' => $this->cosineSimilarity($queryVector, (array) $item->embedding),
            ];
        })
            ->filter(fn (array $result): bool => $result['score'] >= $minSimilarity)
            ->sortByDesc('score')
            ->take($candidateLimit);

        if ($scored->isEmpty()) {
            return collect();
        }

        // Hydrate: Tải đầy đủ thông tin chi tiết từ DB chỉ cho các bản ghi có điểm tương đồng cao nhất
        $topIds = $scored->pluck('id')->all();
        $items = ChatKnowledgeBase::query()->whereIn('id', $topIds)->get()->keyBy('id');

        return $scored
            ->map(function (array $result) use ($items) {
                $item = $items->get($result['id']);
                if ($item) {
                    $item->similarity_score = $result['score'];
                }

                return $item;
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Phân loại các loại tri thức tương ứng với từng ý định nghiệp vụ để thu hẹp phạm vi tìm kiếm.
     * Ví dụ: ý định liên quan tới tour thì tìm trong tour/blog/policy; ý định về địa điểm tìm trong địa điểm/blog.
     *
     * @param  string  $intent  Ý định của người dùng
     * @return array<int,string> Danh sách các loại tri thức phù hợp
     */
    private function typesForIntent(string $intent): array
    {
        return match ($intent) {
            'tour', 'booking', 'schedule' => ['tour', 'blog', 'policy'],
            'location', 'food', 'hotel' => ['location', 'blog'],
            'blog' => ['blog'],
            'payment', 'refund', 'account', 'contact', 'loyalty' => ['policy', 'blog'],
            'greeting' => ['tour', 'location', 'blog', 'policy'],
            default => ['tour', 'location', 'blog', 'policy'],
        };
    }

    /**
     * Tính độ tương đồng Cosine (Cosine Similarity) giữa hai vector số thực.
     * Giá trị trả về nằm trong khoảng [-1.0, 1.0]. Điểm số càng gần 1.0 thì hai văn bản càng tương đồng ngữ nghĩa.
     *
     * @param  array<int,float|int|string>  $a  Vector thứ nhất
     * @param  array<int,float|int|string>  $b  Vector thứ hai
     * @return float Điểm tương đồng Cosine
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $left = (float) $a[$index];
            $right = (float) $b[$index];
            $dot += $left * $right;
            $normA += $left * $left;
            $normB += $right * $right;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
