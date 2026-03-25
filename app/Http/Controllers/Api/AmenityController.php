<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\AmenityValidation;
use App\Services\AmenityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request): JsonResponse
    {
        $validator = AmenityValidation::validateIndex($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->amenityService->getAllAmenities($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
