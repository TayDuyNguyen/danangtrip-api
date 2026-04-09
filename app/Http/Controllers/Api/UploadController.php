<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upload\DeleteImageUploadRequest;
use App\Http\Requests\Upload\UploadImagesUploadRequest;
use App\Http\Requests\Upload\UploadImageUploadRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;

/**
 * Class UploadController
 * Handles image upload and deletion.
 * (Xử lý việc tải lên và xóa ảnh)
 */
final class UploadController extends Controller
{
    /**
     * UploadController constructor.
     * (Khởi tạo UploadController)
     */
    public function __construct(protected UploadService $uploadService) {}

    /**
     * Upload a single image.
     * (Tải lên một ảnh)
     */
    public function uploadImage(UploadImageUploadRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->uploadService->uploadImage($request->file('image'), $data['folder'] ?? null);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Upload multiple images.
     * (Tải lên nhiều ảnh)
     */
    public function uploadImages(UploadImagesUploadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->uploadService->uploadImages(
            $data['images'],
            $data['folder'] ?? null
        );

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete an image from Cloudinary.
     * (Xóa một ảnh từ Cloudinary)
     */
    public function deleteImage(DeleteImageUploadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->uploadService->deleteImage($data['public_id']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
