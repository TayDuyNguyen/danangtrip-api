<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatRequest;
use App\Models\ChatMessage;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => 'required|integer|exists:chat_messages,id',
            'rating' => 'required|string|in:positive,negative',
        ]);

        $message = ChatMessage::findOrFail($data['message_id']);
        $metadata = $message->metadata ?? [];
        $metadata['rating'] = $data['rating'];
        $message->metadata = $metadata;
        $message->save();

        return $this->success(null, 'Feedback saved successfully.');
    }
}
