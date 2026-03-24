<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\LocationValidation;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * Display a listing of the resource.
     * (Danh sách địa điểm (filter, sort, paginate))
     */
    public function index(Request $request): JsonResponse
    {
        $validator = LocationValidation::validateIndex($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

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
    public function featured(Request $request): JsonResponse
    {
        $validator = LocationValidation::validateFeatured($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->getFeaturedLocations($request->limit);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of the resource.
     * (Địa điểm gần vị trí hiện tại (lat, lng, radius))
     */
    public function nearby(Request $request): JsonResponse
    {
        $validator = LocationValidation::validateNearby($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->getNearbyLocations($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of the resource.
     * (Danh sách đánh giá của địa điểm)
     */
    public function ratings(int $id, Request $request): JsonResponse
    {
        $validator = LocationValidation::validateRatings($id, $request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->getLocationRatings($id, $request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Record a view for a location.
     * (Ghi lại lượt xem cho một địa điểm)
     */
    public function recordView(int $id, Request $request): JsonResponse
    {
        $validator = LocationValidation::validateRecordView($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $sessionId = $request->header('X-Session-Id') ?? $request->ip();
        $userId = $request->user()?->id;

        $result = $this->locationService->recordView($id, $sessionId, $userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
