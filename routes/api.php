<?php

use App\Http\Controllers\Api\Admin\AmenityController as AdminAmenityController;
use App\Http\Controllers\Api\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Api\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Api\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\Admin\TourCategoryController as AdminTourCategoryController;
use App\Http\Controllers\Api\Admin\TourController as AdminTourController;
use App\Http\Controllers\Api\Admin\TourScheduleController as AdminTourScheduleController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AmenityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TourCategoryController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\UploadController;
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
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:api.strict');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api.auth');
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:api.strict');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:api.strict');

    // Categories: List & Detail & Locations by slug
    // (Danh mục: Danh sách & Chi tiết & Địa điểm theo danh mục)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');
    Route::get('/categories/{slug}/locations', [CategoryController::class, 'locationsBySlug'])->where('slug', '[a-z0-9-]+');

    // Districts: Static list of Da Nang districts
    // (Quận/huyện: Danh sách tĩnh các quận của Đà Nẵng)
    Route::get('/districts', [DistrictController::class, 'index']);

    // Locations: Search & Details
    // (Địa điểm: Tìm kiếm & Xem thông tin)
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/featured', [LocationController::class, 'featured']);
    Route::get('/locations/nearby', [LocationController::class, 'nearby']);
    Route::get('/locations/districts', [LocationController::class, 'districts']);
    Route::get('/locations/{slug}', [LocationController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('/locations/{id}/images', [LocationController::class, 'images'])->whereNumber('id');
    Route::get('/locations/{id}/ratings', [LocationController::class, 'ratings'])->whereNumber('id');
    Route::get('/locations/{id}/rating-stats', [LocationController::class, 'ratingStats'])->whereNumber('id');
    Route::get('/locations/{id}/nearby', [LocationController::class, 'nearbyLocations'])->whereNumber('id');
    Route::post('/locations/{id}/view', [LocationController::class, 'recordView'])->whereNumber('id')->middleware('throttle:api.standard');

    // Search: Locations Search & Suggestions & Popular Queries
    // (Tìm kiếm: Tìm kiếm địa điểm & Gợi ý & Từ khóa phổ biến)
    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:api.standard');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:api.standard');
    Route::get('/search/popular', [SearchController::class, 'popular'])->middleware('throttle:api.standard');
    Route::get('/search/popular-with-filters', [SearchController::class, 'popularWithFilters'])->middleware('throttle:api.standard');

    // Ratings: Public access
    // (Đánh giá: Truy cập công khai)
    Route::get('/ratings/{id}/images', [RatingController::class, 'images'])->whereNumber('id');

    // Blog: Public access
    // (Blog: Truy cập công khai)
    Route::get('/blog', [BlogController::class, 'index']);
    Route::get('/blog/categories', [BlogController::class, 'categories']);
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->where('slug', '[a-z0-9-]+');

    // Tags & Amenities: Public access
    // (Tags & Tiện ích: Truy cập công khai)
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/amenities', [AmenityController::class, 'index']);

    // Tours: Public access
    // (Tour: Truy cập công khai)
    Route::get('/tours', [TourController::class, 'index']);
    Route::get('/tours/featured', [TourController::class, 'featured']);
    Route::get('/tours/hot', [TourController::class, 'hot']);
    Route::get('/tours/{slug}', [TourController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('/tours/{id}/schedules', [TourController::class, 'schedules'])->whereNumber('id');
    Route::get('/tours/{id}/ratings', [TourController::class, 'ratings'])->whereNumber('id');
    Route::get('/tours/{id}/rating-stats', [TourController::class, 'ratingStats'])->whereNumber('id');
    Route::post('/tours/{id}/check-availability', [TourController::class, 'checkAvailability'])->whereNumber('id')->middleware('throttle:api.standard');

    // Tour Categories: Public access
    // (Danh mục tour: Truy cập công khai)
    Route::get('/tour-categories', [TourCategoryController::class, 'index']);
    Route::get('/tour-categories/{slug}/tours', [TourCategoryController::class, 'toursBySlug'])->where('slug', '[a-z0-9-]+');

    // Payments: Webhook
    // (Thanh toán: Webhook)
    Route::post('/payments/callback', [PaymentController::class, 'callback'])->middleware('throttle:api.callbacks');

    // =========================================================================
    // 2. PROTECTED ROUTES
    // (Tuyến đường bảo vệ - Yêu cầu JWT Token)
    // =========================================================================

    Route::middleware('jwt.auth')->group(function () {
        // Auth: Logout & Profile
        // (Xác thực: Đăng xuất & Thông tin cá nhân)
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:api.auth');
        Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:api.resend');

        // Ratings: Create / Update / Delete / Helpful / Check
        // (Đánh giá: Tạo / Sửa / Xóa / Đánh dấu hữu ích / Kiểm tra)
        Route::get('/ratings/check', [RatingController::class, 'check']);
        Route::post('/ratings', [RatingController::class, 'store'])->middleware('throttle:api.strict');
        Route::put('/ratings/{id}', [RatingController::class, 'update'])->whereNumber('id');
        Route::delete('/ratings/{id}', [RatingController::class, 'destroy'])->whereNumber('id');
        Route::post('/ratings/{id}/helpful', [RatingController::class, 'helpful'])->whereNumber('id');

        // Favorites: List / Add / Remove / Check
        // (Yêu thích: Danh sách / Thêm / Xóa / Kiểm tra)
        Route::get('/user/favorites', [FavoriteController::class, 'index']);
        Route::get('/user/favorites/check/{location_id}', [FavoriteController::class, 'check'])->whereNumber('location_id');
        Route::post('/user/favorites', [FavoriteController::class, 'store']);
        Route::delete('/user/favorites/{location_id}', [FavoriteController::class, 'destroy'])->whereNumber('location_id');

        // User Profile & History
        // (Thông tin cá nhân & Lịch sử)
        Route::get('/user/profile', [ProfileController::class, 'show']);
        Route::put('/user/profile', [ProfileController::class, 'update']);
        Route::post('/user/profile/avatar', [ProfileController::class, 'updateAvatar']);
        Route::put('/user/password', [ProfileController::class, 'changePassword']);
        Route::get('/user/ratings', [ProfileController::class, 'ratings']);

        // Notifications
        // (Thông báo)
        Route::get('/user/notifications', [NotificationController::class, 'index']);
        Route::patch('/user/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
        Route::patch('/user/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/user/notifications/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');

        // Upload Management
        // (Quản lý Upload)
        Route::post('/upload/image', [UploadController::class, 'uploadImage'])->middleware('throttle:api.uploads');
        Route::post('/upload/images', [UploadController::class, 'uploadImages'])->middleware('throttle:api.uploads');
        Route::delete('/upload/image', [UploadController::class, 'deleteImage']);

        // Bookings
        // (Đặt tour)
        Route::post('/bookings/calculate', [BookingController::class, 'calculate'])->middleware('throttle:api.auth');
        Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:api.strict');
        Route::get('/user/bookings', [BookingController::class, 'index']);
        Route::get('/user/bookings/{id}', [BookingController::class, 'show'])->whereNumber('id');
        Route::get('/user/bookings/code/{booking_code}', [BookingController::class, 'showByCode']);
        Route::get('/user/bookings/{id}/invoice', [BookingController::class, 'invoice'])->whereNumber('id');
        Route::post('/user/bookings/{id}/cancel', [BookingController::class, 'cancel'])->whereNumber('id');

        // Payments
        // (Thanh toán)
        Route::post('/payments/create', [PaymentController::class, 'create'])->middleware('throttle:api.strict');
        Route::get('/payments/status/{transaction_code}', [PaymentController::class, 'status']);
        Route::post('/payments/retry/{booking_code}', [PaymentController::class, 'retry'])->middleware('throttle:api.strict');
    });

    // =========================================================================
    // 3. ADMIN ROUTES
    // (Tuyến đường quản trị - Yêu cầu Token + Quyền Admin)
    // =========================================================================

    Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->group(function () {

        // Dashboard & Reports
        // (Bảng điều khiển & Báo cáo)
        Route::get('/dashboard', [AdminDashboardController::class, 'overview'])->middleware('throttle:api.admin');
        Route::get('/reports/locations', [AdminDashboardController::class, 'locationReports'])->middleware('throttle:api.admin');
        Route::get('/reports/ratings', [AdminDashboardController::class, 'ratingReports'])->middleware('throttle:api.admin');
        Route::get('/reports/users', [AdminDashboardController::class, 'userReports'])->middleware('throttle:api.admin');

        // Categories Management
        // (Quản lý Danh mục)
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->whereNumber('id');
        Route::patch('/categories/{id}/status', [AdminCategoryController::class, 'updateStatus'])->whereNumber('id');

        // Subcategory Management
        // (Quản lý Danh mục con)
        Route::post('/subcategories', [AdminSubcategoryController::class, 'store']);
        Route::put('/subcategories/{id}', [AdminSubcategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/subcategories/{id}', [AdminSubcategoryController::class, 'destroy'])->whereNumber('id');
        Route::patch('/subcategories/{id}/status', [AdminSubcategoryController::class, 'updateStatus'])->whereNumber('id');

        // Location Management
        // (Quản lý Địa điểm)
        Route::get('/locations/export', [AdminLocationController::class, 'export'])->middleware('throttle:api.exports');
        Route::post('/locations', [AdminLocationController::class, 'store']);
        Route::put('/locations/{id}', [AdminLocationController::class, 'update'])->whereNumber('id');
        Route::delete('/locations/{id}', [AdminLocationController::class, 'destroy'])->whereNumber('id');
        Route::patch('/locations/{id}/status', [AdminLocationController::class, 'updateStatus'])->whereNumber('id');
        Route::patch('/locations/{id}/featured', [AdminLocationController::class, 'toggleFeatured'])->whereNumber('id');
        Route::post('/locations/{id}/tags', [AdminLocationController::class, 'attachTags'])->whereNumber('id');
        Route::delete('/locations/{id}/tags/{tagId}', [AdminLocationController::class, 'detachTag'])->whereNumber('id')->whereNumber('tagId');
        Route::post('/locations/{id}/amenities', [AdminLocationController::class, 'attachAmenities'])->whereNumber('id');
        Route::delete('/locations/{id}/amenities/{amenityId}', [AdminLocationController::class, 'detachAmenity'])->whereNumber('id')->whereNumber('amenityId');

        // User Management
        // (Quản lý Người dùng)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
        Route::patch('/users/{id}/status', [AdminUserController::class, 'updateStatus'])->whereNumber('id');
        Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])->whereNumber('id');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');

        // Ratings Management
        // (Quản lý Đánh giá)
        Route::get('/ratings', [AdminRatingController::class, 'index']);
        Route::get('/ratings/export', [AdminRatingController::class, 'export'])->middleware('throttle:api.exports');
        Route::patch('/ratings/{id}/approve', [AdminRatingController::class, 'approve'])->whereNumber('id');
        Route::patch('/ratings/{id}/reject', [AdminRatingController::class, 'reject'])->whereNumber('id');
        Route::delete('/ratings/{id}', [AdminRatingController::class, 'destroy'])->whereNumber('id');

        // Blog Management
        // (Quản lý Blog)
        Route::post('/blog', [AdminBlogController::class, 'store']);
        Route::put('/blog/{id}', [AdminBlogController::class, 'update'])->whereNumber('id');
        Route::delete('/blog/{id}', [AdminBlogController::class, 'destroy'])->whereNumber('id');
        Route::patch('/blog/{id}/publish', [AdminBlogController::class, 'publish'])->whereNumber('id');

        // Tours Management
        // (Quản lý Tour)
        Route::post('/tours', [AdminTourController::class, 'store']);
        Route::put('/tours/{id}', [AdminTourController::class, 'update'])->whereNumber('id');
        Route::delete('/tours/{id}', [AdminTourController::class, 'destroy'])->whereNumber('id');
        Route::patch('/tours/{id}/status', [AdminTourController::class, 'updateStatus'])->whereNumber('id');
        Route::patch('/tours/{id}/featured', [AdminTourController::class, 'toggleFeatured'])->whereNumber('id');
        Route::patch('/tours/{id}/hot', [AdminTourController::class, 'toggleHot'])->whereNumber('id');
        Route::get('/tours/export', [AdminTourController::class, 'export'])->middleware('throttle:api.exports');

        // Tour Categories Management
        // (Quản lý Danh mục Tour)
        Route::get('/tour-categories', [AdminTourCategoryController::class, 'index']);
        Route::post('/tour-categories', [AdminTourCategoryController::class, 'store']);
        Route::put('/tour-categories/{id}', [AdminTourCategoryController::class, 'update'])->whereNumber('id');
        Route::delete('/tour-categories/{id}', [AdminTourCategoryController::class, 'destroy'])->whereNumber('id');
        Route::patch('/tour-categories/{id}/status', [AdminTourCategoryController::class, 'updateStatus'])->whereNumber('id');

        // Tour Schedules Management
        // (Quản lý Lịch khởi hành Tour)
        Route::get('/tour-schedules', [AdminTourScheduleController::class, 'index']);
        Route::get('/tour-schedules/{id}', [AdminTourScheduleController::class, 'show'])->whereNumber('id');
        Route::post('/tours/{id}/schedules', [AdminTourScheduleController::class, 'store'])->whereNumber('id');
        Route::put('/tour-schedules/{id}', [AdminTourScheduleController::class, 'update'])->whereNumber('id');
        Route::delete('/tour-schedules/{id}', [AdminTourScheduleController::class, 'destroy'])->whereNumber('id');
        Route::patch('/tour-schedules/{id}/status', [AdminTourScheduleController::class, 'updateStatus'])->whereNumber('id');

        // Bookings Management
        // (Quản lý Đặt tour)
        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::get('/bookings/export', [AdminBookingController::class, 'export'])->middleware('throttle:api.exports');
        Route::get('/bookings/{id}', [AdminBookingController::class, 'show'])->whereNumber('id');
        Route::patch('/bookings/{id}/status', [AdminBookingController::class, 'updateStatus'])->whereNumber('id');
        Route::post('/bookings/{id}/confirm', [AdminBookingController::class, 'confirm'])->whereNumber('id');
        Route::post('/bookings/{id}/cancel', [AdminBookingController::class, 'adminCancel'])->whereNumber('id');
        Route::post('/bookings/{id}/complete', [AdminBookingController::class, 'complete'])->whereNumber('id');

        // Payments Management
        // (Quản lý Thanh toán)
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::get('/payments/export', [AdminPaymentController::class, 'export'])->middleware('throttle:api.exports');
        Route::get('/payments/{id}', [AdminPaymentController::class, 'show'])->whereNumber('id');
        Route::post('/payments/{id}/refund', [AdminPaymentController::class, 'refund'])->whereNumber('id')->middleware('throttle:api.exports');

        // Tags & Amenities Management
        // (Quản lý Tags & Tiện ích)
        Route::post('/tags', [AdminTagController::class, 'store']);
        Route::delete('/tags/{id}', [AdminTagController::class, 'destroy'])->whereNumber('id');
        Route::post('/amenities', [AdminAmenityController::class, 'store']);
        Route::delete('/amenities/{id}', [AdminAmenityController::class, 'destroy'])->whereNumber('id');
    });
});
