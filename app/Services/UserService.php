<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Class UserService
 * Handles business logic related to users.
 * (Xử lý logic nghiệp vụ liên quan đến người dùng)
 */
class UserService
{
    /**
     * UserService constructor.
     * (Khởi tạo UserService)
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated users with filters.
     * (Lấy danh sách người dùng có phân trang và bộ lọc)
     */
    public function getAdminUsers(array $filters): array
    {
        try {
            $users = $this->userRepository->getUsersPaginated($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $users,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get users',
            ];
        }
    }

    /**
     * Get a specific user by ID with stats.
     * (Lấy thông tin một người dùng cụ thể theo ID kèm thống kê)
     */
    public function getUserDetail(int $id): array
    {
        try {
            $user = $this->userRepository->getUserWithStats($id);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get user details',
            ];
        }
    }

    /**
     * Update user status.
     * (Cập nhật trạng thái người dùng)
     */
    public function updateStatus(int $id, string $status, int $currentAdminId): array
    {
        try {
            // Safety check: Prevent admin from changing their own status (TC35 protection)
            // (Kiểm tra an toàn: Ngăn admin tự thay đổi trạng thái của mình)
            if ($id === $currentAdminId) {
                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'message' => 'You cannot change your own status.',
                ];
            }

            $updated = $this->userRepository->update($id, ['status' => $status]);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'User status updated successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update user status',
            ];
        }
    }

    /**
     * Update user role.
     * (Cập nhật vai trò người dùng)
     */
    public function updateRole(int $id, string $role, int $currentAdminId): array
    {
        try {
            // Safety check: Prevent admin from changing their own role (TC35 protection)
            // (Kiểm tra an toàn: Ngăn admin tự thay đổi vai trò của mình)
            if ($id === $currentAdminId) {
                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'message' => 'You cannot change your own role.',
                ];
            }

            $updated = $this->userRepository->update($id, ['role' => $role]);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'User role updated successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update user role',
            ];
        }
    }

    /**
     * Get all users.
     * (Lấy danh sách tất cả người dùng)
     */
    public function getAllUsers(): array
    {
        try {
            $users = $this->userRepository->all();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $users,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get all users',
            ];
        }
    }

    /**
     * Get a specific user by ID.
     * (Lấy thông tin một người dùng cụ thể theo ID)
     */
    public function getUserById(int $id): array
    {
        try {
            $user = $this->userRepository->find($id);
            if (! $user) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get user by ID',
            ];
        }
    }

    /**
     * Create a new user.
     * (Tạo một người dùng mới)
     */
    public function createUser(array $data): array
    {
        try {
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->userRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create user',
            ];
        }
    }

    /**
     * Update an existing user.
     * (Cập nhật thông tin người dùng)
     */
    public function updateUser(int $id, array $data, ?int $currentAdminId = null): array
    {
        try {
            // Safety check: Prevent admin from downgrading their own role/status via generic update
            // (Kiểm tra an toàn: Ngăn admin tự hạ quyền/trạng thái của mình qua cập nhật chung)
            if ($currentAdminId && $id === $currentAdminId) {
                if (isset($data['role']) || isset($data['status'])) {
                    return [
                        'status' => HttpStatusCode::FORBIDDEN->value,
                        'message' => 'You cannot change your own role or status.',
                    ];
                }
            }

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $updated = $this->userRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            $user = $this->userRepository->find($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update user',
            ];
        }
    }

    /**
     * Delete a user by ID.
     * (Xóa người dùng theo ID)
     */
    public function deleteUser(int $id, int $currentAdminId): array
    {
        try {
            // Safety check: Prevent admin from deleting their own account (TC35 protection)
            // (Kiểm tra an toàn: Ngăn admin tự xóa tài khoản của mình)
            if ($id === $currentAdminId) {
                return [
                    'status' => HttpStatusCode::FORBIDDEN->value,
                    'message' => 'You cannot delete your own account.',
                ];
            }

            $deleted = $this->userRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'User deleted successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete user',
            ];
        }
    }
}
