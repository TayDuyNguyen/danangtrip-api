<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\RatingsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rating\AdminDestroyRatingRequest;
use App\Http\Requests\Rating\AdminIndexRatingRequest;
use App\Http\Requests\Rating\ApproveRatingRequest;
use App\Http\Requests\Rating\RejectRatingRequest;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class RatingController
 * Handles admin API requests for ratings moderation.
 * (Xử lý các yêu cầu API quản trị cho kiểm duyệt đánh giá)
 */
final class RatingController extends Controller
{
    public function __construct(
        protected RatingService $ratingService
    ) {}

    /**
     * List ratings for moderation.
     * (Danh sách đánh giá chờ duyệt / tất cả)
     */
    public function index(AdminIndexRatingRequest $request): JsonResponse
    {
        $result = $this->ratingService->adminList($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Approve a rating.
     * (Duyệt đánh giá)
     */
    public function approve(ApproveRatingRequest $request, int $id): JsonResponse
    {
        $adminId = auth('api')->id();
        $result = $this->ratingService->approve($adminId, $id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Rating approved')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Reject a rating with reason.
     * (Từ chối đánh giá kèm lý do)
     *
     * @param Request request
     */
    public function reject(RejectRatingRequest $request, int $id): JsonResponse
    {
        $adminId = auth('api')->id();
        $result = $this->ratingService->reject($adminId, $id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Rating rejected')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a rating completely.
     * (Xóa đánh giá hoàn toàn)
     */
    public function destroy(AdminDestroyRatingRequest $request, int $id): JsonResponse
    {
        $result = $this->ratingService->adminDelete($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Rating deleted')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Export ratings to Excel.
     * (Xuất danh sách đánh giá ra file Excel)
     */
    public function export(AdminIndexRatingRequest $request)
    {
        $result = $this->ratingService->exportRatings($request->validated());

        if ($result['status'] !== HttpStatusCode::SUCCESS->value) {
            return $this->error($result['message'], $result['status']);
        }

        return Excel::download(new RatingsExport($result['data']), 'ratings.xlsx');
    }
}
