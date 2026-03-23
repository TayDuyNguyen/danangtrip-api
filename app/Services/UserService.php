<?php

namespace App\Services;

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
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * UserService constructor.
     * (Khởi tạo UserService)
     *
     * @return void
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
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
                'status' => 200,
                'data' => $users,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
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
                    'status' => 404,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => 200,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
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
                'status' => 201,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to create user',
            ];
        }
    }

    /**
     * Update an existing user.
     * (Cập nhật thông tin người dùng)
     */
    public function updateUser(int $id, array $data): array
    {
        try {
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $updated = $this->userRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => 404,
                    'message' => 'User not found',
                ];
            }

            $user = $this->userRepository->find($id);

            return [
                'status' => 200,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to update user',
            ];
        }
    }

    /**
     * Delete a user.
     * (Xóa một người dùng)
     */
    public function deleteUser(int $id): array
    {
        try {
            $deleted = $this->userRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => 404,
                    'message' => 'User not found',
                ];
            }

            return [
                'status' => 200,
                'message' => 'User deleted successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to delete user',
            ];
        }
    }
}
