<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\ToursExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tour\PatchStatusTourRequest;
use App\Http\Requests\Tour\ShowTourRequest;
use App\Http\Requests\Tour\StoreTourRequest;
use App\Http\Requests\Tour\UpdateTourRequest;
use App\Services\TourService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class TourController
 * Handles admin API requests for tour management.
 * (Xử lý các yêu cầu API admin cho quản lý tour)
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
     * Store a new tour.
     * (Tạo tour mới)
     */
    public function store(StoreTourRequest $request): JsonResponse
    {
        $result = $this->tourService->createTour($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['tour' => $result['data']], 'Tour created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing tour.
     * (Cập nhật tour)
     */
    public function update(UpdateTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->updateTour($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['tour' => $result['data']], 'Tour updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a tour.
     * (Xóa tour)
     */
    public function destroy(ShowTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->deleteTour($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update tour status.
     * (Đổi trạng thái tour)
     */
    public function updateStatus(PatchStatusTourRequest $request, int $id): JsonResponse
    {
        $result = $this->tourService->updateStatus($id, $request->status);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Status updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle featured status.
     * (Bật/tắt nổi bật)
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        $result = $this->tourService->toggleFeatured($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Featured status toggled successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle hot status.
     * (Bật/tắt tour hot)
     */
    public function toggleHot(int $id): JsonResponse
    {
        $result = $this->tourService->toggleHot($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Hot status toggled successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Export tours to Excel.
     * (Export danh sách tour ra Excel)
     */
    public function export(): BinaryFileResponse|JsonResponse
    {
        $result = $this->tourService->exportTours();

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        return Excel::download(new ToursExport($result['data']), 'tours.xlsx');
    }
}
