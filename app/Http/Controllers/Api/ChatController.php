<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;

final class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    public function send(SendChatRequest $request): JsonResponse
    {
        $result = $this->chatService->send($request->validated(), $request);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Success')
            : $this->error($result['message'], $result['status']);
    }
}
