<?php

namespace App\Traits;

use App\Enums\HttpStatusCode;
use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Success response with 200 code.
     * (Phản hồi thành công với mã 200)
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = HttpStatusCode::SUCCESS->value): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response with 400 code.
     * (Phản hồi lỗi chung với mã 400)
     */
    protected function error(string $message = 'Error', int $code = HttpStatusCode::BAD_REQUEST->value, mixed $errors = null): JsonResponse
    {
        $response = [
            'code' => $code,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Created response with 201 code.
     * (Phản hồi tạo mới thành công với mã 201)
     */
    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, HttpStatusCode::CREATED->value);
    }

    /**
     * Unauthorized response with 401 code.
     * (Phản hồi lỗi xác thực, không có quyền truy cập với mã 401)
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, HttpStatusCode::UNAUTHORIZED->value);
    }

    /**
     * Forbidden response with 403 code.
     * (Phản hồi lỗi bị cấm, không có quyền thực hiện hành động với mã 403)
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, HttpStatusCode::FORBIDDEN->value);
    }

    /**
     * Not found response with 404 code.
     * (Phản hồi không tìm thấy dữ liệu với mã 404)
     */
    protected function not_found(string $message = 'Not found'): JsonResponse
    {
        return $this->error($message, HttpStatusCode::NOT_FOUND->value);
    }

    /**
     * Validation error response with 422 code.
     * (Phản hồi lỗi do dữ liệu gửi lên không hợp lệ với mã 422)
     */
    protected function validation_error(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, HttpStatusCode::VALIDATION_ERROR->value, $errors);
    }

    /**
     * Internal server error response with 500 code.
     * (Phản hồi lỗi hệ thống từ phía server với mã 500)
     */
    protected function server_error(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, HttpStatusCode::INTERNAL_SERVER_ERROR->value);
    }
}
