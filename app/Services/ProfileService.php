<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Class ProfileService
 * Handles business logic for user profile management.
 * (Xử lý logic nghiệp vụ cho quản lý thông tin cá nhân)
 */
final class ProfileService
{
    /**
     * ProfileService constructor.
     * (Khởi tạo ProfileService)
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected RatingRepositoryInterface $ratingRepository
    ) {}

    /**
     * Get user profile.
     * (Lấy thông tin cá nhân)
     */
    public function getProfile(int $userId): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to fetch profile.',
            ];
        }
    }

    /**
     * Update user profile.
     * (Cập nhật thông tin cá nhân)
     */
    public function updateProfile(int $userId, array $data): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            $updated = $this->userRepository->update($userId, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update profile.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->userRepository->find($userId),
                'message' => 'Profile updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'An error occurred while updating profile.',
            ];
        }
    }

    /**
     * Upload and update user avatar.
     * (Upload và cập nhật ảnh đại diện)
     *
     * @param  mixed  $file
     */
    public function updateAvatar(int $userId, $file): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $path = $file->store('avatars', 'public');

            $this->userRepository->update($userId, ['avatar' => $path]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['avatar_url' => asset('storage/'.$path)],
                'message' => 'Avatar updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to upload avatar.',
            ];
        }
    }

    /**
     * Change user password.
     * (Thay đổi mật khẩu)
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            if (! Hash::check($currentPassword, $user->password)) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'The current password is incorrect.',
                ];
            }

            $this->userRepository->update($userId, [
                'password' => Hash::make($newPassword),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Password changed successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to change password.',
            ];
        }
    }

    /**
     * Get user rating history.
     * (Lấy lịch sử đánh giá của người dùng)
     */
    public function getRatingHistory(int $userId, array $filters): array
    {
        try {
            $ratings = $this->ratingRepository->getByUserPaginated($userId, $filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $ratings,
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to fetch rating history.',
            ];
        }
    }
}
