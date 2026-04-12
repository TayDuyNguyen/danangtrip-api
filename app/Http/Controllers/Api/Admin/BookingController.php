<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\BookingsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class BookingController
 * Handles administrative API requests for bookings.
 * (Xử lý các yêu cầu API quản trị cho đơn đặt tour)
 */
class BookingController extends Controller
{
    use ApiResponser;

    /**
     * BookingController constructor.
     * (Khởi tạo BookingController)
     */
    public function __construct(protected BookingService $bookingService)
    {
        //
    }

    /**
     * Display a listing of bookings.
     * (Hiển thị danh sách các đơn đặt tour)
     */
    public function index(IndexBookingRequest $request): JsonResponse
    {
        $result = $this->bookingService->getBookings($request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Export bookings to Excel.
     * (Xuất danh sách đơn đặt tour ra file Excel)
     */
    public function export(IndexBookingRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->bookingService->getBookings($request->validated());

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        // Return Excel download response
        return Excel::download(new BookingsExport($result['data']), 'bookings.xlsx');
    }

    /**
     * Display the specified booking.
     * (Hiển thị chi tiết đơn đặt tour cụ thể)
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->bookingService->getBooking($id);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Update booking status.
     * (Cập nhật trạng thái đơn đặt tour)
     */
    public function updateStatus(UpdateBookingStatusRequest $request, int $id): JsonResponse
    {
        $result = $this->bookingService->updateBookingStatus($id, $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data'] ?? null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
