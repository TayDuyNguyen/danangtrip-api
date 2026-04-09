<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

/**
 * Class TagController
 * (Điều khiển các hoạt động cho Tag - Admin)
 */
final class TagController extends Controller
{
    /**
     * TagController constructor.
     */
    public function __construct(
        protected TagService $tagService
    ) {}

    /**
     * Store a new tag.
     * (Tạo tag mới)
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $result = $this->tagService->createTag($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Remove the specified tag.
     * (Xóa tag)
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->tagService->deleteTag($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
