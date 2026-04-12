<?php

namespace App\Http\Middleware;

use App\Enums\HttpStatusCode;
use Closure;
use Exception;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

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
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'message' => 'Token not provided',
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
        } catch (Exception $e) {
            $user = null;
        }

        if (! $user) {
            return response()->json([
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'message' => 'Invalid token',
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
