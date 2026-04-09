<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class UploadService
 * (Dịch vụ xử lý việc tải lên)
 */
final class UploadService
{
    /**
     * Upload a single image to Cloudinary.
     * (Tải lên một ảnh lên Cloudinary)
     */
    public function uploadImage(UploadedFile $file, ?string $folder = null): array
    {
        try {
            $uploaded = method_exists($file, 'storeOnCloudinary')
                ? $file->storeOnCloudinary($folder)
                : Cloudinary::uploadApi()->upload($file->getRealPath(), [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'public_id' => uniqid(),
                ]);

            $url = method_exists($uploaded, 'getSecurePath') ? $uploaded->getSecurePath() : ($uploaded['secure_url'] ?? null);
            $publicId = method_exists($uploaded, 'getPublicId') ? $uploaded->getPublicId() : ($uploaded['public_id'] ?? null);
            $assetId = method_exists($uploaded, 'getAssetId') ? $uploaded->getAssetId() : ($uploaded['asset_id'] ?? null);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => [
                    'url' => $url,
                    'public_id' => $publicId,
                    'asset_id' => $assetId,
                ],
                'message' => 'Image uploaded successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to upload image.',
            ];
        }
    }

    /**
     * Upload multiple images to Cloudinary.
     * (Tải lên nhiều ảnh lên Cloudinary)
     */
    public function uploadImages(array $files, ?string $folder = null): array
    {
        try {
            $validFiles = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

            if (count($validFiles) === 0) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'No valid images provided.',
                ];
            }

            $results = [];
            foreach (array_slice($validFiles, 0, 10) as $file) {
                $safeFolder = is_string($folder) && trim($folder) !== '' ? $folder : null;

                $uploadOptions = [
                    'resource_type' => 'image',
                    'public_id' => Str::uuid()->toString(),
                ];

                if ($safeFolder !== null) {
                    $uploadOptions['folder'] = $safeFolder;
                }

                $uploaded = method_exists($file, 'storeOnCloudinary')
                    ? $file->storeOnCloudinary($safeFolder)
                    : Cloudinary::uploadApi()->upload($file->getRealPath(), $uploadOptions);

                $url = method_exists($uploaded, 'getSecurePath') ? $uploaded->getSecurePath() : ($uploaded['secure_url'] ?? null);
                $publicId = method_exists($uploaded, 'getPublicId') ? $uploaded->getPublicId() : ($uploaded['public_id'] ?? null);
                $assetId = method_exists($uploaded, 'getAssetId') ? $uploaded->getAssetId() : ($uploaded['asset_id'] ?? null);

                $results[] = [
                    'url' => $url,
                    'public_id' => $publicId,
                    'asset_id' => $assetId,
                ];
            }

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => [
                    'items' => $results,
                ],
                'message' => 'Images uploaded successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to upload images.',
            ];
        }
    }

    /**
     * Delete an image from Cloudinary using its public ID.
     * (Xóa một ảnh từ Cloudinary bằng public ID của nó)
     */
    public function deleteImage(string $publicId): array
    {
        try {
            $result = null;
            if (method_exists(Cloudinary::getFacadeRoot(), 'destroy')) {
                $result = Cloudinary::destroy($publicId);
            } else {
                $result = Cloudinary::uploadApi()->destroy($publicId);
            }

            $ok = is_array($result) ? (($result['result'] ?? '') === 'ok') : (property_exists($result, 'result') ? $result->result === 'ok' : true);

            if (! $ok) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Failed to delete image from Cloudinary.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Image deleted successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete image.',
            ];
        }
    }
}
