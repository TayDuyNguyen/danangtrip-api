<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\IndexTagRequest;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;

/**
 * Class TagController
 * (Điều khiển các hoạt động cho Tag)
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
     * Display a listing of all tags.
     * (Hiển thị danh sách tất cả tags)
     */
    public function index(IndexTagRequest $request): JsonResponse
    {
        $result = $this->tagService->getAllTags($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
