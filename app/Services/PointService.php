<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PointReward;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\UserPointBalance;
use App\Models\UserVoucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PointService
{
    public function getOverview(int $userId): array
    {
        return [
            'balance' => $this->getOrCreateBalance($userId),
            'rewards' => $this->getActiveRewards(),
            'vouchers' => $this->getActiveVouchers($userId),
            'recent_transactions' => PointTransaction::query()
                ->where('user_id', $userId)
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    public function getTransactions(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return PointTransaction::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getActiveRewards()
    {
        return PointReward::query()
            ->where('status', 'active')
            ->orderBy('required_points')
            ->get();
    }

    public function getActiveVouchers(int $userId)
    {
        return UserVoucher::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('expires_at')
            ->get();
    }

    public function redeemReward(int $userId, int $rewardId): array
    {
        return DB::transaction(function () use ($userId, $rewardId) {
            $balance = UserPointBalance::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = $this->getOrCreateBalance($userId);
                $balance->refresh();
            }

            $reward = PointReward::query()
                ->where('id', $rewardId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $reward) {
                throw new InvalidArgumentException('Phần thưởng không tồn tại hoặc đã ngừng áp dụng.');
            }

            if ($balance->available_points < $reward->required_points) {
                throw new InvalidArgumentException('Bạn chưa đủ điểm để đổi phần thưởng này.');
            }

            if ($reward->usage_limit_per_user > 0) {
                $redeemedCount = UserVoucher::query()
                    ->where('user_id', $userId)
                    ->where('point_reward_id', $reward->id)
                    ->count();

                if ($redeemedCount >= $reward->usage_limit_per_user) {
                    throw new InvalidArgumentException('Bạn đã đổi tối đa số lần cho phần thưởng này.');
                }
            }

            $balance->available_points -= $reward->required_points;
            $balance->lifetime_spent += $reward->required_points;
            $balance->save();

            PointTransaction::query()->create([
                'user_id' => $userId,
                'type' => 'spend',
                'action_key' => 'redeem_reward',
                'points' => -1 * $reward->required_points,
                'balance_after' => $balance->available_points,
                'source_type' => 'point_reward',
                'source_id' => $reward->id,
                'description' => 'Đổi điểm lấy '.$reward->name,
                'status' => 'approved',
            ]);

            $voucher = UserVoucher::query()->create([
                'user_id' => $userId,
                'point_reward_id' => $reward->id,
                'code' => $this->makeVoucherCode($reward),
                'name' => $reward->name,
                'discount_type' => $reward->discount_type,
                'discount_value' => $reward->discount_value,
                'max_discount_amount' => $reward->max_discount_amount,
                'min_order_amount' => $reward->min_order_amount,
                'expires_at' => now()->addDays($reward->expires_in_days),
                'status' => 'active',
            ]);

            $this->notifyUser(
                $userId,
                'point_voucher_redeemed',
                'Đổi voucher thành công',
                "Bạn đã dùng {$reward->required_points} điểm để đổi voucher {$voucher->code}.",
                [
                    'reward_id' => $reward->id,
                    'voucher_id' => $voucher->id,
                    'voucher_code' => $voucher->code,
                    'points_spent' => $reward->required_points,
                ]
            );

            return [
                'balance' => $balance->refresh(),
                'voucher' => $voucher,
            ];
        });
    }

    public function awardPoints(
        int $userId,
        string $actionKey,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $description = null,
        bool $forceApproved = false
    ): ?PointTransaction {
        return DB::transaction(function () use ($userId, $actionKey, $sourceType, $sourceId, $description, $forceApproved) {
            $rule = PointRule::query()
                ->where('action_key', $actionKey)
                ->where('status', 'active')
                ->first();

            if (! $rule || $rule->points <= 0) {
                return null;
            }

            if ($sourceType && $sourceId) {
                $exists = PointTransaction::query()
                    ->where('user_id', $userId)
                    ->where('type', 'earn')
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->exists();

                if ($exists) {
                    return null;
                }
            }

            if ($rule->max_per_day !== null) {
                $earnedToday = PointTransaction::query()
                    ->where('user_id', $userId)
                    ->where('type', 'earn')
                    ->where('action_key', $actionKey)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();

                if ($earnedToday >= $rule->max_per_day) {
                    return null;
                }
            }

            $balance = UserPointBalance::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = $this->getOrCreateBalance($userId);
                $balance->refresh();
            }

            $status = ($forceApproved || ! $rule->requires_approval) ? 'approved' : 'pending';
            if ($status === 'approved') {
                $balance->available_points += $rule->points;
                $balance->lifetime_earned += $rule->points;
                $balance->save();
            }

            $transaction = PointTransaction::query()->create([
                'user_id' => $userId,
                'type' => 'earn',
                'action_key' => $actionKey,
                'points' => $rule->points,
                'balance_after' => $balance->available_points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description ?? $rule->name,
                'status' => $status,
            ]);

            if ($status === 'approved') {
                $this->notifyUser(
                    $userId,
                    'point_earned',
                    'Bạn vừa nhận được điểm thưởng',
                    "Bạn được cộng {$rule->points} điểm: ".($description ?? $rule->name).'.',
                    [
                        'transaction_id' => $transaction->id,
                        'action_key' => $actionKey,
                        'points' => $rule->points,
                        'balance_after' => $balance->available_points,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                    ]
                );
            }

            return $transaction;
        });
    }

    public function getOrCreateBalance(int $userId): UserPointBalance
    {
        return UserPointBalance::query()->firstOrCreate(
            ['user_id' => $userId],
            ['available_points' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0]
        );
    }

    private function makeVoucherCode(PointReward $reward): string
    {
        do {
            $code = 'DTV-'.Str::upper($reward->code).'-'.Str::upper(Str::random(6));
        } while (UserVoucher::query()->where('code', $code)->exists());

        return $code;
    }

    private function notifyUser(int $userId, string $type, string $title, string $content, array $data = []): void
    {
        Notification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'data' => $data,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
