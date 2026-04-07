<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected TourScheduleRepositoryInterface $tourScheduleRepository,
        protected TourRepositoryInterface $tourRepository
    ) {}

    /**
     * Get all bookings with optional filters.
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
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve bookings',
            ];
        }
    }

    /**
     * Get a specific booking by ID.
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
                'data' => $booking,
                'message' => 'Booking retrieved successfully.',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking',
            ];
        }
    }

    /**
     * Get a specific booking by Code.
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
                'data' => $booking,
                'message' => 'Booking retrieved successfully.',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking by code',
            ];
        }
    }

    /**
     * Calculate price for a booking.
     */
    public function calculatePrice(array $data): array
    {
        try {
            $tour = $this->tourRepository->find($data['tour_id']);
            $schedule = $this->tourScheduleRepository->findWithTour($data['tour_schedule_id']);

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
            $priceAdult = $schedule->price_adult ?? $tour->price_adult;
            $priceChild = $schedule->price_child ?? $tour->price_child;
            $priceInfant = $schedule->price_infant ?? $tour->price_infant;

            $subtotalAdult = $adults * $priceAdult;
            $subtotalChild = $children * $priceChild;
            $subtotalInfant = $infants * $priceInfant;

            $totalAmount = $subtotalAdult + $subtotalChild + $subtotalInfant;

            $discountPercent = $tour->discount_percent ?? 0;
            $discountAmount = ($totalAmount * $discountPercent) / 100;
            $finalAmount = $totalAmount - $discountAmount;

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
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                ],
                'message' => 'Price calculated successfully.',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Price calculation failed',
            ];
        }
    }

    /**
     * Create a new booking.
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
                $priceCalc = $this->calculatePrice($data);
                if ($priceCalc['status'] !== HttpStatusCode::SUCCESS->value) {
                    return $priceCalc;
                }
                $priceData = $priceCalc['data'];

                // Create Booking Record
                $bookingData = [
                    'user_id' => $userId,
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

                // Create Booking Item
                $booking->items()->create([
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
                $tourSchedule->increment('booked_people', $requestedSeats);

                return [
                    'status' => HttpStatusCode::CREATED->value,
                    'data' => $booking->load('items'),
                    'message' => 'Booking created successfully.',
                ];
            });
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking creation failed',
            ];
        }
    }

    /**
     * User cancels a booking.
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

                $booking->update([
                    'booking_status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'],
                ]);

                // Return seats
                foreach ($booking->items as $item) {
                    $schedule = $this->tourScheduleRepository->findForUpdate($item->tour_schedule_id);
                    if ($schedule) {
                        $returnedSeats = $item->quantity_adult + $item->quantity_child;
                        $schedule->decrement('booked_people', $returnedSeats);
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking cancelled successfully.',
                ];
            });
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking cancellation failed',
            ];
        }
    }

    /**
     * Admin Confirms a booking.
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

                $booking->update([
                    'booking_status' => BookingStatus::CONFIRMED->value,
                    'confirmed_at' => now(),
                ]);

                // TODO: Create Notification for User here

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking confirmed successfully.',
                ];
            });
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking confirmation failed',
            ];
        }
    }

    /**
     * Admin Completes a booking.
     */
    public function completeBooking(int $id): array
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

                if ($booking->booking_status !== BookingStatus::CONFIRMED->value && $booking->booking_status !== BookingStatus::PENDING->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Only confirmed or pending bookings can be completed.',
                    ];
                }

                $booking->update([
                    'booking_status' => BookingStatus::COMPLETED->value,
                    'completed_at' => now(),
                    'payment_status' => PaymentStatus::PAID->value, // Usually completed means paid in full
                ]);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking completed successfully.',
                ];
            });
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking completion failed',
            ];
        }
    }

    /**
     * Admin Cancels a booking.
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

                $booking->update([
                    'booking_status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['cancellation_reason'] ?? 'Cancelled by Administrator',
                ]);

                // Return seats
                foreach ($booking->items as $item) {
                    $schedule = $this->tourScheduleRepository->findForUpdate($item->tour_schedule_id);
                    if ($schedule) {
                        $returnedSeats = $item->quantity_adult + $item->quantity_child;
                        $schedule->decrement('booked_people', $returnedSeats);
                    }
                }

                // TODO: Create Notification for User

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $booking->fresh(),
                    'message' => 'Booking cancelled successfully.',
                ];
            });
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Booking cancellation failed',
            ];
        }
    }

    /**
     * Update the status of a booking (generic).
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
                // Need cancellation reason array, we will just pass empty and fallback
                return $this->cancelBookingAdmin($id, ['cancellation_reason' => 'Status manually updated via generic updateStatus']);
            } elseif ($newStatus === BookingStatus::COMPLETED->value) {
                return $this->completeBooking($id);
            }

            $this->bookingRepository->updateStatus($id, $newStatus);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $booking->fresh(),
                'message' => 'Booking status updated successfully.',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update booking status',
            ];
        }
    }

    /**
     * Get bookings for a specific user.
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
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve user bookings',
            ];
        }
    }
}
