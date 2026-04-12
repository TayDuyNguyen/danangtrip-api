<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CalculateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use ApiResponser;

    public function __construct(protected BookingService $bookingService)
    {
        //
    }

    public function calculate(CalculateBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->calculatePrice($request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function index(IndexBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->getUserBookings(Auth::id(), $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->createBooking($request->validated(), Auth::id());

        if ($result['status'] === HttpStatusCode::CREATED->value) {
            return $this->created($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value && $result['data']->user_id !== $userId) {
            return $this->forbidden('You are not authorized to view this booking.');
        }

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function showByCode(string $bookingCode): JsonResponse
    {
        $result = $this->bookingService->getBookingByCode($bookingCode, Auth::id());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function invoice(int $id): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value && $result['data']->user_id !== $userId) {
            return $this->forbidden('You are not authorized to view this booking.');
        }

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'], 'Invoice data retrieved. PDF generation is not yet available.');
        }

        return $this->error($result['message'], $result['status']);
    }

    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->cancelBooking($id, Auth::id(), $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
