<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tour\CheckAvailabilityTourRequest;
use App\Http\Requests\Tour\FeaturedTourRequest;
use App\Http\Requests\Tour\HotTourRequest;
use App\Http\Requests\Tour\IndexTourRequest;
use App\Http\Requests\Tour\RatingsTourRequest;
use App\Http\Requests\Tour\ShowTourRequest;
use App\Services\TourService;
use Illuminate\Http\JsonResponse;

/**
 * Class TourController
 * Handles public API requests for tours.
 * (Xử lý các yêu cầu API công khai cho tour)
 */
final class TourController extends Controller
{
    /**
     * TourController constructor.
     * (Khởi tạo TourController)
     */
    public function __construct(
        protected TourService $tourService
    ) {}

    /**
     * Display a listing of tours.
     * (Danh sách tour (filter, sort, paginate))
     */
    public function index(IndexTourRequest $request): JsonResponse
    {
        $result = $this->tourService->getTours($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display featured tours.
     * (Danh sách tour nổi bật)
     */
    public function featured(FeaturedTourRequest $request): JsonResponse
    {
        $result = $this->tourService->getFeaturedTours($request->limit);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display hot tours.
     * (Danh sách tour hot)
     */
    public function hot(HotTourRequest $request): JsonResponse
    {
        $result = $this->tourService->getHotTours($request->limit);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified tour by slug.
     * (Chi tiết tour theo slug)
     */
    public function show(string $slug): JsonResponse
    {
        $result = $this->tourService->getTourBySlug($slug);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display schedules for a tour.
     * (Lịch khởi hành của tour)
     */
    public function schedules(ShowTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->getSchedules($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display ratings for a tour.
     * (Đánh giá của tour)
     */
    public function ratings(RatingsTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->getRatings($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display rating statistics for a tour.
     * (Phân bố số sao của tour)
     */
    public function ratingStats(ShowTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->getRatingStats($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Check availability for a specific date.
     * (Kiểm tra còn chỗ cho ngày cụ thể)
     */
    public function checkAvailability(CheckAvailabilityTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->checkAvailability($id, $request->date);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
