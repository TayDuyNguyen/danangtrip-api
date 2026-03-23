<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthService
 * Handles business logic related to authentication.
 * (Xử lý logic nghiệp vụ liên quan đến xác thực)
 */
class AuthService
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * AuthService constructor.
     * (Khởi tạo AuthService)
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user.
     * (Đăng ký người dùng mới)
     */
    public function register(array $data): array
    {
        try {
            $data['password'] = Hash::make($data['password']);

            $user = $this->userRepository->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'role' => $data['role'] ?? 'user',
            ]);

            // $token = Auth::guard('api')->login($user);

            return [
                'status' => 200,
                'data' => $user,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Register failed',
            ];
        }
    }

    /**
     * Authenticate a user and return token.
     * (Xác thực người dùng và trả về token)
     */
    public function login(string $email, string $password): ?array
    {
        try {
            if (! $token = Auth::guard('api')->attempt(['email' => $email, 'password' => $password])) {
                return [
                    'status' => 401,
                    'message' => 'Invalid credentials',
                ];
            }

            return [
                'status' => 200,
                'data' => [
                    'token' => $token,
                    'user' => Auth::guard('api')->user(),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Login failed',
            ];
        }
    }

    /**
     * Invalidate user token (Logout).
     * (Vô hiệu hóa token người dùng - Đăng xuất)
     */
    public function logout(): array
    {
        try {
            if (Auth::guard('api')->check()) {
                Auth::guard('api')->logout();
            }

            return [
                'status' => 200,
                'message' => 'Logout successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Logout failed',
            ];
        }
    }
}
