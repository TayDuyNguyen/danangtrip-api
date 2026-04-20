<?php

namespace App\Http\Middleware;

use App\Enums\HttpStatusCode;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
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
                'error' => 'TOKEN_NOT_PROVIDED',
                'message' => 'Token not provided',
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json([
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'error' => 'TOKEN_EXPIRED',
                'message' => 'Token expired',
            ], HttpStatusCode::UNAUTHORIZED->value);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'error' => 'TOKEN_INVALID',
                'message' => 'Invalid token',
            ], HttpStatusCode::UNAUTHORIZED->value);
        } catch (JWTException $e) {
            return response()->json([
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'error' => 'TOKEN_INVALID',
                'message' => 'Invalid token',
            ], HttpStatusCode::UNAUTHORIZED->value);
        } catch (\Throwable $e) {
            $user = null;
        }

        if (! $user) {
            return response()->json([
                'code' => HttpStatusCode::UNAUTHORIZED->value,
                'error' => 'TOKEN_INVALID',
                'message' => 'Invalid token',
            ], HttpStatusCode::UNAUTHORIZED->value);
        }

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
