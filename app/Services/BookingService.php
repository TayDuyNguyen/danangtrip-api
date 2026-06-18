<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Enums\TourScheduleBookingAvailability;
use App\Enums\TourStatus;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\UserVoucher;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\PromotionRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;
use App\Support\JsonColumn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class BookingService
 * Handles business logic for bookings.
 * (Xử lý logic nghiệp vụ cho đơn đặt tour)
 */
class BookingService
{
    /**
     * BookingService constructor.
     * (Khởi tạo BookingService)
     */
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected TourScheduleRepositoryInterface $tourScheduleRepository,
        protected TourRepositoryInterface $tourRepository,
        protected TourStatusSyncService $tourStatusSyncService,
        protected PaymentRepositoryInterface $paymentRepository,
        protected BookingPaymentNotificationService $paymentNotificationService,
        protected PromotionRepositoryInterface $promotionRepository,
        protected PointService $pointService,
        protected RefundService $refundService
    ) {}

    /**
     * Get all bookings with optional filters.
     * (Lấy tất cả đơn đặt tour với bộ lọc tùy chọn)
     */
    public function getBookings(array $filters = []): array
    {
        try {
            $bookings = $this->bookingRepository->getBookings($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $bookings,
                'message' => 'Bookings retrieved successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve bookings',
            ];
        }
    }

    /**
     * Get a specific booking by ID.
     * (Lấy thông tin đơn đặt tour cụ thể theo ID)
     */
    public function getBooking(int $id): array
    {
        try {
            $booking = $this->bookingRepository->findWithDetails($id);

            if (! $booking) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Booking not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->withLatestPendingPayment($booking),
                'message' => 'Booking retrieved successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking',
            ];
        }
    }

    /**
     * Get a specific booking by Code.
     * (Lấy thông tin đơn đặt tour cụ thể theo mã Code)
     */
    public function getBookingByCode(string $code, ?int $userId = null): array
    {
        try {
            $booking = $this->bookingRepository->findByCode($code);

            if (! $booking) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Booking not found.',
                ];
            }

            if ($userId && $booking->user_id !== $userId) {
                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'message' => 'You are not authorized to view this booking.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->withLatestPendingPayment($booking),
                'message' => 'Booking retrieved successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking by code',
            ];
        }
    }

    /**
     * Calculate price for a booking.
     * (Tính giá cho một đơn đặt tour)
     */
    public function calculatePrice(array $data, ?int $userId = null): array
    {
        try {
            $tour = $this->tourRepository->find($data['tour_id']);
            $schedule = $this->tourScheduleRepository->find($data['tour_schedule_id']);

            if (! $tour || ! $schedule || $schedule->tour_id !== $tour->id) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Invalid tour or schedule provided.',
                ];
            }

            $adults = $data['quantity_adult'];
            $children = $data['quantity_child'] ?? 0;
            $infants = $data['quantity_infant'] ?? 0;

            // Use schedule price if available, fallback to tour price
            $priceAdult = max(0.0, (float) ($schedule->price_adult ?? $tour->price_adult));
            $priceChild = max(0.0, (float) ($schedule->price_child ?? $tour->price_child));
            $priceInfant = max(0.0, (float) ($schedule->price_infant ?? $tour->price_infant));

            $subtotalAdult = $adults * $priceAdult;
            $subtotalChild = $children * $priceChild;
            $subtotalInfant = $infants * $priceInfant;

            $totalAmount = max(0.0, $subtotalAdult + $subtotalChild + $subtotalInfant);

            $discountPercent = min(100.0, max(0.0, (float) ($tour->discount_percent ?? 0)));
            $tourDiscount = ($totalAmount * $discountPercent) / 100;
            $tourSubtotal = max(0.0, $totalAmount - $tourDiscount);

            $promotionDiscount = 0.0;
            $voucherDiscount = 0.0;
            $promotionData = null;
            $voucherData = null;

            if (! empty($data['promotion_code'])) {
                $code = trim((string) $data['promotion_code']);
                $promotion = $this->promotionRepository->findByCode($code);

                if (! $promotion) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Promotion code not found.',
                    ];
                }

                if (! $promotion->isValid()) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Promotion code is not currently valid.',
                    ];
                }

                if ($tourSubtotal < (float) $promotion->min_order_amount) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Minimum order amount for this promotion code is '.number_format($promotion->min_order_amount).' đ.',
                    ];
                }

                $promotionDiscount = min(
                    $tourSubtotal,
                    max(0.0, (float) $promotion->calculateDiscount($tourSubtotal))
                );
                $promotionData = [
                    'id' => $promotion->id,
                    'code' => $promotion->code,
                    'name' => $promotion->name,
                    'discount_type' => $promotion->discount_type,
                    'discount_value' => $promotion->discount_value,
                    'coupon_discount_amount' => $promotionDiscount,
                    'source' => 'promotion',
                ];
            }

            $afterPromotion = max(0.0, $tourSubtotal - $promotionDiscount);

            if (! empty($data['user_voucher_code'])) {
                if (! $userId) {
                    return [
                        'status' => HttpStatusCode::UNAUTHORIZED->value,
                        'message' => 'Authentication is required to use a personal voucher.',
                    ];
                }

                $code = trim((string) $data['user_voucher_code']);
                $voucher = UserVoucher::query()
                    ->where('user_id', $userId)
                    ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                    ->first();

                if (! $voucher) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Personal voucher not found.',
                    ];
                }

                if (! $voucher->isValidForUser($userId)) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'This personal voucher is not currently valid.',
                    ];
                }

                if ($tourSubtotal < (float) $voucher->min_order_amount) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Minimum order amount for this voucher is '.number_format((float) $voucher->min_order_amount).' đ.',
                    ];
                }

                $voucherDiscount = min(
                    $afterPromotion,
                    max(0.0, (float) $voucher->calculateDiscount($afterPromotion))
                );
                $voucherData = [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'discount_type' => $voucher->discount_type,
                    'discount_value' => $voucher->discount_value,
                    'voucher_discount_amount' => $voucherDiscount,
                    'source' => 'user_voucher',
                ];
            }

            $couponDiscount = min($tourSubtotal, $promotionDiscount + $voucherDiscount);
            $discountAmount = min($totalAmount, max(0.0, $tourDiscount + $couponDiscount));
            $finalAmount = max(0.0, $afterPromotion - $voucherDiscount);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'breakdown' => [
                        'adult' => [
                            'quantity' => $adults,
                            'unit_price' => $priceAdult,
                            'subtotal' => $subtotalAdult,
                        ],
                        'child' => [
                            'quantity' => $children,
                            'unit_price' => $priceChild,
                            'subtotal' => $subtotalChild,
                        ],
                        'infant' => [
                            'quantity' => $infants,
                            'unit_price' => $priceInfant,
                            'subtotal' => $subtotalInfant,
                        ],
                    ],
                    'total_amount' => $totalAmount,
                    'tour_discount' => $tourDiscount,
                    'promotion_discount' => $promotionDiscount,
                    'voucher_discount' => $voucherDiscount,
                    'coupon_discount' => $couponDiscount,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'applied_promotion' => $promotionData,
                    'applied_user_voucher' => $voucherData,
                ],
                'message' => 'Price calculated successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Booking price calculation failed', [
                'payload' => $data,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Price calculation failed',
            ];
        }
    }

    /**
     * Create a new booking.
     * (Tạo một đơn đặt tour mới)
     */
    public function createBooking(array $data, ?int $userId = null): array
    {
        try {
            return DB::transaction(function () use ($data, $userId) {
                // Lock the schedule to prevent overbooking
                $tourSchedule = $this->tourScheduleRepository->findForUpdate($data['tour_schedule_id']);

                if (! $tourSchedule) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Tour schedule not found.',
                    ];
                }

                if ($tourSchedule->tour_id !== $data['tour_id']) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Schedule does not belong to the selected tour.',
                    ];
                }

                $requestedSeats = $data['quantity_adult'] + ($data['quantity_child'] ?? 0);
                $availableSeats = $tourSchedule->max_people - $tourSchedule->booked_people;

                if ($availableSeats < $requestedSeats) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Not enough available seats for this tour schedule.',
                    ];
                }

                // Calculate price
                $priceCalc = $this->calculatePrice($data, $userId);
                if ($priceCalc['status'] !== HttpStatusCode::SUCCESS->value) {
                    return $priceCalc;
                }
                $priceData = $priceCalc['data'];

                $promotionId = null;
                $userVoucherId = null;
                $promotion = null;
                $voucher = null;
                $tourSubtotal = (float) $priceData['total_amount'] - (float) $priceData['tour_discount'];

                if (! empty($data['promotion_code'])) {
                    $code = trim((string) $data['promotion_code']);
                    $promotion = $this->promotionRepository->findByCodeForUpdate($code);

                    if (! $promotion) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'Promotion code not found.',
                        ];
                    }

                    if (! $promotion->isValid()) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'Promotion code is not currently valid.',
                        ];
                    }

                    if ($tourSubtotal < (float) $promotion->min_order_amount) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'Minimum order amount for this promotion code is '.number_format((float) $promotion->min_order_amount).' đ.',
                        ];
                    }

                    $promotionId = (int) $promotion->id;

                    if ($promotion->usage_per_user !== null && $userId) {
                        $userUsageCount = $this->bookingRepository->countNonCancelledByUserAndPromotion($userId, $promotionId);
                        if ($userUsageCount >= $promotion->usage_per_user) {
                            return [
                                'status' => HttpStatusCode::BAD_REQUEST->value,
                                'message' => 'You have already reached the maximum usage limit for this promotion code.',
                            ];
                        }
                    }
                }

                if (! empty($data['user_voucher_code'])) {
                    if (! $userId) {
                        return [
                            'status' => HttpStatusCode::UNAUTHORIZED->value,
                            'message' => 'Authentication is required to use a personal voucher.',
                        ];
                    }

                    $code = trim((string) $data['user_voucher_code']);
                    $voucher = UserVoucher::query()
                        ->where('user_id', $userId)
                        ->whereRaw('LOWER(code) = ?', [strtolower($code)])
                        ->lockForUpdate()
                        ->first();

                    if (! $voucher) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'Personal voucher not found.',
                        ];
                    }

                    if (! $voucher->isValidForUser($userId)) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'This personal voucher is not currently valid.',
                        ];
                    }

                    if ($tourSubtotal < (float) $voucher->min_order_amount) {
                        return [
                            'status' => HttpStatusCode::BAD_REQUEST->value,
                            'message' => 'Minimum order amount for this voucher is '.number_format((float) $voucher->min_order_amount).' đ.',
                        ];
                    }

                    $userVoucherId = (int) $voucher->id;
                }

                // Create Booking Record
                $bookingData = [
                    'user_id' => $userId,
                    'promotion_id' => $promotionId,
                    'user_voucher_id' => $userVoucherId,
                    'booking_code' => 'BOOK-'.Str::upper(Str::random(8)),
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_address' => $data['customer_address'] ?? null,
                    'customer_note' => $data['customer_note'] ?? null,

                    'total_amount' => $priceData['total_amount'],
                    'discount_amount' => $priceData['discount_amount'],
                    'final_amount' => $priceData['final_amount'],
                    'deposit_amount' => 0, // Assuming 0 for now

                    'payment_method' => $data['payment_method'],
                    'payment_status' => PaymentStatus::PENDING->value,
                    'booking_status' => BookingStatus::PENDING->value,
                    'booked_at' => now(),
                ];

                $booking = $this->bookingRepository->create($bookingData);

                if ($promotion !== null) {
                    $promotion->increment('used_count');
                }

                if ($voucher !== null) {
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now(),
                    ]);
                }

                // Create Booking Item
                $this->bookingRepository->createItem((int) $booking->id, [
                    'tour_id' => $data['tour_id'],
                    'tour_schedule_id' => $data['tour_schedule_id'],
                    'item_type' => 'tour',
                    'item_name' => $tourSchedule->tour->name,
                    'travel_date' => $tourSchedule->start_date,
                    'quantity_adult' => $data['quantity_adult'],
                    'quantity_child' => $data['quantity_child'] ?? 0,
                    'quantity_infant' => $data['quantity_infant'] ?? 0,
                    'unit_price_adult' => $priceData['breakdown']['adult']['unit_price'],
                    'unit_price_child' => $priceData['breakdown']['child']['unit_price'],
                    'unit_price_infant' => $priceData['breakdown']['infant']['unit_price'],
                    'subtotal' => $priceData['final_amount'],
                    'status' => 'active',
                ]);

                // Update booked_people counter
                $this->tourScheduleRepository->increaseBookedPeople((int) $tourSchedule->id, $requestedSeats);
                $tourSchedule = $this->tourScheduleRepository->findForUpdate((int) $tourSchedule->id);
                if ($tourSchedule->booked_people >= $tourSchedule->max_people) {
                    $this->tourScheduleRepository->updateBookingAvailability((int) $tourSchedule->id, TourScheduleBookingAvailability::SOLD_OUT->value);
                }
                $this->tourStatusSyncService->syncByTourId((int) $tourSchedule->tour_id);

                return [
                    'status' => HttpStatusCode::CREATED->value,
                    'data' => $booking->load('items'),
                    'message' => 'Booking created successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'payload' => $data,
                'user_id' => $userId,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking creation failed',
            ];
        }
    }

    /**
     * User cancels a booking.
     * (Người dùng hủy đơn đặt tour)
     */
    public function cancelBooking(int $id, int $userId, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $userId, $data) {
                $booking = $this->bookingRepository->findWithDetails($id);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found.',
                    ];
                }

                if ($booking->user_id !== $userId) {
                    return [
                        'status' => HttpStatusCode::FORBIDDEN->value,
                        'message' => 'You are not authorized to cancel this booking.',
                    ];
                }

                if ($booking->booking_status === BookingStatus::CANCELLED->value || $booking->booking_status === BookingStatus::COMPLETED->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Booking is already cancelled or completed.',
                    ];
                }

                $preview = $this->refundService->preview($booking);
                if (now()->greaterThanOrEqualTo(Carbon::parse($preview['departure_at']))) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'The tour has already departed. Please contact support.',
                    ];
                }

                if ($preview['requires_bank_details']) {
                    foreach (['refund_bank_code', 'refund_account_no', 'refund_account_name'] as $field) {
                        if (empty($data[$field])) {
                            return [
                                'status' => HttpStatusCode::VALIDATION_ERROR->value,
                                'message' => 'Refund bank information is required.',
                            ];
                        }
                    }
                }

                $this->bookingRepository->updateBooking((int) $booking->id, [
                    'booking_status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'],
                ]);

                // Return seats
                foreach ($booking->items as $item) {
                    $schedule = $this->tourScheduleRepository->findForUpdate($item->tour_schedule_id);
                    if ($schedule) {
                        $returnedSeats = $item->quantity_adult + $item->quantity_child;
                        $this->tourScheduleRepository->decreaseBookedPeople((int) $schedule->id, $returnedSeats);
                        $schedule = $this->tourScheduleRepository->findForUpdate((int) $schedule->id);
                        if (
                            $schedule->booking_availability === TourScheduleBookingAvailability::SOLD_OUT
                            && $schedule->booked_people < $schedule->max_people
                            && $item->tour?->status !== TourStatus::INACTIVE->value
                        ) {
                            $this->tourScheduleRepository->updateBookingAvailability((int) $schedule->id, TourScheduleBookingAvailability::OPEN->value);
                        }
                        $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);
                    }
                }

                $this->createBookingNotification(
                    $booking->fresh(),
                    'booking_cancelled',
                    'Đơn đặt tour đã được hủy',
                    "Đơn {$booking->booking_code} đã được ghi nhận hủy theo yêu cầu của bạn."
                );

                $refundRequest = $this->refundService->createCancellationRequest(
                    $booking->fresh()->load(['items.tour', 'payments', 'paymentReceipts']),
                    $data,
                    $userId
                );
                if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
                    $this->pointService->reverseBookingPaymentPoints($userId, (int) $booking->id);
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'booking' => $booking->fresh(),
                        'refund_preview' => $preview,
                        'refund_request' => $refundRequest,
                    ],
                    'message' => 'Booking cancelled successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Booking cancellation (user) failed: '.$e->getMessage(), ['exception' => $e]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking cancellation failed',
            ];
        }
    }

    public function previewRefund(int $id, int $userId): array
    {
        try {
            $booking = $this->bookingRepository->findWithDetails($id);
            if (! $booking) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Booking not found.'];
            }
            if ((int) $booking->user_id !== $userId) {
                return ['status' => HttpStatusCode::FORBIDDEN->value, 'message' => 'You are not authorized to view this booking.'];
            }
            if (in_array($booking->booking_status, [BookingStatus::CANCELLED->value, BookingStatus::COMPLETED->value], true)) {
                return ['status' => HttpStatusCode::BAD_REQUEST->value, 'message' => 'Booking cannot be cancelled.'];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->refundService->preview($booking),
                'message' => 'Refund preview retrieved successfully.',
            ];
        } catch (\Throwable $e) {
            Log::error('Refund preview failed', ['booking_id' => $id, 'exception' => $e]);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to calculate refund preview.'];
        }
    }

    public function previewRefundAdmin(int $id): array
    {
        try {
            $booking = $this->bookingRepository->findWithDetails($id);
            if (! $booking) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Booking not found.'];
            }
            if (in_array($booking->booking_status, [BookingStatus::CANCELLED->value, BookingStatus::COMPLETED->value], true)) {
                return ['status' => HttpStatusCode::BAD_REQUEST->value, 'message' => 'Booking cannot be cancelled.'];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->refundService->preview($booking),
                'message' => 'Refund preview retrieved successfully.',
            ];
        } catch (\Throwable $e) {
            Log::error('Admin refund preview failed', ['booking_id' => $id, 'exception' => $e]);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to calculate refund preview.'];
        }
    }

    /**
     * Admin Confirms a booking.
     * (Quản trị viên xác nhận đơn đặt tour)
     */
    public function confirmBooking(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $booking = $this->bookingRepository->find($id);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found.',
                    ];
                }

                if ($booking->booking_status !== BookingStatus::PENDING->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Only pending bookings can be confirmed.',
                    ];
                }

                $this->bookingRepository->updateBooking((int) $booking->id, [
                    'booking_status' => BookingStatus::CONFIRMED->value,
                    'confirmed_at' => now(),
                ]);

                $this->createBookingNotification(
                    $booking->fresh(),
                    'booking_confirmed',
                    'Đơn đặt tour đã được xác nhận',
                    "Đơn {$booking->booking_code} đã được xác nhận. Vui lòng kiểm tra lịch khởi hành và thông tin điểm hẹn trước chuyến đi."
                );

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking confirmed successfully.',
                ];
            });
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking confirmation failed',
            ];
        }
    }

    /**
     * Admin Completes a booking.
     * (Quản trị viên hoàn thành đơn đặt tour)
     */
    public function completeBooking(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $booking = $this->bookingRepository->findForUpdate($id);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found.',
                    ];
                }

                if ($booking->booking_status !== BookingStatus::CONFIRMED->value && $booking->booking_status !== BookingStatus::PENDING->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Only confirmed or pending bookings can be completed.',
                    ];
                }

                $completedAt = now();
                $successfulPayment = $this->paymentRepository->findSuccessfulByBookingId((int) $booking->id);

                if (! $successfulPayment) {
                    $this->paymentRepository->create([
                        'booking_id' => $booking->id,
                        'transaction_code' => 'ADMIN-COMPLETE-'.$booking->id.'-'.$completedAt->format('YmdHis'),
                        'amount' => $booking->final_amount,
                        'payment_method' => $booking->payment_method,
                        'payment_status' => PaymentStatus::SUCCESS->value,
                        'payment_gateway' => 'ADMIN',
                        'gateway_response' => [
                            'source' => 'admin_complete_booking',
                        ],
                        'paid_at' => $completedAt,
                    ]);
                }

                $completionData = [
                    'booking_status' => BookingStatus::COMPLETED->value,
                    'completed_at' => $completedAt,
                    'payment_status' => PaymentStatus::SUCCESS->value,
                ];

                if (! $this->bookingRepository->updateBooking((int) $booking->id, $completionData)) {
                    throw new \RuntimeException('Unable to persist completed booking status.');
                }

                $booking->fill($completionData);
                $this->createBookingNotification(
                    $booking->fresh(),
                    'booking_completed',
                    'Tour đã hoàn thành',
                    "Đơn {$booking->booking_code} đã được cập nhật hoàn thành. Cảm ơn bạn đã sử dụng DanangTrip."
                );

                if ($booking->user_id) {
                    $this->pointService->awardPoints(
                        (int) $booking->user_id,
                        'booking_paid',
                        'booking',
                        (int) $booking->id,
                        'Thưởng điểm hoàn thành đơn '.$booking->booking_code
                    );
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking,
                    'message' => 'Booking completed successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Booking completion failed', [
                'booking_id' => $id,
                'exception' => $e,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking completion failed',
            ];
        }
    }

    /**
     * Admin Cancels a booking.
     * (Quản trị viên hủy đơn đặt tour)
     */
    public function cancelBookingAdmin(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $booking = $this->bookingRepository->findWithDetails($id);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found.',
                    ];
                }

                if ($booking->booking_status === BookingStatus::CANCELLED->value || $booking->booking_status === BookingStatus::COMPLETED->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Booking is already cancelled or completed.',
                    ];
                }

                $this->bookingRepository->updateBooking((int) $booking->id, [
                    'booking_status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'] ?? 'Cancelled by Administrator',
                ]);

                // Return seats
                foreach ($booking->items as $item) {
                    $schedule = $this->tourScheduleRepository->findForUpdate($item->tour_schedule_id);
                    if ($schedule) {
                        $returnedSeats = $item->quantity_adult + $item->quantity_child;
                        $this->tourScheduleRepository->decreaseBookedPeople((int) $schedule->id, $returnedSeats);
                        $schedule = $this->tourScheduleRepository->findForUpdate((int) $schedule->id);
                        if (
                            $schedule->booking_availability === TourScheduleBookingAvailability::SOLD_OUT
                            && $schedule->booked_people < $schedule->max_people
                            && $item->tour?->status !== TourStatus::INACTIVE->value
                        ) {
                            $this->tourScheduleRepository->updateBookingAvailability((int) $schedule->id, TourScheduleBookingAvailability::OPEN->value);
                        }
                        $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);
                    }
                }

                $freshBooking = $booking->fresh();
                $this->createBookingNotification(
                    $freshBooking,
                    'booking_cancelled',
                    'Đơn đặt tour đã bị hủy',
                    "Đơn {$booking->booking_code} đã được quản trị viên cập nhật hủy. Lý do: ".($data['cancellation_reason'] ?? 'Không có ghi chú thêm').'.'
                );

                if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
                    $this->refundService->createCancellationRequest(
                        $booking->fresh()->load(['items.tour', 'payments', 'paymentReceipts']),
                        [
                            'cancellation_reason' => $data['cancellation_reason'] ?? 'Hủy bởi quản trị viên',
                        ],
                        (int) Auth::id()
                    );
                    if ($booking->user_id) {
                        $this->pointService->reverseBookingPaymentPoints(
                            (int) $booking->user_id,
                            (int) $booking->id
                        );
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $freshBooking,
                    'message' => 'Booking cancelled successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Booking cancellation (admin) failed: '.$e->getMessage(), ['exception' => $e]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking cancellation failed',
            ];
        }
    }

    /**
     * Update the status of a booking (generic).
     * (Cập nhật trạng thái của đơn đặt tour - chung)
     */
    public function updateBookingStatus(int $id, array $data): array
    {
        try {
            $booking = $this->bookingRepository->find($id);

            if (! $booking) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Booking not found.',
                ];
            }

            $newStatus = $data['booking_status'];

            // Delegate to specific methods if possible to ensure logic flows cleanly
            if ($newStatus === BookingStatus::CONFIRMED->value) {
                return $this->confirmBooking($id);
            } elseif ($newStatus === BookingStatus::CANCELLED->value) {
                return $this->cancelBookingAdmin($id, $data);
            } elseif ($newStatus === BookingStatus::COMPLETED->value) {
                return $this->completeBooking($id);
            }

            $this->bookingRepository->updateStatus($id, $newStatus);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $booking->fresh(),
                'message' => 'Booking status updated successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Booking status update failed: '.$e->getMessage(), ['exception' => $e]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update booking status',
            ];
        }
    }

    /**
     * Get bookings for a specific user.
     * (Lấy danh sách đơn đặt tour của một người dùng cụ thể)
     */
    public function getUserBookings(int $userId, array $filters = []): array
    {
        try {
            $bookings = $this->bookingRepository->getUserBookings($userId, $filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $bookings,
                'message' => 'User bookings retrieved successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve user bookings',
            ];
        }
    }

    /**
     * Get booking counts grouped by status.
     * (Lấy số lượng đơn đặt tour theo trạng thái)
     */
    public function getBookingStatusCounts(array $filters = []): array
    {
        try {
            $data = $this->bookingRepository->getStatusCounts($filters);

            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $result = [];
            foreach ($statuses as $status) {
                $result[$status] = (int) ($data[$status] ?? 0);
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $result,
                'message' => 'Booking status counts retrieved successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking status counts.',
            ];
        }
    }

    /**
     * Auto-cancel pending bookings that were never paid within the hold window.
     *
     * @return array{expired: int, skipped: int, booking_ids: int[]}
     */
    public function expireUnpaidBookings(?Carbon $now = null, ?int $expiryMinutes = null): array
    {
        $now ??= now();
        $expiryMinutes = max(1, $expiryMinutes ?? (int) config('booking.unpaid_expiry_minutes', 60));
        $cutoff = $now->copy()->subMinutes($expiryMinutes);
        $cancellationReason = "Đơn bị hủy tự động do quá {$expiryMinutes} phút chưa hoàn tất thanh toán.";

        $bookings = $this->bookingRepository->getUnpaidExpiredCandidates($cutoff);

        $expired = 0;
        $skipped = 0;
        $bookingIds = [];

        foreach ($bookings as $booking) {
            try {
                $didExpire = DB::transaction(function () use ($booking, $cancellationReason, $expiryMinutes, $cutoff): bool {
                    $lockedBooking = $this->bookingRepository->findForUpdate((int) $booking->id);
                    if (! $lockedBooking) {
                        return false;
                    }

                    $bookedAt = $lockedBooking->booked_at ?? $lockedBooking->created_at;
                    if (! $bookedAt || $bookedAt->greaterThan($cutoff)) {
                        return false;
                    }

                    if ($lockedBooking->booking_status !== BookingStatus::PENDING->value) {
                        return false;
                    }

                    if (! in_array($lockedBooking->payment_status, ['pending', 'unpaid', 'failed'], true)) {
                        return false;
                    }

                    $lockedBooking->loadMissing(['items.tour', 'payments']);

                    $this->bookingRepository->updateBooking((int) $lockedBooking->id, [
                        'booking_status' => BookingStatus::CANCELLED->value,
                        'payment_status' => PaymentStatus::FAILED->value,
                        'cancelled_at' => now(),
                        'cancellation_reason' => $cancellationReason,
                    ]);

                    foreach ($lockedBooking->items as $item) {
                        $schedule = $this->tourScheduleRepository->findForUpdate($item->tour_schedule_id);
                        if (! $schedule) {
                            continue;
                        }

                        $returnedSeats = $item->quantity_adult + $item->quantity_child;
                        $this->tourScheduleRepository->decreaseBookedPeople((int) $schedule->id, $returnedSeats);
                        $schedule = $this->tourScheduleRepository->findForUpdate((int) $schedule->id);
                        if (
                            $schedule->booking_availability === TourScheduleBookingAvailability::SOLD_OUT
                            && $schedule->booked_people < $schedule->max_people
                            && $schedule->status === 'available'
                            && $schedule->start_date?->toDateString() >= now()->toDateString()
                            && ($schedule->booking_deadline === null || $schedule->booking_deadline->isFuture())
                            && $item->tour?->status !== TourStatus::INACTIVE->value
                        ) {
                            $this->tourScheduleRepository->updateBookingAvailability(
                                (int) $schedule->id,
                                TourScheduleBookingAvailability::OPEN->value
                            );
                        }
                        $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);
                    }

                    $this->paymentRepository->markPendingPaymentsFailedByBookingId((int) $lockedBooking->id);

                    if ($lockedBooking->promotion_id) {
                        $this->promotionRepository->decrementUsedCountIfPositive((int) $lockedBooking->promotion_id);
                    }

                    if ($lockedBooking->user_voucher_id) {
                        $this->bookingRepository->restoreUserVoucherToActive((int) $lockedBooking->user_voucher_id);
                    }

                    $freshBooking = $lockedBooking->fresh();
                    $this->createBookingNotification(
                        $freshBooking,
                        'booking_unpaid_expired',
                        'Đơn đặt tour đã bị hủy',
                        "Đơn {$lockedBooking->booking_code} đã bị hủy tự động vì quá {$expiryMinutes} phút chưa thanh toán. Chỗ trên tour đã được trả lại."
                    );

                    return true;
                });

                if ($didExpire) {
                    $expired++;
                    $bookingIds[] = (int) $booking->id;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('Failed to expire unpaid booking', [
                    'booking_id' => $booking->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return [
            'expired' => $expired,
            'skipped' => $skipped,
            'booking_ids' => $bookingIds,
        ];
    }

    /**
     * Preview bookings eligible for automatic unpaid expiry without mutating data.
     *
     * @return array{count: int, booking_ids: int[]}
     */
    public function previewUnpaidBookings(?Carbon $now = null, ?int $expiryMinutes = null): array
    {
        $now ??= now();
        $expiryMinutes = max(1, $expiryMinutes ?? (int) config('booking.unpaid_expiry_minutes', 60));
        $bookings = $this->bookingRepository->getUnpaidExpiredCandidates(
            $now->copy()->subMinutes($expiryMinutes)
        );

        return [
            'count' => $bookings->count(),
            'booking_ids' => $bookings->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function paymentSessionMinutes(): int
    {
        return max(1, (int) config('booking.payment_session_minutes', 15));
    }

    private function withLatestPendingPayment(Booking $booking): Booking
    {
        $payments = $booking->relationLoaded('payments')
            ? $booking->payments
            : $booking->payments()->get();

        $latestPendingPayment = $payments
            ->where('payment_status', PaymentStatus::PENDING->value)
            ->filter(fn ($payment) => $payment->created_at?->gt(
                now()->subMinutes($this->paymentSessionMinutes())
            ))
            ->sortByDesc('id')
            ->first();

        $booking->setAttribute('latest_pending_payment', $latestPendingPayment ? [
            'id' => $latestPendingPayment->id,
            'transaction_code' => $latestPendingPayment->transaction_code,
            'payment_status' => $latestPendingPayment->payment_status,
            'payment_method' => $latestPendingPayment->payment_method,
            'payment_gateway' => $latestPendingPayment->payment_gateway,
            'amount' => $latestPendingPayment->amount,
            'created_at' => $latestPendingPayment->created_at?->toISOString(),
            'expires_at' => $latestPendingPayment->created_at?->copy()->addMinutes($this->paymentSessionMinutes())->toISOString(),
        ] : null);

        return $booking;
    }

    private function createBookingNotification(?Booking $booking, string $type, string $title, string $content): void
    {
        if (! $booking?->user_id) {
            return;
        }

        $existsQuery = Notification::query()
            ->where('user_id', $booking->user_id)
            ->where('type', $type);

        JsonColumn::whereInt($existsQuery, 'data', 'booking_id', (int) $booking->id);

        if ($existsQuery->exists()) {
            return;
        }

        Notification::query()->create([
            'user_id' => $booking->user_id,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'data' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
            ],
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Admin manually confirms booking payment.
     * (Quản trị viên xác nhận thủ công thanh toán của đơn đặt tour)
     */
    public function confirmBookingPayment(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $booking = $this->bookingRepository->find($id);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found.',
                    ];
                }

                if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Booking payment is already confirmed.',
                    ];
                }

                if ($booking->booking_status === BookingStatus::CANCELLED->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Cannot confirm payment for a cancelled booking.',
                    ];
                }

                // Update booking status and payment status
                $updateData = [
                    'payment_status' => PaymentStatus::SUCCESS->value,
                ];

                // If booking is pending, automatically confirm it
                if ($booking->booking_status === BookingStatus::PENDING->value) {
                    $updateData['booking_status'] = BookingStatus::CONFIRMED->value;
                    $updateData['confirmed_at'] = now();
                }

                $this->bookingRepository->updateBooking((int) $booking->id, $updateData);

                // Find the latest pending payment or create one
                $latestPendingPayment = $booking->payments()
                    ->where('payment_status', PaymentStatus::PENDING->value)
                    ->latest('id')
                    ->first();

                if ($latestPendingPayment) {
                    $this->paymentRepository->update((int) $latestPendingPayment->id, [
                        'payment_status' => PaymentStatus::SUCCESS->value,
                        'paid_at' => now(),
                        'gateway_response' => array_merge(
                            is_array($latestPendingPayment->gateway_response) ? $latestPendingPayment->gateway_response : [],
                            ['confirmed_by' => 'admin']
                        ),
                    ]);
                } else {
                    // Create new success payment record
                    $this->paymentRepository->create([
                        'booking_id' => $booking->id,
                        'transaction_code' => 'PAY-MANUAL-'.strtoupper(Str::random(10)),
                        'amount' => $booking->final_amount ?? $booking->total_amount,
                        'payment_method' => $booking->payment_method ?? 'bank_transfer',
                        'payment_status' => PaymentStatus::SUCCESS->value,
                        'payment_gateway' => 'admin_manual',
                        'gateway_response' => [
                            'confirmed_by' => 'admin',
                            'source' => 'admin_manual_confirmation',
                        ],
                        'paid_at' => now(),
                    ]);
                }

                // Send payment confirmation email/notification
                $this->paymentNotificationService->sendPaymentConfirmedAfterCommit((int) $booking->id);
                if ($booking->user_id) {
                    $this->pointService->awardPoints(
                        (int) $booking->user_id,
                        'booking_paid',
                        'booking',
                        (int) $booking->id,
                        'Thưởng điểm thanh toán đơn '.$booking->booking_code
                    );
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking payment confirmed successfully.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Manual payment confirmation failed', [
                'booking_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to confirm booking payment.',
            ];
        }
    }
}
