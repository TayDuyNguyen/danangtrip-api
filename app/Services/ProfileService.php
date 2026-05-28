<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
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
        protected RatingRepositoryInterface $ratingRepository,
        protected BookingRepositoryInterface $bookingRepository,
        protected LocationRepositoryInterface $locationRepository,
        protected TourRepositoryInterface $tourRepository,
        protected UploadService $uploadService
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

            // Upload new avatar to Cloudinary
            $uploadResult = $this->uploadService->uploadImage($file, 'avatars');
            if ($uploadResult['status'] !== HttpStatusCode::CREATED->value) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to upload avatar to Cloudinary.',
                ];
            }

            $url = $uploadResult['data']['url'];

            // Delete old avatar if exists and it was a local file
            if ($user->avatar && ! str_starts_with($user->avatar, 'http://') && ! str_starts_with($user->avatar, 'https://')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $this->userRepository->update($userId, ['avatar' => $url]);

            $user = $this->userRepository->find($userId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'avatar_url' => $user->avatar_url,
                    'user' => $user,
                ],
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

    /**
     * Delete authenticated user's account permanently.
     * (Xóa vĩnh viễn tài khoản cá nhân)
     */
    public function deleteAccount(int $userId, string $password): array
    {
        try {
            $user = $this->userRepository->find($userId);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found.',
                ];
            }

            // Verify password
            if (! Hash::check($password, $user->password)) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'The confirmation password is incorrect.',
                ];
            }

            // Check for active bookings
            $hasActiveBookings = $this->bookingRepository->hasActiveBookings($userId);

            if ($hasActiveBookings) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'You have active bookings. Please cancel or complete them before deleting your account.',
                ];
            }

            // Dọn dẹp dữ liệu của user trong transaction để đảm bảo toàn vẹn
            DB::transaction(function () use ($userId, $user) {
                // 1. Tìm các rating của user để xóa ảnh vật lý trên disk và lấy location/tour IDs để update stats sau
                $ratings = $this->ratingRepository->getWhere(['user_id' => $userId]);
                $locationIdsToUpdate = [];
                $tourIdsToUpdate = [];

                foreach ($ratings as $rating) {
                    if ($rating->location_id) {
                        $locationIdsToUpdate[] = (int) $rating->location_id;
                    }
                    if ($rating->tour_id) {
                        $tourIdsToUpdate[] = (int) $rating->tour_id;
                    }

                    // Xóa thư mục ảnh rating trên disk nếu có
                    Storage::disk('public')->deleteDirectory('ratings/'.$rating->id);
                }

                // 2. Xóa avatar của user trên disk nếu có và nó không phải URL Cloudinary
                if ($user->avatar && ! str_starts_with($user->avatar, 'http://') && ! str_starts_with($user->avatar, 'https://')) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // 3. Xóa user model (sẽ tự động cascade delete ratings, favorites, notifications, refresh_tokens ở tầng database)
                $this->userRepository->delete($userId);

                // 4. Cập nhật lại stats (avg score và review count) cho các location và tour liên quan
                $uniqueLocationIds = array_unique($locationIdsToUpdate);
                foreach ($uniqueLocationIds as $locId) {
                    $this->locationRepository->updateStats($locId);
                }

                $uniqueTourIds = array_unique($tourIdsToUpdate);
                foreach ($uniqueTourIds as $tId) {
                    $this->tourRepository->updateStats($tId);
                }
            });

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Your account has been deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'An error occurred while deleting your account.',
            ];
        }
    }
}
