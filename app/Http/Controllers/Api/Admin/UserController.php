<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
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
     * Display a listing of all users with filters.
     * (Hiển thị danh sách tất cả người dùng với bộ lọc)
     */
    public function index(Request $request): JsonResponse
    {
        $validator = UserValidation::validateIndex($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->getAdminUsers($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display user detail with stats.
     * (Hiển thị chi tiết người dùng kèm thống kê)
     */
    public function show(int $id): JsonResponse
    {
        $validate = UserValidation::validateShow($id);
        if ($validate->fails()) {
            return $this->validation_error($validate->errors());
        }

        $result = $this->userService->getUserDetail($id);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle user status (active/banned).
     * (Bật/tắt trạng thái người dùng - active/banned)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = UserValidation::validateUpdateStatus($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $currentAdminId = $request->user()->id;
        $result = $this->userService->updateStatus($id, $request->input('status'), $currentAdminId);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Change user role.
     * (Thay đổi vai trò người dùng)
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $validator = UserValidation::validateUpdateRole($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $currentAdminId = $request->user()->id;
        $result = $this->userService->updateRole($id, $request->input('role'), $currentAdminId);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete user account.
     * (Xóa tài khoản người dùng)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $validator = UserValidation::validateDelete($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $currentAdminId = $request->user()->id;
        $result = $this->userService->deleteUser($id, $currentAdminId);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
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

        return $result['status'] == HttpStatusCode::CREATED->value
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

        $currentAdminId = $request->user()->id;
        $result = $this->userService->updateUser($id, $validator->validated(), $currentAdminId);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success(['user' => $result['data']], 'User updated successfully')
            : $this->error($result['message'], $result['status']);
    }
}
