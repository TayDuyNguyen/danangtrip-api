<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PointController extends Controller
{
    public function __construct(
        private readonly PointService $pointService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return $this->success(
            $this->pointService->getOverview((int) $request->user()->id),
            'Point overview retrieved successfully.'
        );
    }

    public function transactions(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 50);

        return $this->success(
            $this->pointService->getTransactions((int) $request->user()->id, $perPage),
            'Point transactions retrieved successfully.'
        );
    }

    public function rewards(): JsonResponse
    {
        return $this->success(
            $this->pointService->getActiveRewards(),
            'Point rewards retrieved successfully.'
        );
    }

    public function vouchers(Request $request): JsonResponse
    {
        return $this->success(
            $this->pointService->getActiveVouchers((int) $request->user()->id),
            'User vouchers retrieved successfully.'
        );
    }

    public function redeem(Request $request, int $id): JsonResponse
    {
        try {
            return $this->created(
                $this->pointService->redeemReward((int) $request->user()->id, $id),
                'Reward redeemed successfully.'
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage());
        }
    }
}
