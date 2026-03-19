<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RoleMiddleware
 * Middleware to check if the authenticated user has any of the required roles.
 * (Middleware kiểm tra xem người dùng đã xác thực có vai trò nào trong các vai trò yêu cầu không)
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * (Xử lý một yêu cầu đến)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[] ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Get the authenticated user (must be used after jwt.auth)
        // (Lấy người dùng đã xác thực - phải được sử dụng sau middleware jwt.auth)
        $user = $request->user();

        // Check if user exists and has a role that matches any of the required roles
        // (Kiểm tra xem người dùng có tồn tại và có vai trò khớp với bất kỳ vai trò yêu cầu nào không)
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'code' => 403,
                'message' => 'Access denied. Your role (' . ($user->role ?? 'none') . ') does not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
