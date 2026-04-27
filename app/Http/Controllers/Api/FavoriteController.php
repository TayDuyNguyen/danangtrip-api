<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Favorite\CheckFavoriteRequest;
use App\Http\Requests\Favorite\DestroyFavoriteRequest;
use App\Http\Requests\Favorite\IndexFavoriteRequest;
use App\Http\Requests\Favorite\StoreFavoriteRequest;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;

/**
 * Class FavoriteController.
 * (Điều khiển Yêu thích)
 */
final class FavoriteController extends Controller
{
    private FavoriteService $favoriteService;

    /**
     * FavoriteController constructor.
     */
    public function __construct(FavoriteService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    /**
     * Get list of favorite locations.
     * (Lấy danh sách địa điểm yêu thích)
     */
    public function index(IndexFavoriteRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->favoriteService->getFavorites($userId, $request->validated());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data']);
        }

        return $this->server_error($result['message']);
    }

    /**
     * Check if a location or tour is favorited.
     * (Kiểm tra xem một địa điểm hoặc tour có được yêu thích hay không)
     */
    public function check(CheckFavoriteRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $locationId = $request->validated('location_id');
        $tourId = $request->validated('tour_id');

        $result = $this->favoriteService->checkFavorite($userId, $locationId ? (int) $locationId : null, $tourId ? (int) $tourId : null);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data']);
        }

        return $this->server_error($result['message']);
    }

    /**
     * Add a location or tour to favorites.
     * (Thêm địa điểm hoặc tour vào danh sách yêu thích)
     */
    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $locationId = $request->validated('location_id');
        $tourId = $request->validated('tour_id');

        $result = $this->favoriteService->saveFavorite($userId, $locationId ? (int) $locationId : null, $tourId ? (int) $tourId : null);

        if ($result['status'] === HttpStatusCode::CREATED->value) {
            return $this->created(null, $result['message']);
        }

        if ($result['status'] === HttpStatusCode::BAD_REQUEST->value) {
            return $this->validation_error(['item' => [$result['message']]]);
        }

        return $this->server_error($result['message']);
    }

    /**
     * Remove a location or tour from favorites.
     * (Xóa địa điểm hoặc tour khỏi danh sách yêu thích)
     */
    public function destroy(DestroyFavoriteRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $locationId = $request->validated('location_id');
        $tourId = $request->validated('tour_id');

        $result = $this->favoriteService->unsaveFavorite($userId, $locationId ? (int) $locationId : null, $tourId ? (int) $tourId : null);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        }

        if ($result['status'] === HttpStatusCode::NOT_FOUND->value) {
            return $this->not_found($result['message']);
        }

        return $this->server_error($result['message']);
    }
}
