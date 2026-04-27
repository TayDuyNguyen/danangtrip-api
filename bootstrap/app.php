<?php

use App\Enums\HttpStatusCode;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $code = HttpStatusCode::INTERNAL_SERVER_ERROR->value;
                $message = config('app.debug') ? $e->getMessage() : 'Internal Server Error';
                if (! $message) {
                    $message = 'Internal Server Error';
                }
                $errors = null;

                if ($e instanceof ValidationException) {
                    $code = HttpStatusCode::VALIDATION_ERROR->value;
                    $message = 'Validation failed';
                    $errors = $e->errors();
                } elseif (
                    $e instanceof AuthenticationException ||
                    $e instanceof TokenInvalidException ||
                    $e instanceof TokenExpiredException ||
                    $e instanceof JWTException ||
                    $e instanceof UnauthorizedHttpException
                ) {
                    $code = HttpStatusCode::UNAUTHORIZED->value;
                    $message = 'Unauthenticated';
                } elseif ($e instanceof AccessDeniedHttpException) {
                    $code = HttpStatusCode::FORBIDDEN->value;
                    $message = 'Forbidden';
                } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    $code = HttpStatusCode::NOT_FOUND->value;
                    $message = 'Resource not found';
                } elseif ($e instanceof MethodNotAllowedHttpException) {
                    $code = HttpStatusCode::METHOD_NOT_ALLOWED->value;
                    $message = 'Method not allowed';
                } elseif ($e instanceof ThrottleRequestsException) {
                    $code = HttpStatusCode::TOO_MANY_REQUESTS->value;
                    $message = 'Too many requests';
                }

                $response = [
                    'code' => $code,
                    'message' => $message,
                ];

                if ($errors) {
                    $response['errors'] = $errors;
                }

                return response()->json($response, $code);
            }
        });
    })->create();
