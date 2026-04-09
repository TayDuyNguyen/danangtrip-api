<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\PopularSearchRequest;
use App\Http\Requests\Search\PopularWithFiltersSearchRequest;
use App\Http\Requests\Search\SearchSearchRequest;
use App\Http\Requests\Search\SuggestionsSearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

/**
 * Class SearchController
 * Handles API requests for search.
 * (Xử lý các yêu cầu API cho tìm kiếm)
 */
final class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search locations by keyword and filters.
     * (Tìm kiếm địa điểm theo từ khóa và bộ lọc)
     */
    public function search(SearchSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->validated(), $request);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get search suggestions (autocomplete).
     * (Lấy gợi ý tìm kiếm (autocomplete))
     */
    public function suggestions(SuggestionsSearchRequest $request): JsonResponse
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
     * Get popular search queries with filters.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến có bộ lọc)
     */
    public function popularWithFilters(PopularWithFiltersSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->popularWithFilters($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
