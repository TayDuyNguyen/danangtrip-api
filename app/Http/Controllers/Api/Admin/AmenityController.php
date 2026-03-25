<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\AmenityValidation;
use App\Services\AmenityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AmenityController
 * (Điều khiển các hoạt động cho Tiện ích - Admin)
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
     * Store a new amenity.
     * (Tạo tiện ích mới)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = AmenityValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->amenityService->createAmenity($validator->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Remove the specified amenity.
     * (Xóa tiện ích)
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->amenityService->deleteAmenity($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
