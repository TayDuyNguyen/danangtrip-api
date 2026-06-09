<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class ContactService
 * Handles business logic for contact form submissions and admin management.
 * (Xử lý logic nghiệp vụ cho form liên hệ và quản lý liên hệ của admin)
 */
final class ContactService
{
    /**
     * ContactService constructor.
     * (Khởi tạo ContactService)
     */
    public function __construct(
        protected ContactRepositoryInterface $contactRepository,
        protected ContactReplyMailService $contactReplyMailService
    ) {}

    /**
     * Submit a new contact form.
     * (Gửi form liên hệ mới)
     */
    public function submit(array $data): array
    {
        try {
            $contact = $this->contactRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $contact,
                'message' => 'Your message has been sent successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to submit contact form.',
            ];
        }
    }

    /**
     * Get paginated contacts list for admin.
     * (Lấy danh sách liên hệ có phân trang cho admin)
     */
    public function getList(array $filters): array
    {
        try {
            $contacts = $this->contactRepository->getPaginated($filters);
            $data = $contacts->toArray();
            $data['stats'] = $this->contactRepository->getStats($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve contacts.',
            ];
        }
    }

    /**
     * Get contact detail and mark as read if new.
     * (Lấy chi tiết liên hệ và đánh dấu đã đọc nếu còn mới)
     */
    public function getDetail(int $id): array
    {
        try {
            $contact = $this->contactRepository->find($id);

            if (! $contact) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Contact not found.',
                ];
            }

            $this->contactRepository->markAsRead($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->contactRepository->find($id),
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve contact.',
            ];
        }
    }

    /**
     * Reply to a contact.
     * (Trả lời liên hệ)
     */
    public function replyContact(int $id, string $reply, int $adminId): array
    {
        try {
            $contact = $this->contactRepository->find($id);

            if (! $contact) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Contact not found.',
                ];
            }

            if ($contact->status === 'replied') {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'This contact has already been replied to.',
                ];
            }

            if (! empty($contact->email)) {
                $this->contactReplyMailService->send($contact, $reply);
            }

            $this->contactRepository->reply($id, $reply, $adminId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Reply sent successfully.',
            ];
        } catch (Throwable $e) {
            Log::error('Failed to send contact reply.', [
                'contact_id' => $id,
                'admin_id' => $adminId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to send reply.',
            ];
        }
    }

    /**
     * Delete a contact.
     * (Xóa liên hệ)
     */
    public function deleteContact(int $id): array
    {
        try {
            $contact = $this->contactRepository->find($id);

            if (! $contact) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Contact not found.',
                ];
            }

            $this->contactRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Contact deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete contact.',
            ];
        }
    }

    /**
     * Export contacts to Excel collection.
     * (Xuất danh sách liên hệ ra Excel)
     */
    public function exportContacts(array $filters): array
    {
        try {
            $contacts = $this->contactRepository->getAllForExport($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $contacts,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to export contacts.',
            ];
        }
    }
}
