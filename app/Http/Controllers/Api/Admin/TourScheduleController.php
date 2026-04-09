<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\TourSchedule\IndexTourScheduleRequest;
use App\Http\Requests\TourSchedule\ShowTourScheduleRequest;
use App\Http\Requests\TourSchedule\StoreTourScheduleRequest;
use App\Http\Requests\TourSchedule\UpdateStatusTourScheduleRequest;
use App\Http\Requests\TourSchedule\UpdateTourScheduleRequest;
use App\Services\TourScheduleService;
use Illuminate\Http\JsonResponse;

/**
 * Class TourScheduleController
 * Handles admin API requests for tour schedule management.
 * (Xử lý các yêu cầu API admin cho quản lý lịch khởi hành tour)
 */
final class TourScheduleController extends Controller
{
    /**
     * TourScheduleController constructor.
     * (Khởi tạo TourScheduleController)
     */
    public function __construct(
        protected TourScheduleService $tourScheduleService
    ) {}

    /**
     * Display a listing of tour schedules.
     * (Danh sách lịch khởi hành tour)
     */
    public function index(IndexTourScheduleRequest $request): JsonResponse
    {
        $result = $this->tourScheduleService->getSchedules($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified tour schedule.
     * (Chi tiết lịch khởi hành tour)
     */
    public function show(ShowTourScheduleRequest $request, int $id): JsonResponse
    {
        $result = $this->tourScheduleService->getScheduleById($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Store a new tour schedule for a specific tour.
     * (Thêm lịch khởi hành cho tour)
     *
     * @param  int  $id  Tour ID
     */
    public function store(StoreTourScheduleRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['tour_id'] = $id;

        $result = $this->tourScheduleService->createSchedule($data);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], 'Tour schedule created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing tour schedule.
     * (Cập nhật lịch khởi hành tour)
     *
     * @param  int  $id  Schedule ID
     */
    public function update(UpdateTourScheduleRequest $request, int $id): JsonResponse
    {
        $result = $this->tourScheduleService->updateSchedule($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Tour schedule updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a tour schedule.
     * (Xóa lịch khởi hành tour)
     *
     * @param  int  $id  Schedule ID
     */
    public function destroy(ShowTourScheduleRequest $request, int $id): JsonResponse
    {
        $result = $this->tourScheduleService->deleteSchedule($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update tour schedule status.
     * (Cập nhật trạng thái lịch khởi hành tour)
     *
     * @param  int  $id  Schedule ID
     */
    public function updateStatus(UpdateStatusTourScheduleRequest $request, int $id): JsonResponse
    {
        $result = $this->tourScheduleService->updateStatus($id, $request->status);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
