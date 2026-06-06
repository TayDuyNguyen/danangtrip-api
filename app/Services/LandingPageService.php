<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LandingPageRepositoryInterface;
use Exception;
use Illuminate\Support\Str;

/**
 * Class LandingPageService
 * (Dịch vụ xử lý nghiệp vụ Landing Pages)
 */
final class LandingPageService
{
    public function __construct(
        protected LandingPageRepositoryInterface $landingPageRepository
    ) {}

    /**
     * Admin list landing pages with filters.
     */
    public function adminList(array $filters): array
    {
        try {
            $paginator = $this->landingPageRepository->adminList($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $paginator,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve landing pages.',
            ];
        }
    }

    /**
     * Get a single landing page detail (admin).
     */
    public function show(int $id): array
    {
        try {
            $landingPage = $this->landingPageRepository->find($id);

            if (! $landingPage) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Landing page not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $landingPage,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve landing page.',
            ];
        }
    }

    /**
     * Create a new landing page.
     */
    public function create(array $data): array
    {
        try {
            // Normalize slug
            $data['slug'] = Str::slug($data['slug'] ?? $data['title']);

            // Check slug uniqueness
            if ($this->landingPageRepository->findBySlug($data['slug'])) {
                return [
                    'status' => HttpStatusCode::VALIDATION_ERROR->value,
                    'message' => 'Landing page slug already exists.',
                ];
            }

            $landingPage = $this->landingPageRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $landingPage,
                'message' => 'Landing page created successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create landing page.',
            ];
        }
    }

    /**
     * Update an existing landing page.
     */
    public function update(int $id, array $data): array
    {
        try {
            $landingPage = $this->landingPageRepository->find($id);

            if (! $landingPage) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Landing page not found.',
                ];
            }

            // Normalize slug if provided
            if (isset($data['slug'])) {
                $data['slug'] = Str::slug($data['slug']);

                // Check uniqueness only if slug changed
                if ($data['slug'] !== $landingPage->slug) {
                    $existing = $this->landingPageRepository->findBySlug($data['slug']);
                    if ($existing && $existing->id !== $id) {
                        return [
                            'status' => HttpStatusCode::VALIDATION_ERROR->value,
                            'message' => 'Landing page slug already exists.',
                        ];
                    }
                }
            }

            $this->landingPageRepository->update($id, $data);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->landingPageRepository->find($id),
                'message' => 'Landing page updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update landing page.',
            ];
        }
    }

    /**
     * Toggle landing page status.
     */
    public function toggleStatus(int $id, string $status): array
    {
        try {
            $landingPage = $this->landingPageRepository->find($id);

            if (! $landingPage) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Landing page not found.',
                ];
            }

            $this->landingPageRepository->toggleStatus($id, $status);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Landing page status updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update landing page status.',
            ];
        }
    }

    /**
     * Delete a landing page.
     */
    public function delete(int $id): array
    {
        try {
            $landingPage = $this->landingPageRepository->find($id);

            if (! $landingPage) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Landing page not found.',
                ];
            }

            $this->landingPageRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Landing page deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete landing page.',
            ];
        }
    }
}
