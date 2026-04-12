<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AvatarProfileRequest;
use App\Http\Requests\Profile\PasswordProfileRequest;
use App\Http\Requests\Profile\RatingsProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ProfileController
 * Handles user profile management API requests.
 * (Xử lý các yêu cầu API quản lý thông tin cá nhân)
 */
final class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get authenticated user profile.
     * (Lấy thông tin cá nhân của người dùng đã xác thực)
     */
    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->profileService->getProfile($userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update user profile information.
     * (Cập nhật thông tin cá nhân)
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->profileService->updateProfile($userId, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Upload and update user avatar.
     * (Upload và cập nhật ảnh đại diện)
     */
    public function updateAvatar(AvatarProfileRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->profileService->updateAvatar($userId, $request->file('avatar'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Change user password.
     * (Thay đổi mật khẩu người dùng)
     */
    public function changePassword(PasswordProfileRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        $result = $this->profileService->changePassword($userId, $data['current_password'], $data['password']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get user rating history.
     * (Lấy lịch sử đánh giá của người dùng)
     */
    public function ratings(RatingsProfileRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->profileService->getRatingHistory($userId, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
