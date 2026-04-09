<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateRoleUserRequest;
use App\Http\Requests\User\UpdateStatusUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

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
    public function index(IndexUserRequest $request): JsonResponse
    {
        $result = $this->userService->getAdminUsers($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display user detail with stats.
     * (Hiển thị chi tiết người dùng kèm thống kê)
     */
    public function show(ShowUserRequest $request, int $id): JsonResponse
    {
        $result = $this->userService->getUserDetail($id);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle user status (active/banned).
     * (Bật/tắt trạng thái người dùng - active/banned)
     */
    public function updateStatus(UpdateStatusUserRequest $request, int $id): JsonResponse
    {
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
    public function updateRole(UpdateRoleUserRequest $request, int $id): JsonResponse
    {
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
    public function destroy(DeleteUserRequest $request, int $id): JsonResponse
    {
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
    public function store(StoreUserRequest $request): JsonResponse
    {
        $result = $this->userService->createUser($request->validated());

        return $result['status'] == HttpStatusCode::CREATED->value
            ? $this->created(['user' => $result['data']], 'User created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update user detail.
     * (Cập nhật thông tin người dùng)
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $currentAdminId = $request->user()->id;
        $result = $this->userService->updateUser($id, $request->validated(), $currentAdminId);

        return $result['status'] == HttpStatusCode::SUCCESS->value
            ? $this->success(['user' => $result['data']], 'User updated successfully')
            : $this->error($result['message'], $result['status']);
    }
}
