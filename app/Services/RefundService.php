<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\RefundRequest;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RefundService
{
    public function __construct(private PointService $pointService) {}

    public function preview(Booking $booking): array
    {
        $booking->loadMissing(['items.tour', 'payments', 'paymentReceipts']);
        $departureAt = $this->departureAt($booking);
        $now = now();
        $hoursBeforeDeparture = max(0, $now->diffInMinutes($departureAt, false) / 60);
        $paidAmount = $this->paidAmount($booking);
        $policyAmount = min($paidAmount, (float) ($booking->final_amount ?? $paidAmount));
        $rules = $this->policy();
        $latestPaidAt = $booking->payments
            ->where('payment_status', 'success')
            ->whereNotNull('paid_at')
            ->max('paid_at');

        $minutesSincePayment = $latestPaidAt ? Carbon::parse($latestPaidAt)->diffInMinutes($now, false) : null;
        $graceEligible = $minutesSincePayment !== null
            && $minutesSincePayment >= 0
            && $minutesSincePayment <= (int) $rules['grace_period_minutes']
            && $hoursBeforeDeparture >= (int) $rules['grace_minimum_hours_before_departure']
            && ! $this->isHolidayDeparture($departureAt, $rules);

        if ($graceEligible) {
            $refundPercent = 100;
            $policyCode = 'grace_period';
        } elseif ($this->isHolidayDeparture($departureAt, $rules)) {
            $refundPercent = (float) ($rules['holiday_refund_percent'] ?? 0);
            $policyCode = 'holiday';
        } else {
            $refundPercent = 0;
            $policyCode = 'under_three_days';
            $configuredRules = collect($rules['rules'] ?? [])
                ->sortByDesc(fn (array $rule) => (float) ($rule['minimum_hours_before_departure'] ?? 0));
            foreach ($configuredRules as $rule) {
                $minimumHours = (float) ($rule['minimum_hours_before_departure'] ?? 0);
                if ($hoursBeforeDeparture >= $minimumHours) {
                    $refundPercent = (float) ($rule['refund_percent'] ?? 0);
                    $policyCode = match (true) {
                        $minimumHours >= 168 => 'seven_days_or_more',
                        $minimumHours >= 72 => 'three_to_under_seven_days',
                        default => 'under_three_days',
                    };
                    break;
                }
            }
        }

        $refundAmount = round($policyAmount * $refundPercent / 100, 2);

        return [
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'departure_at' => $departureAt->toIso8601String(),
            'hours_before_departure' => round($hoursBeforeDeparture, 2),
            'paid_amount' => $paidAmount,
            'policy_amount' => $policyAmount,
            'refund_percent' => $refundPercent,
            'refund_amount' => $refundAmount,
            'cancellation_fee' => max(0, $policyAmount - $refundAmount),
            'policy_code' => $policyCode,
            'grace_period_applied' => $graceEligible,
            'requires_bank_details' => $refundAmount > 0,
            'policy' => $rules,
        ];
    }

    public function createCancellationRequest(Booking $booking, array $data, int $userId): ?RefundRequest
    {
        $preview = $this->preview($booking);
        if ($preview['refund_amount'] <= 0) {
            return null;
        }

        $existing = RefundRequest::query()
            ->where('booking_id', $booking->id)
            ->where('reason_type', 'cancellation')
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return RefundRequest::query()->create([
            'refund_code' => $this->newCode(),
            'booking_id' => $booking->id,
            'payment_id' => $booking->payments->where('payment_status', 'success')->sortByDesc('id')->first()?->id,
            'reason_type' => 'cancellation',
            'requested_amount' => $preview['refund_amount'],
            'approved_amount' => $preview['refund_amount'],
            'refund_percent' => $preview['refund_percent'],
            'status' => 'pending',
            'bank_code' => ! empty($data['refund_bank_code'])
                ? strtoupper(trim((string) $data['refund_bank_code']))
                : null,
            'account_no' => ! empty($data['refund_account_no'])
                ? trim((string) $data['refund_account_no'])
                : null,
            'account_name' => ! empty($data['refund_account_name'])
                ? mb_strtoupper(trim((string) $data['refund_account_name']), 'UTF-8')
                : null,
            'policy_snapshot' => $preview,
            'reason' => $data['cancellation_reason'],
            'requested_by' => $userId,
            'requested_at' => now(),
        ]);
    }

    public function createOverpaymentRequest(Booking $booking, Payment $payment, float $excessAmount): RefundRequest
    {
        return RefundRequest::query()->updateOrCreate(
            [
                'booking_id' => $booking->id,
                'reason_type' => 'overpayment',
                'status' => 'pending',
            ],
            [
                'refund_code' => $this->newCode(),
                'payment_id' => $payment->id,
                'requested_amount' => $excessAmount,
                'approved_amount' => $excessAmount,
                'refund_percent' => 100,
                'policy_snapshot' => [
                    'expected_amount' => (float) $booking->final_amount,
                    'total_received' => $this->paidAmount($booking),
                    'excess_amount' => $excessAmount,
                ],
                'reason' => 'Hoàn lại số tiền khách đã chuyển thừa.',
                'requested_by' => $booking->user_id,
                'requested_at' => now(),
            ]
        );
    }

    public function createAdminAdjustmentRequest(Payment $payment, array $data, int $adminId): RefundRequest
    {
        $payment->loadMissing('booking');
        $amount = (float) ($data['approved_amount'] ?? $payment->amount);

        return RefundRequest::query()->create([
            'refund_code' => $this->newCode(),
            'booking_id' => $payment->booking_id,
            'payment_id' => $payment->id,
            'reason_type' => 'admin_adjustment',
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'refund_percent' => $payment->amount > 0 ? min(100, $amount / (float) $payment->amount * 100) : 0,
            'status' => 'pending',
            'bank_code' => strtoupper(trim((string) $data['refund_bank_code'])),
            'account_no' => trim((string) $data['refund_account_no']),
            'account_name' => mb_strtoupper(trim((string) $data['refund_account_name']), 'UTF-8'),
            'reason' => $data['refund_reason'],
            'requested_by' => $adminId,
            'requested_at' => now(),
        ]);
    }

    public function complete(RefundRequest $refund, int $adminId, array $data): RefundRequest
    {
        return DB::transaction(function () use ($refund, $adminId, $data) {
            $locked = RefundRequest::query()->lockForUpdate()->findOrFail($refund->id);
            if ($locked->status === 'completed') {
                return $locked;
            }

            $completedAmount = (float) ($data['approved_amount'] ?? $locked->approved_amount ?? $locked->requested_amount);
            $alreadyRefunded = (float) RefundRequest::query()
                ->where('booking_id', $locked->booking_id)
                ->where('status', 'completed')
                ->sum('approved_amount');
            $received = (float) PaymentReceipt::query()->where('booking_id', $locked->booking_id)->sum('amount');
            if ($received <= 0) {
                $received = (float) Payment::query()
                    ->where('booking_id', $locked->booking_id)
                    ->where('payment_status', 'success')
                    ->sum('amount');
            }

            if ($completedAmount <= 0 || $alreadyRefunded + $completedAmount > $received) {
                throw new \InvalidArgumentException('Refund amount exceeds the amount actually received.');
            }

            $updateData = [
                'approved_amount' => $completedAmount,
                'status' => 'completed',
                'completed_by' => $adminId,
                'transfer_reference' => $data['transfer_reference'],
                'evidence_url' => $data['evidence_url'] ?? null,
                'completed_at' => now(),
            ];
            if (! empty($data['refund_bank_code'])) {
                $updateData['bank_code'] = strtoupper(trim((string) $data['refund_bank_code']));
            }
            if (! empty($data['refund_account_no'])) {
                $updateData['account_no'] = trim((string) $data['refund_account_no']);
            }
            if (! empty($data['refund_account_name'])) {
                $updateData['account_name'] = mb_strtoupper(trim((string) $data['refund_account_name']), 'UTF-8');
            }
            $locked->update($updateData);

            $booking = Booking::query()->findOrFail($locked->booking_id);
            $totalRefunded = (float) RefundRequest::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'completed')
                ->sum('approved_amount');
            if ($totalRefunded >= min($received, (float) $booking->final_amount)) {
                $booking->update(['payment_status' => 'refunded']);
                Payment::query()
                    ->where('booking_id', $booking->id)
                    ->where('payment_status', 'success')
                    ->update([
                        'payment_status' => 'refunded',
                        'refunded_at' => now(),
                    ]);
            }

            if ($booking->user_id) {
                $this->pointService->reverseBookingPaymentPoints((int) $booking->user_id, (int) $booking->id);
                Notification::query()->create([
                    'user_id' => $booking->user_id,
                    'type' => 'payment_refunded',
                    'title' => 'Khoản hoàn tiền đã được chuyển',
                    'content' => "DanangTrip đã chuyển khoản hoàn tiền cho đơn {$booking->booking_code}.",
                    'data' => [
                        'booking_id' => $booking->id,
                        'refund_id' => $locked->id,
                        'refund_code' => $locked->refund_code,
                        'amount' => $completedAmount,
                        'transfer_reference' => $data['transfer_reference'],
                    ],
                    'is_read' => false,
                ]);
            }

            return $locked->fresh();
        });
    }

    public function adminPayload(RefundRequest $refund, bool $isList = false): array
    {
        $refund->loadMissing('booking');

        $payload = [
            'id' => $refund->id,
            'refund_code' => $refund->refund_code,
            'booking_id' => $refund->booking_id,
            'booking_code' => $refund->booking?->booking_code,
            'reason_type' => $refund->reason_type,
            'status' => $refund->status,
            'requested_amount' => (float) $refund->requested_amount,
            'approved_amount' => (float) ($refund->approved_amount ?? $refund->requested_amount),
            'refund_percent' => (float) $refund->refund_percent,
            'bank_code' => $refund->bank_code,
            'masked_account_no' => $this->maskAccount((string) $refund->account_no),
            'account_name' => $refund->account_name,
            'reason' => $refund->reason,
            'policy_snapshot' => $refund->policy_snapshot,
            'transfer_reference' => $refund->transfer_reference,
            'requested_at' => $refund->requested_at,
            'completed_at' => $refund->completed_at,
        ];

        if (! $isList) {
            $payload['account_no'] = $refund->account_no;
            $payload['qr_image_url'] = $refund->bank_code && $refund->account_no ? $this->buildRefundQrUrl(
                (int) ($refund->approved_amount ?? $refund->requested_amount),
                $refund->refund_code,
                $refund->bank_code,
                $refund->account_no,
                $refund->account_name
            ) : null;
        }

        return $payload;
    }

    private function buildRefundQrUrl(int $amount, string $transferContent, string $bankCode, string $accountNo, string $accountName): string
    {
        $baseUrl = rtrim((string) config('services.vietqr.image_base_url', 'https://img.vietqr.io/image'), '/');
        $template = (string) config('services.vietqr.template', 'compact2');

        return sprintf(
            '%s/%s-%s-%s.png?%s',
            $baseUrl,
            rawurlencode($bankCode),
            rawurlencode($accountNo),
            rawurlencode($template),
            http_build_query([
                'amount' => $amount,
                'addInfo' => $transferContent,
                'accountName' => $accountName,
            ], '', '&', PHP_QUERY_RFC3986)
        );
    }

    private function paidAmount(Booking $booking): float
    {
        $received = (float) $booking->paymentReceipts->sum('amount');

        return $received > 0
            ? $received
            : (float) $booking->payments->where('payment_status', 'success')->sum('amount');
    }

    private function departureAt(Booking $booking): Carbon
    {
        $item = $booking->items->sortBy('travel_date')->first();
        if (! $item) {
            throw new \InvalidArgumentException('Booking has no departure schedule.');
        }

        $departure = Carbon::parse($item->travel_date, config('app.timezone'))->startOfDay();
        $startTime = trim((string) ($item->tour?->start_time ?? ''));
        if (preg_match('/^(\d{1,2}):(\d{2})/', $startTime, $matches)) {
            $departure->setTime((int) $matches[1], (int) $matches[2]);
        }

        return $departure;
    }

    private function policy(): array
    {
        $defaults = [
            'rules' => [
                ['minimum_hours_before_departure' => 168, 'refund_percent' => 100],
                ['minimum_hours_before_departure' => 72, 'refund_percent' => 50],
                ['minimum_hours_before_departure' => 0, 'refund_percent' => 0],
            ],
            'grace_period_minutes' => 30,
            'grace_minimum_hours_before_departure' => 12,
            'holiday_refund_percent' => 0,
            'holiday_ranges' => [],
        ];
        $setting = Setting::query()->where('key', 'cancellation.rules')->first();

        return array_replace_recursive($defaults, is_array($setting?->cast_value) ? $setting->cast_value : []);
    }

    private function isHolidayDeparture(Carbon $departure, array $rules): bool
    {
        foreach ($rules['holiday_ranges'] ?? [] as $range) {
            if (empty($range['from']) || empty($range['to'])) {
                continue;
            }
            if ($departure->betweenIncluded(Carbon::parse($range['from']), Carbon::parse($range['to']))) {
                return true;
            }
        }

        return false;
    }

    private function newCode(): string
    {
        do {
            $code = 'RF-'.Str::upper(Str::random(10));
        } while (RefundRequest::query()->where('refund_code', $code)->exists());

        return $code;
    }

    private function maskAccount(string $account): string
    {
        return str_repeat('*', max(0, strlen($account) - 4)).substr($account, -4);
    }
}
