<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\BookingValidation;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use ApiResponser;

    public function __construct(protected BookingService $bookingService)
    {
        //
    }

    public function calculate(Request $request): JsonResponse
    {
        $validation = BookingValidation::validateCalculate($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->calculatePrice($validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $validation = BookingValidation::validateIndex($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->getUserBookings($userId, $validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function store(Request $request): JsonResponse
    {
        $validation = BookingValidation::validateStore($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->createBooking($validation->validated(), Auth::id());

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

    public function cancel(Request $request, int $id): JsonResponse
    {
        $validation = BookingValidation::validateCancel($request->all());

        if ($validation->fails()) {
            return $this->validation_error($validation->errors()->first());
        }

        $result = $this->bookingService->cancelBooking($id, Auth::id(), $validation->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
