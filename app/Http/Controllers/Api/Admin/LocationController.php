<?php

namespace App\Http\Controllers\Api\Admin;

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
     * Store a new location.
     * (Tạo địa điểm mới)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = LocationValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $data = $validator->validated();
        $data['created_by'] = auth('api')->id();

        $result = $this->locationService->createLocation($data);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['location' => $result['data']], 'Location created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing location.
     * (Cập nhật địa điểm)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = LocationValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->updateLocation($id, $validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['location' => $result['data']], 'Location updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a location.
     * (Xóa địa điểm)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = LocationValidation::validateDestroy($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->deleteLocation($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update location status.
     * (Cập nhật trạng thái địa điểm)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = LocationValidation::validatePatchStatus($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->updateLocation($id, ['status' => $request->status]);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Status updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle location featured status.
     * (Cập nhật trạng thái nổi bật của địa điểm)
     */
    public function toggleFeatured(Request $request, int $id): JsonResponse
    {
        $validator = LocationValidation::validatePatchFeatured($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->updateLocation($id, ['is_featured' => $request->is_featured]);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Featured status toggled successfully')
            : $this->error($result['message'], $result['status']);
    }
}
