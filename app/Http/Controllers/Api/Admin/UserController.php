<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Validations\UserValidation;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class UserController
 * Handles administrative API requests for users.
 * (Xử lý các yêu cầu API quản trị cho người dùng)
 */
final class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of all users.
     * (Hiển thị danh sách tất cả người dùng)
     */
    public function index(): JsonResponse
    {
        $result = $this->userService->getAllUsers();

        return $this->success(['users' => $result['data']]);
    }

    /**
     * Display user detail.
     * (Hiển thị chi tiết người dùng)
     */
    public function show(int $id): JsonResponse
    {
        $validate = UserValidation::validateShow($id);
        if ($validate->fails()) {
            return $this->validation_error($validate->errors());
        }

        $result = $this->userService->getUserById($id);

        return $result['status'] == 200
            ? $this->success(['user' => $result['data']])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Store a new user.
     * (Tạo người dùng mới)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = UserValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->createUser($validator->validated());

        return $result['status'] == 201
            ? $this->created(['user' => $result['data']], 'User created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update user detail.
     * (Cập nhật thông tin người dùng)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = UserValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->updateUser($id, $validator->validated());

        return $result['status'] == 200
            ? $this->success(['user' => $result['data']], 'User updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Remove user.
     * (Xóa người dùng)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = UserValidation::validateDelete($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->deleteUser($id);

        return $result['status'] == 200
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
