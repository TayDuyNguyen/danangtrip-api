<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Validations\UserValidation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class UserController
 * Handles API requests for user-related actions.
 * (Xử lý các yêu cầu API cho các hành động liên quan đến người dùng)
 */
class UserController extends Controller
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * UserController constructor.
     * (Khởi tạo UserController)
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of all users.
     * (Hiển thị danh sách tất cả người dùng)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $result = $this->userService->getAllUsers();
        return $this->success(['users' => $result['data']]);
    }

    /**
     * Display the specified user.
     * (Hiển thị thông tin người dùng cụ thể)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->userService->getUserById($id);

        if ($result['status'] == 200) {
            return $this->success(['user' => $result['data']]);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Store a newly created user in storage.
     * (Lưu trữ người dùng mới tạo vào bộ nhớ)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = UserValidation::validateStore($request);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->createUser($validator->validated());

        if ($result['status'] == 201) {
            return $this->created(['user' => $result['data']], 'User created successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Update the specified user in storage.
     * (Cập nhật thông tin người dùng cụ thể trong bộ nhớ)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = UserValidation::validateUpdate($request, $id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->userService->updateUser($id, $validator->validated());

        if ($result['status'] == 200) {
            return $this->success(['user' => $result['data']], 'User updated successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Remove the specified user from storage.
     * (Xóa người dùng cụ thể khỏi bộ nhớ)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->userService->deleteUser($id);

        if ($result['status'] == 200) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
