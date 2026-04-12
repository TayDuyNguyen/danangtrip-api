<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

/**
 * Class ContactController
 * Handles public API requests for contact form submissions.
 * (Xử lý các yêu cầu API công khai cho form liên hệ)
 */
final class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * Submit a new contact form.
     * (Gửi form liên hệ mới)
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $result = $this->contactService->submit($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
