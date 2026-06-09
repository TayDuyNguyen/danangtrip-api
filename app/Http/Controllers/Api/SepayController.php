<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Services\SepayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SepayController extends Controller
{
    public function __construct(protected SepayPaymentService $sepayPaymentService) {}

    public function ipn(Request $request): JsonResponse
    {
        $result = $this->sepayPaymentService->handleIpn(
            $request->all(),
            $request->headers->all(),
            $request->getContent()
        );

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
