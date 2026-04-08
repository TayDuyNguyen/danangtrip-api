<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Amenity\IndexAmenityRequest;
use App\Services\AmenityService;
use Illuminate\Http\JsonResponse;

/**
 * Class AmenityController
 * (Điều khiển các hoạt động cho Tiện ích)
 */
final class AmenityController extends Controller
{
    /**
     * AmenityController constructor.
     */
    public function __construct(
        protected AmenityService $amenityService
    ) {}

    /**
     * Display a listing of all amenities.
     * (Hiển thị danh sách tất cả tiện ích)
     */
    public function index(IndexAmenityRequest $request): JsonResponse
    {
        $result = $this->amenityService->getAllAmenities($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
