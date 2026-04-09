<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    use ApiResponser;

    public function __construct(protected BookingService $bookingService)
    {
        //
    }

    public function index(IndexBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->getBookings($request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function export(IndexBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->getBookings($request->validated());

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

    public function updateStatus(UpdateBookingStatusRequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->updateBookingStatus($id, $request->validated());

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

    public function adminCancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->cancelBookingAdmin($id, $request->validated());

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
