<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatCache;
use App\Models\ChatMessage;
use App\Support\BooleanColumn;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Class ChatbotController
 * (Điều khiển quản trị hệ thống Chatbot, Semantic Cache và Analytics)
 */
final class ChatbotController extends Controller
{
    /**
     * Lấy các chỉ số thống kê (Technical & Business Analytics) của Chatbot.
     */
    public function stats(): JsonResponse
    {
        try {
            // Lấy toàn bộ message trong vòng 30 ngày qua để tổng hợp
            $thirtyDaysAgo = Carbon::now()->subDays(30);
            $messages = ChatMessage::query()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->get();

            // 1. Phân nhóm theo ngày cho Technical Analytics
            $grouped = $messages->groupBy(function ($msg) {
                return $msg->created_at->format('Y-m-d');
            })->sortKeys();

            $latencyTrend = [];
            $cacheRateTrend = [];
            $costTrend = [];
            $errorTrend = [];

            // Điền đủ 30 ngày để biểu đồ mượt mà
            for ($i = 29; $i >= 0; $i--) {
                $dateStr = Carbon::now()->subDays($i)->format('Y-m-d');
                $dayMessages = $grouped->get($dateStr, collect());

                $total = $dayMessages->count();
                $cacheHits = $dayMessages->where('cache_hit', true)->count();

                $latencies = $dayMessages->map(fn ($m) => $m->metadata['latency_ms'] ?? null)->filter()->values();
                $avgLatency = $latencies->isEmpty() ? 0 : $latencies->average();

                $tokens = $dayMessages->sum('tokens_used');
                $estimatedCost = ($tokens / 1000) * 0.00015; // Ước lượng 0.15$ / 1M tokens

                $errors = $dayMessages->filter(function ($msg) {
                    $meta = $msg->metadata ?? [];

                    return ($meta['ai_ok'] ?? true) === false || (! empty($meta['reason']) && str_contains($meta['reason'], 'failover'));
                })->count();

                $latencyTrend[] = [
                    'date' => $dateStr,
                    'latency' => round($avgLatency, 2),
                ];

                $cacheRateTrend[] = [
                    'date' => $dateStr,
                    'hitRate' => $total > 0 ? round(($cacheHits / $total) * 100, 2) : 0,
                ];

                $costTrend[] = [
                    'date' => $dateStr,
                    'cost' => round($estimatedCost, 6),
                    'tokens' => $tokens,
                ];

                $errorTrend[] = [
                    'date' => $dateStr,
                    'errors' => $errors,
                ];
            }

            // 2. Tổng quan KPI kỹ thuật hiện tại
            $totalMsgsCount = $messages->count();
            $totalCacheHitsCount = $messages->where('cache_hit', true)->count();
            $avgLatencyOverall = $messages->map(fn ($m) => $m->metadata['latency_ms'] ?? null)->filter()->average() ?? 0;
            $totalCostOverall = ($messages->sum('tokens_used') / 1000) * 0.00015;

            $systemErrorsCount = $messages->filter(function ($msg) {
                $meta = $msg->metadata ?? [];

                return ($meta['ai_ok'] ?? true) === false || (! empty($meta['reason']) && str_contains($meta['reason'], 'failover'));
            })->count();

            // 3. Phân hệ Business Analytics
            // Top Destinations
            $topDestinations = $messages->map(function ($msg) {
                return $msg->metadata['understanding']['destination'] ?? $msg->metadata['session_slots']['destination'] ?? null;
            })->filter()
                ->map(fn ($x) => mb_convert_case(trim((string) $x), MB_CASE_TITLE, 'UTF-8'))
                ->filter(fn ($x) => $x !== '')
                ->groupBy(fn ($x) => $x)
                ->map->count()
                ->sortByDesc(fn ($x) => $x)
                ->take(5)
                ->map(fn ($count, $name) => ['name' => $name, 'value' => $count])
                ->values()
                ->toArray();

            // Top Tours (lọc từ context type = tour)
            $topTours = $messages->flatMap(function ($msg) {
                $ctx = $msg->context ?? [];

                return collect($ctx)->where('type', 'tour')->pluck('title');
            })->filter()
                ->groupBy(fn ($x) => $x)
                ->map->count()
                ->sortByDesc(fn ($x) => $x)
                ->take(5)
                ->map(fn ($count, $title) => ['name' => $title, 'value' => $count])
                ->values()
                ->toArray();

            // Intent Distribution
            $intentDistribution = $messages->groupBy('intent')
                ->map(fn ($group, $intent) => [
                    'name' => match ($intent) {
                        'tour' => 'Tìm kiếm Tour 🏖',
                        'booking' => 'Đặt Tour 🛒',
                        'location' => 'Địa điểm 📍',
                        'food' => 'Ẩm thực 🍜',
                        'hotel' => 'Chỗ ở 🏨',
                        'blog' => 'Bài viết 📖',
                        'schedule' => 'Lịch trình 📅',
                        'payment' => 'Thanh toán 💳',
                        'refund' => 'Hoàn tiền 🔄',
                        'loyalty' => 'Tích điểm 🎁',
                        'greeting' => 'Chào hỏi 👋',
                        'handoff' => 'Hỗ trợ người thật 📞',
                        default => 'Không rõ 🤖'
                    },
                    'value' => $group->count(),
                ])->values()->toArray();

            // Unknown Intents list (recent 10)
            $unknownIntents = ChatMessage::query()
                ->where('intent', 'unknown')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(['id', 'question', 'created_at'])
                ->toArray();

            // Negative Feedbacks list (recent 10)
            $negativeFeedbacks = ChatMessage::query()
                ->where('metadata->rating', 'negative')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(['id', 'question', 'answer', 'intent', 'metadata', 'created_at'])
                ->toArray();

            // Clarification Completion Rate & Drop-off Analysis
            $sessions = $messages->groupBy('session_id');
            $totalClarified = 0;
            $completedClarified = 0;

            foreach ($sessions as $sessionId => $sessionMsgs) {
                $hasClarification = $sessionMsgs->contains(function ($msg) {
                    return ($msg->metadata['reason'] ?? '') === 'clarification_step_triggered';
                });

                if ($hasClarification) {
                    $totalClarified++;
                    $latestMsg = $sessionMsgs->sortByDesc('created_at')->first();
                    $latestReason = $latestMsg->metadata['reason'] ?? '';
                    if ($latestReason !== 'clarification_step_triggered' && $latestMsg->intent !== 'unknown') {
                        $completedClarified++;
                    }
                }
            }

            $clarificationRate = $totalClarified > 0 ? round(($completedClarified / $totalClarified) * 100, 2) : 100;

            $stats = [
                'kpis' => [
                    'total_messages' => $totalMsgsCount,
                    'cache_hit_rate' => $totalMsgsCount > 0 ? round(($totalCacheHitsCount / $totalMsgsCount) * 100, 2) : 0,
                    'avg_latency' => round($avgLatencyOverall, 2),
                    'total_cost' => round($totalCostOverall, 6),
                    'system_errors' => $systemErrorsCount,
                ],
                'trends' => [
                    'latency' => $latencyTrend,
                    'cacheRate' => $cacheRateTrend,
                    'cost' => $costTrend,
                    'errors' => $errorTrend,
                ],
                'business' => [
                    'topDestinations' => $topDestinations,
                    'topTours' => $topTours,
                    'intentDistribution' => $intentDistribution,
                    'unknownIntents' => $unknownIntents,
                    'negativeFeedbacks' => $negativeFeedbacks,
                    'clarification' => [
                        'total_clarified_sessions' => $totalClarified,
                        'completed_sessions' => $completedClarified,
                        'completion_rate' => $clarificationRate,
                        'drop_off_rate' => 100 - $clarificationRate,
                    ],
                ],
            ];

            return $this->success($stats, 'Chatbot analytics retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve chatbot analytics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Danh sách nhật ký hội thoại (Chat Logs) có phân trang và bộ lọc.
     */
    public function logs(Request $request): JsonResponse
    {
        try {
            $intent = $request->input('intent');
            $cacheHit = $request->input('cache_hit');
            $rating = $request->input('rating'); // 'positive' or 'negative'
            $search = $request->input('search');

            $logs = ChatMessage::query()
                ->when($intent, fn ($q) => $q->where('intent', $intent))
                ->when($cacheHit !== null, function ($q) use ($cacheHit) {
                    BooleanColumn::where(
                        $q,
                        'cache_hit',
                        filter_var($cacheHit, FILTER_VALIDATE_BOOLEAN)
                    );
                })
                ->when($rating, fn ($q) => $q->where('metadata->rating', $rating))
                ->when($search, fn ($q) => $q->where(fn ($sq) => $sq->where('question', 'like', "%{$search}%")->orWhere('answer', 'like', "%{$search}%")))
                ->orderByDesc('created_at')
                ->paginate(15);

            return $this->success($logs, 'Chat logs retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve chat logs: '.$e->getMessage(), 500);
        }
    }

    /**
     * Danh sách các câu hỏi đang được cache trong hệ thống.
     */
    public function cache(): JsonResponse
    {
        try {
            $caches = ChatCache::query()
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('created_at')
                ->get(['id', 'question_hash', 'normalized_question', 'locale', 'intent', 'provider', 'model', 'expires_at', 'created_at']);

            return $this->success($caches, 'Active caches retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to retrieve cache list: '.$e->getMessage(), 500);
        }
    }

    /**
     * Xóa một bản ghi cache theo hash.
     */
    public function deleteCache(string $hash): JsonResponse
    {
        try {
            ChatCache::query()->where('question_hash', $hash)->delete();

            return $this->success(null, 'Cache entry deleted successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to delete cache entry: '.$e->getMessage(), 500);
        }
    }

    /**
     * Xóa toàn bộ cache chatbot.
     */
    public function clearAllCache(): JsonResponse
    {
        try {
            ChatCache::query()->truncate();

            return $this->success(null, 'All chatbot caches cleared successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to clear chatbot cache: '.$e->getMessage(), 500);
        }
    }
}
