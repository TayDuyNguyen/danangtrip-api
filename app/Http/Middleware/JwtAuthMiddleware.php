<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class JwtAuthMiddleware
 * Middleware to authenticate requests using JSON Web Tokens.
 * (Middleware xác thực yêu cầu bằng JSON Web Token)
 */
class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     * (Xử lý một yêu cầu đến)
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'code' => 401,
                'message' => 'Token not provided',
            ], 401);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
        } catch (Exception $e) {
            $user = null;
        }

        if (! $user) {
            return response()->json([
                'code' => 401,
                'message' => 'Invalid token',
            ], 401);
        }

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
