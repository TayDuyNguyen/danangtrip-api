<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\BookingsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Services\BookingService;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
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
        $filters = $request->validated();
        $filters['no_paginate'] = true;
        $result = $this->bookingService->getBookings($filters);

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        $data = $result['data'];

        // Ensure we always have a Collection (not a Paginator) for the export
        if ($data instanceof LengthAwarePaginator) {
            $data = $data->getCollection();
        } elseif (! $data instanceof Collection) {
            $data = collect($data);
        }

        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($fromDate && $toDate) {
            $asciiName = "bao-cao-dat-tour-{$fromDate}-{$toDate}.xlsx";
            $utf8Name = "Báo cáo đặt tour {$fromDate} - {$toDate}.xlsx";
        } else {
            $stamp = now()->format('Y-m-d_His');
            $asciiName = "bao-cao-dat-tour-{$stamp}.xlsx";
            $utf8Name = "Báo cáo đặt tour {$stamp}.xlsx";
        }

        $response = Excel::download(new BookingsExport($data), $asciiName);

        $safeAscii = str_replace(['"', '\\'], '-', $asciiName);
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="'.$safeAscii.'"; filename*=UTF-8\'\''.rawurlencode($utf8Name),
            true
        );

        return $response;
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

    /**
     * Get booking counts grouped by status.
     * (Lấy số lượng đơn đặt tour theo trạng thái)
     */
    public function statusCounts(IndexBookingRequest $request): JsonResponse
    {
        // We reuse the DashboardService logic or implement it in BookingService
        // Let's check BookingService.
        $result = $this->bookingService->getBookingStatusCounts($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
