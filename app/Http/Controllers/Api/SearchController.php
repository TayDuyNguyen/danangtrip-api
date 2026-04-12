<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\PopularSearchRequest;
use App\Http\Requests\Search\RecommendationSearchRequest;
use App\Http\Requests\Search\SearchSearchRequest;
use App\Http\Requests\Search\SuggestionSearchRequest;
use App\Http\Requests\Search\TrendingSearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

/**
 * Class SearchController
 * Handles search-related API requests.
 * (Xử lý các yêu cầu API liên quan đến tìm kiếm)
 */
final class SearchController extends Controller
{
    /**
     * SearchController constructor.
     * (Khởi tạo SearchController)
     */
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search locations or tours.
     * (Tìm kiếm địa điểm hoặc tour)
     */
    public function index(SearchSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->validated(), $request);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get autocomplete suggestions.
     * (Lấy gợi ý tự động hoàn thành)
     */
    public function suggestions(SuggestionSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->suggestions($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     */
    public function popular(PopularSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->popular($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get trending search queries.
     * (Lấy danh sách từ khóa tìm kiếm xu hướng)
     */
    public function trending(TrendingSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->trending($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get recommendations based on user history.
     * (Lấy gợi ý dựa trên lịch sử của người dùng)
     */
    public function recommendations(RecommendationSearchRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->searchService->recommendations($userId, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
