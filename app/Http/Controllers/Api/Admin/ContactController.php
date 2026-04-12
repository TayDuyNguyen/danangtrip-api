<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\ContactsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ExportContactRequest;
use App\Http\Requests\Contact\IndexContactRequest;
use App\Http\Requests\Contact\ReplyContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ContactController
 * Handles administrative API requests for contact management.
 * (Xử lý các yêu cầu API quản trị cho quản lý liên hệ)
 */
final class ContactController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * Display a paginated listing of contacts.
     * (Hiển thị danh sách liên hệ có phân trang)
     */
    public function index(IndexContactRequest $request): JsonResponse
    {
        $result = $this->contactService->getList($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display contact detail and mark as read.
     * (Hiển thị chi tiết liên hệ và đánh dấu đã đọc)
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->contactService->getDetail($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Reply to a contact.
     * (Trả lời liên hệ)
     */
    public function reply(ReplyContactRequest $request, int $id): JsonResponse
    {
        $adminId = $request->user()->id;
        $result = $this->contactService->replyContact($id, $request->validated()['reply'], $adminId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a contact.
     * (Xóa liên hệ)
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->contactService->deleteContact($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Export contacts list to Excel.
     * (Xuất danh sách liên hệ ra Excel)
     *
     * @return BinaryFileResponse|JsonResponse
     */
    public function export(ExportContactRequest $request)
    {
        $result = $this->contactService->exportContacts($request->validated());

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        return Excel::download(
            new ContactsExport($result['data']),
            'contacts_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
