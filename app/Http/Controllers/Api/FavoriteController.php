<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\FavoriteValidation;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->favoriteService->getFavorites($userId, $request->all());

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data']);
        }

        return $this->server_error($result['message']);
    }

    /**
     * Add a location to favorites.
     * (Thêm địa điểm vào danh sách yêu thích)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = FavoriteValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $userId = $request->user()->id;
        $locationId = (int) $request->input('location_id');

        $result = $this->favoriteService->saveFavorite($userId, $locationId);

        if ($result['status'] === HttpStatusCode::CREATED->value) {
            return $this->created(null, $result['message']);
        }

        if ($result['status'] === HttpStatusCode::BAD_REQUEST->value) {
            return $this->validation_error(['location_id' => [$result['message']]]);
        }

        return $this->server_error($result['message']);
    }

    /**
     * Remove a location from favorites.
     * (Xóa địa điểm khỏi danh sách yêu thích)
     */
    public function destroy(int $locationId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->favoriteService->unsaveFavorite($userId, $locationId);

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success(null, $result['message']);
        }

        if ($result['status'] === HttpStatusCode::NOT_FOUND->value) {
            return $this->not_found($result['message']);
        }

        return $this->server_error($result['message']);
    }
}
