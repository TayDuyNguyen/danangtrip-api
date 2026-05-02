<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\District\IndexDistrictRequest;
use App\Services\DistrictService;
use Illuminate\Http\JsonResponse;

/**
 * Class DistrictController
 * Returns static list of districts in Da Nang city.
 * (Trả về danh sách quận/huyện tĩnh của thành phố Đà Nẵng)
 */
final class DistrictController extends Controller
{
    public function __construct(
        protected DistrictService $districtService
    ) {}

    /**
     * Get the list of districts in Da Nang.
     * (Lấy danh sách quận/huyện tại Đà Nẵng)
     */
    public function index(IndexDistrictRequest $request): JsonResponse
    {
        return $this->success($this->districtService->listAll());
    }
}
