<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Api\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Api\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PointController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Main API route definitions for the application.
| (Định nghĩa các tập hợp Route API cho ứng dụng)
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // 1. PUBLIC ROUTES
    // (Tuyến đường công khai - Không cần Token)
    // =========================================================================

    // Auth: Register & Login & Forgot Password
    // (Xác thực: Đăng ký & Đăng nhập & Quên mật khẩu)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Categories: List & Detail
    // (Danh mục: Danh sách & Chi tiết)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');

    // Locations: Search & Details
    // (Địa điểm: Tìm kiếm & Xem thông tin)
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/featured', [LocationController::class, 'featured']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);
    Route::get('/locations/{slug}', [LocationController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('/locations/{id}/ratings', [LocationController::class, 'ratings'])->whereNumber('id');
    Route::post('/locations/{id}/view', [LocationController::class, 'recordView'])->whereNumber('id');

    // Search: Locations Search & Suggestions & Popular Queries
    // (Tìm kiếm: Tìm kiếm địa điểm & Gợi ý & Từ khóa phổ biến)
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
    Route::get('/search/popular', [SearchController::class, 'popular']);

    // =========================================================================
    // 2. PROTECTED ROUTES
    // (Tuyến đường bảo vệ - Yêu cầu JWT Token)
    // =========================================================================

    Route::middleware('jwt.auth')->group(function () {
        // Auth: Logout & Profile & Token Refresh
        // (Xác thực: Đăng xuất & Thông tin cá nhân & Làm mới Token)
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);

        // Ratings: Create / Update / Delete / Helpful
        // (Đánh giá: Tạo / Sửa / Xóa / Đánh dấu hữu ích)
        Route::post('/ratings', [RatingController::class, 'store']);
        Route::put('/ratings/{id}', [RatingController::class, 'update'])->whereNumber('id');
        Route::delete('/ratings/{id}', [RatingController::class, 'destroy'])->whereNumber('id');
        Route::post('/ratings/{id}/helpful', [RatingController::class, 'helpful'])->whereNumber('id');

        // Favorites: List / Add / Remove
        // (Yêu thích: Danh sách / Thêm / Xóa)
        Route::get('/user/favorites', [FavoriteController::class, 'index']);
        Route::post('/user/favorites', [FavoriteController::class, 'store']);
        Route::delete('/user/favorites/{location_id}', [FavoriteController::class, 'destroy'])->whereNumber('location_id');

        // User Profile & History
        // (Thông tin cá nhân & Lịch sử)
        Route::get('/user/profile', [ProfileController::class, 'show']);
        Route::put('/user/profile', [ProfileController::class, 'update']);
        Route::post('/user/profile/avatar', [ProfileController::class, 'updateAvatar']);
        Route::put('/user/password', [ProfileController::class, 'changePassword']);
        Route::get('/user/ratings', [ProfileController::class, 'ratings']);

        // Points Management
        // (Quản lý Điểm thưởng)
        Route::get('/user/points', [PointController::class, 'balance']);
        Route::get('/user/points/transactions', [PointController::class, 'transactions']);
        Route::post('/user/points/purchase', [PointController::class, 'purchase']);

        // Notifications
        // (Thông báo)
        Route::get('/user/notifications', [NotificationController::class, 'index']);
        Route::patch('/user/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
        Route::patch('/user/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/user/notifications/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
    });

    // =========================================================================
    // 3. ADMIN ROUTES
    // (Tuyến đường quản trị - Yêu cầu Token + Quyền Admin)
    // =========================================================================

    Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->group(function () {

        // Category Management
        // (Quản lý Danh mục)
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->whereNumber('id');

        // Subcategory Management
        // (Quản lý Danh mục con)
        Route::post('/subcategories', [AdminSubcategoryController::class, 'store']);
        Route::put('/subcategories/{id}', [AdminSubcategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/subcategories/{id}', [AdminSubcategoryController::class, 'destroy'])->whereNumber('id');

        // Location Management
        // (Quản lý Địa điểm)
        Route::post('/locations', [AdminLocationController::class, 'store']);
        Route::put('/locations/{id}', [AdminLocationController::class, 'update'])->whereNumber('id');
        Route::delete('/locations/{id}', [AdminLocationController::class, 'destroy'])->whereNumber('id');
        Route::patch('/locations/{id}/status', [AdminLocationController::class, 'updateStatus'])->whereNumber('id');
        Route::patch('/locations/{id}/featured', [AdminLocationController::class, 'toggleFeatured'])->whereNumber('id');

        // User Management
        // (Quản lý Người dùng)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');

        // Ratings Management
        // (Quản lý Đánh giá)
        Route::get('/ratings', [AdminRatingController::class, 'index']);
        Route::patch('/ratings/{id}/approve', [AdminRatingController::class, 'approve'])->whereNumber('id');
        Route::patch('/ratings/{id}/reject', [AdminRatingController::class, 'reject'])->whereNumber('id');
    });

});
