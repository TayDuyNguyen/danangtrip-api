<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\TourScheduleValidation;
use App\Services\TourScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request): JsonResponse
    {
        $validator = TourScheduleValidation::validateIndex($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourScheduleService->getSchedules($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified tour schedule.
     * (Chi tiết lịch khởi hành tour)
     */
    public function show(int $id): JsonResponse
    {
        $validator = TourScheduleValidation::validateShow($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

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
    public function store(int $id, Request $request): JsonResponse
    {
        $validator = TourScheduleValidation::validateStore($id, $request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $data = $validator->validated();
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
    public function update(int $id, Request $request): JsonResponse
    {
        $validator = TourScheduleValidation::validateUpdate($id, $request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourScheduleService->updateSchedule($id, $validator->validated());

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
    public function destroy(int $id): JsonResponse
    {
        $validator = TourScheduleValidation::validateShow($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

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
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = TourScheduleValidation::validateUpdateStatus($id, $request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourScheduleService->updateStatus($id, $request->status);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
