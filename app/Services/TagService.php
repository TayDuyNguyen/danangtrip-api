<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\TagRepositoryInterface;
use Exception;

/**
 * Class TagService
 * (Dịch vụ xử lý các hoạt động cho Tag)
 */
final class TagService
{
    /**
     * TagService constructor.
     * (Khởi tạo TagService)
     */
    public function __construct(
        protected TagRepositoryInterface $tagRepository
    ) {}

    /**
     * Get all tags.
     * (Lấy tất cả danh mục thẻ)
     */
    public function getAllTags(array $filters): array
    {
        try {
            $type = $filters['type'] ?? null;
            $tags = $this->tagRepository->getAll($type);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $tags,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve tags.',
            ];
        }
    }

    /**
     * Create a new tag (Admin).
     * (Tạo thẻ mới - Admin)
     */
    public function createTag(array $data): array
    {
        try {
            $tag = $this->tagRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $tag,
                'message' => 'Tag created successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create tag.',
            ];
        }
    }

    /**
     * Delete a tag (Admin).
     * (Xóa thẻ - Admin)
     */
    public function deleteTag(int $id): array
    {
        try {
            $tag = $this->tagRepository->find($id);

            if (! $tag) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tag not found.',
                ];
            }

            $this->tagRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Tag deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete tag.',
            ];
        }
    }
}
