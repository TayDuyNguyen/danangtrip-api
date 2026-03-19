<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Validations\AuthValidation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class AuthController
 * Handles user authentication requests.
 * (Xử lý các yêu cầu xác thực người dùng)
 */
class AuthController extends Controller
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * AuthController constructor.
     * (Khởi tạo AuthController)
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     * (Đăng ký người dùng mới)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = AuthValidation::validateRegister($request);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->authService->register($validator->validated());
        if ($result['status'] == 200) {
            return $this->created($result['data'], 'User registered successfully');
        } else {
            return $this->error('User registered failed', $result['status']);
        }
    }

    /**
     * Authenticate a user and return token.
     * (Xác thực người dùng và trả về token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = AuthValidation::validateLogin($request);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $validated = $validator->validated();

        $result = $this->authService->login($validated['email'], $validated['password']);

        if ($result['status'] == 200) {
            return $this->success($result['data'], 'Login successful');
        } else {
            return $this->unauthorized($result['message']);
        }
    }

    /**
     * Invalidate user token (Logout).
     * (Vô hiệu hóa token người dùng - Đăng xuất)
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $result = $this->authService->logout();

        if ($result['status'] == 200) {
            return $this->success(null, 'Logged out successfully');
        } else {
            return $this->unauthorized($result['message']);
        }
    }

    /**
     * Get authenticated user.
     * (Lấy thông tin người dùng đã xác thực)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }
}
