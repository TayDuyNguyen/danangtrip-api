<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Validations\SearchValidation;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function search(Request $request): JsonResponse
    {
        $validator = SearchValidation::validateSearch($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->searchService->search($validator->validated(), $request);

        return $result['status'] === 200
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get search suggestions (autocomplete).
     * (Lấy gợi ý tìm kiếm (autocomplete))
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validator = SearchValidation::validateSuggestions($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->searchService->suggestions($validator->validated());

        return $result['status'] === 200
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     */
    public function popular(Request $request): JsonResponse
    {
        $validator = SearchValidation::validatePopular($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->searchService->popular($validator->validated());

        return $result['status'] === 200
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
