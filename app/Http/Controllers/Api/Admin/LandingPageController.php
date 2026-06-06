<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\IndexLandingPageRequest;
use App\Http\Requests\LandingPage\StoreLandingPageRequest;
use App\Http\Requests\LandingPage\UpdateLandingPageRequest;
use App\Http\Requests\LandingPage\UpdateLandingPageStatusRequest;
use App\Services\LandingPageService;
use Illuminate\Http\JsonResponse;

/**
 * Class LandingPageController (Admin)
 * Handles admin API requests for managing landing pages.
 */
final class LandingPageController extends Controller
{
    public function __construct(
        protected LandingPageService $landingPageService
    ) {}

    /**
     * List landing pages with filters/pagination.
     */
    public function index(IndexLandingPageRequest $request): JsonResponse
    {
        $result = $this->landingPageService->adminList($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get landing page detail.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->landingPageService->show($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Create a new landing page.
     */
    public function store(StoreLandingPageRequest $request): JsonResponse
    {
        $result = $this->landingPageService->create($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->success($result['data'], $result['message'] ?? 'Landing page created.', HttpStatusCode::CREATED->value)
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a landing page.
     */
    public function update(UpdateLandingPageRequest $request, int $id): JsonResponse
    {
        $result = $this->landingPageService->update($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Landing page updated.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle landing page status (draft/published).
     */
    public function updateStatus(UpdateLandingPageStatusRequest $request, int $id): JsonResponse
    {
        $result = $this->landingPageService->toggleStatus($id, $request->validated()['status']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Landing page status updated.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a landing page.
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->landingPageService->delete($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Landing page deleted.')
            : $this->error($result['message'], $result['status']);
    }
}
