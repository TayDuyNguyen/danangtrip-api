<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\BookingValidation;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponser;

    public function __construct(protected BookingService $bookingService)
    {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $validation = BookingValidation::validateIndex($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->getBookings($validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function export(Request $request): JsonResponse
    {
        $validation = BookingValidation::validateIndex($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->getBookings($validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'], 'Export data retrieved successfully. Excel file generation is not yet implemented.');
        }

        return $this->error($result['message'], $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validation = BookingValidation::validateUpdateStatus($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->updateBookingStatus($id, $validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function confirm(int $id): JsonResponse
    {
        $result = $this->bookingService->confirmBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function adminCancel(Request $request, int $id): JsonResponse
    {
        $validation = BookingValidation::validateCancel($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->cancelBookingAdmin($id, $validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function complete(int $id): JsonResponse
    {
        $result = $this->bookingService->completeBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
