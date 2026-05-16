<?php

namespace App\Http\Middleware;

use App\Enums\HttpStatusCode;
use App\Support\ApiErrorResponse;
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
            return $this->unauthorizedResponse('Token not provided');
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expired');
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Invalid token');
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Invalid token');
        } catch (\Throwable $e) {
            $user = null;
        }

        if (! $user) {
            return $this->unauthorizedResponse('Invalid token');
        }

        $request->merge(['auth_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorizedResponse(string $message): Response
    {
        $code = HttpStatusCode::UNAUTHORIZED->value;

        return response()->json(ApiErrorResponse::make($code, $message), $code);
    }
}
