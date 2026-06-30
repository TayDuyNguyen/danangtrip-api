<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Services\LandingPageService;
use Illuminate\Http\JsonResponse;

/**
 * Handles public landing page requests.
 */
final class LandingPageController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService
    ) {}

    /**
     * Display a published landing page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $result = $this->landingPageService->publicShow($slug);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
