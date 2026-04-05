<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\LocationValidation;
use App\Services\LocationService;
use App\Traits\CsvExportable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LocationController extends Controller
{
    use CsvExportable;

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

    /**
     * Export locations to CSV.
     * (Xuất danh sách địa điểm ra file CSV)
     */
    public function export(): StreamedResponse|JsonResponse
    {
        $result = $this->locationService->getExportData();

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        return $this->exportToCsv($result['data'], 'locations');
    }

    /**
     * Attach tags to a location.
     * (Gán tags cho địa điểm)
     */
    public function attachTags(Request $request, int $id): JsonResponse
    {
        $validator = LocationValidation::validateAttachTags($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->attachTags($id, $request->tag_ids);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Detach a specific tag from a location.
     * (Xóa tag khỏi địa điểm)
     */
    public function detachTag(int $id, int $tagId): JsonResponse
    {
        $validator = LocationValidation::validateDetachTag($id, $tagId);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->detachTag($id, $tagId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Attach amenities to a location.
     * (Gán tiện ích cho địa điểm)
     */
    public function attachAmenities(Request $request, int $id): JsonResponse
    {
        $validator = LocationValidation::validateAttachAmenities($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->attachAmenities($id, $request->amenity_ids);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Detach a specific amenity from a location.
     * (Xóa tiện ích khỏi địa điểm)
     */
    public function detachAmenity(int $id, int $amenityId): JsonResponse
    {
        $validator = LocationValidation::validateDetachAmenity($id, $amenityId);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->locationService->detachAmenity($id, $amenityId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
