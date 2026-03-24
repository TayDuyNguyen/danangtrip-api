<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\PointTransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class PointService
 * (Dịch vụ xử lý các hoạt động liên quan đến điểm thưởng)
 */
final class PointService
{
    /**
     * PointService constructor.
     * (Khởi tạo PointService)
     */
    public function __construct(
        protected PointTransactionRepositoryInterface $pointTransactionRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user point balance.
     * (Lấy số dư điểm của người dùng)
     */
    public function getBalance(int $userId): array
    {
        try {
            $user = $this->userRepository->find($userId);

            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'point_balance' => (int) $user->point_balance,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get point balance.',
            ];
        }
    }

    /**
     * Get user point transaction history.
     * (Lấy lịch sử giao dịch điểm của người dùng)
     */
    public function getTransactions(int $userId, array $filters): array
    {
        try {
            $transactions = $this->pointTransactionRepository->getByUserPaginated($userId, $filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $transactions,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get transaction history.',
            ];
        }
    }

    /**
     * Process point purchase.
     * (Xử lý nạp điểm)
     */
    public function purchasePoints(int $userId, array $data): array
    {
        return DB::transaction(function () use ($userId, $data) {
            try {
                $user = $this->userRepository->find($userId);

                if (! $user) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'User not found.',
                    ];
                }

                $amount = $data['amount'];
                $balanceBefore = (int) $user->point_balance;

                // 1. Update user balance
                $this->userRepository->incrementPointBalance($userId, $amount);

                // 2. Refresh user to get new balance
                $user->refresh();
                $balanceAfter = (int) $user->point_balance;

                // 3. Create transaction record
                $transaction = $this->pointTransactionRepository->create([
                    'user_id' => $userId,
                    'transaction_code' => $this->pointTransactionRepository->generateTransactionCode(),
                    'type' => 'purchase',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'payment_method' => $data['payment_method'],
                    'status' => 'completed', // Assuming payment is already successful
                    'description' => 'Purchase points via '.strtoupper($data['payment_method']),
                    'created_at' => now(),
                ]);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'transaction' => $transaction,
                        'new_balance' => $balanceAfter,
                    ],
                    'message' => 'Points purchased successfully.',
                ];
            } catch (Exception $e) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to process point purchase.',
                ];
            }
        });
    }
}
