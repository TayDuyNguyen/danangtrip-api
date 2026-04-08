<?php

namespace App\Http\Controllers\Api;

use App\Enums\Constants;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Location\FeaturedLocationRequest;
use App\Http\Requests\Location\IndexLocationRequest;
use App\Http\Requests\Location\NearbyByIdLocationRequest;
use App\Http\Requests\Location\NearbyLocationRequest;
use App\Http\Requests\Location\RatingsLocationRequest;
use App\Http\Requests\Location\RatingStatsLocationRequest;
use App\Http\Requests\Location\RecordViewLocationRequest;
use App\Http\Requests\Location\ShowLocationRequest;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

final class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * Display a listing of the resource.
     * (Danh sách địa điểm (filter, sort, paginate))
     */
    public function index(IndexLocationRequest $request): JsonResponse
    {
        $result = $this->locationService->getLocations($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified resource.
     * (Chi tiết địa điểm theo slug)
     */
    public function show(string $slug): JsonResponse
    {
        $result = $this->locationService->getLocationBySlug($slug);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of the resource.
     * (Danh sách địa điểm nổi bật)
     */
    public function featured(FeaturedLocationRequest $request): JsonResponse
    {
        $result = $this->locationService->getFeaturedLocations($request->limit);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of the resource.
     * (Địa điểm gần vị trí hiện tại (lat, lng, radius))
     */
    public function nearby(NearbyLocationRequest $request): JsonResponse
    {
        $result = $this->locationService->getNearbyLocations($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of the resource.
     * (Danh sách đánh giá của địa điểm)
     */
    public function ratings(RatingsLocationRequest $request, int $id): JsonResponse
    {
        $result = $this->locationService->getLocationRatings($id, $request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Record a view for a location.
     * (Ghi lại lượt xem cho một địa điểm)
     */
    public function recordView(RecordViewLocationRequest $request, int $id): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->ip();
        $userId = $request->user()?->id;

        $result = $this->locationService->recordView($id, $sessionId, $userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get unique districts that have active locations.
     * (Danh sách quận có địa điểm (dynamic))
     */
    public function districts(): JsonResponse
    {
        $result = $this->locationService->getDistrictsWithLocations();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get images for a location.
     * (Danh sách ảnh của địa điểm)
     */
    public function images(ShowLocationRequest $request, int $id): JsonResponse
    {
        $result = $this->locationService->getLocationImages($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get rating statistics for a location.
     * (Phân bố số sao (5 sao:12, 4 sao:8...))
     */
    public function ratingStats(RatingStatsLocationRequest $request, int $id): JsonResponse
    {
        $result = $this->locationService->getLocationRatingStats($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get nearby locations relative to a specific location.
     * (Địa điểm lân cận (gợi ý sau khi xem chi tiết))
     */
    public function nearbyLocations(NearbyByIdLocationRequest $request, int $id): JsonResponse
    {
        $limit = $request->limit ?? Constants::LIMIT;
        $result = $this->locationService->getNearbyLocationsByLocationId($id, $limit);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
