<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CalculateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\ShowBookingByCodeRequest;
use App\Http\Requests\Booking\ShowBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Services\BookingService;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected InvoicePdfService $invoicePdfService
    ) {}

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
        $result = $this->bookingService->getUserBookings((int) $request->user()->id, $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->createBooking($request->validated(), (int) $request->user()->id);

        if ($result['status'] === HttpStatusCode::CREATED->value) {
            return $this->created($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function show(ShowBookingRequest $request, int $id): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value && $result['data']->user_id !== $userId) {
            return $this->forbidden('You are not authorized to view this booking.');
        }

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function showByCode(ShowBookingByCodeRequest $request): JsonResponse
    {
        $result = $this->bookingService->getBookingByCode(
            $request->validated()['booking_code'],
            (int) $request->user()->id
        );

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function invoice(ShowBookingRequest $request, int $id): Response|JsonResponse
    {
        $userId = (int) $request->user()->id;
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value && $result['data']->user_id !== $userId) {
            return $this->forbidden('You are not authorized to view this booking.');
        }

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            $booking = $result['data'];
            $pdf = $this->invoicePdfService->render($booking);
            $filename = 'hoa-don-'.$booking->booking_code.'.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.rawurlencode($filename),
                'Content-Length' => (string) strlen($pdf),
            ]);
        }

        return $this->error($result['message'], $result['status']);
    }

    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->cancelBooking($id, (int) $request->user()->id, $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
