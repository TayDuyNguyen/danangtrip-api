<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BlogService;
use App\Services\CategoryService;
use App\Services\DashboardService;
use App\Services\LocationService;
use App\Services\SettingService;
use App\Services\TourCategoryService;
use App\Services\TourService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Class HomeController
 * Consolidates public home page sections into a single cached endpoint.
 * (Gom các phần trang chủ công khai thành một endpoint được cache)
 */
final class HomeController extends Controller
{
    /**
     * HomeController constructor.
     */
    public function __construct(
        protected DashboardService $dashboardService,
        protected CategoryService $categoryService,
        protected LocationService $locationService,
        protected TourCategoryService $tourCategoryService,
        protected TourService $tourService,
        protected BlogService $blogService,
        protected SettingService $settingService
    ) {}

    /**
     * Retrieve public home page consolidated datasets with caching.
     * (Lấy dữ liệu hợp nhất cho trang chủ kèm cache)
     */
    public function index(): JsonResponse
    {
        $cachedData = Cache::get('public_homepage_data');
        if ($cachedData) {
            return $this->success($cachedData, 'Public homepage data retrieved successfully.');
        }

        // Fetch each service result and verify status before writing to cache
        $statisticsResult = $this->dashboardService->getOverviewStats();
        $categoriesResult = $this->categoryService->getPublicCategories();
        $featuredLocationsResult = $this->locationService->getFeaturedLocations(10);
        $tourCategoriesResult = $this->tourCategoryService->getActiveCategories();
        $featuredToursResult = $this->tourService->getFeaturedTours(10);
        $hotToursResult = $this->tourService->getHotTours(10);
        $latestBlogsResult = $this->blogService->getPublicPosts(['page' => 1, 'per_page' => 10]);
        $configResult = $this->settingService->getPublicSettings();

        // Detect if any service returned an error status (anything other than 200)
        $hasError = ($statisticsResult['status'] ?? 200) !== 200
            || ($categoriesResult['status'] ?? 200) !== 200
            || ($featuredLocationsResult['status'] ?? 200) !== 200
            || ($tourCategoriesResult['status'] ?? 200) !== 200
            || ($featuredToursResult['status'] ?? 200) !== 200
            || ($hotToursResult['status'] ?? 200) !== 200
            || ($latestBlogsResult['status'] ?? 200) !== 200
            || ($configResult['status'] ?? 200) !== 200;

        $data = [
            'statistics' => $statisticsResult['data'] ?? null,
            'categories' => $categoriesResult['data'] ?? [],
            'featured_locations' => $featuredLocationsResult['data'] ?? [],
            'tour_categories' => $tourCategoriesResult['data'] ?? [],
            'featured_tours' => $featuredToursResult['data'] ?? [],
            'hot_tours' => $hotToursResult['data'] ?? [],
            'latest_blogs' => $latestBlogsResult['data'] ?? null,
            'config' => $configResult['data'] ?? null,
        ];

        // Cache the result ONLY if all services completed successfully
        if (! $hasError) {
            Cache::put('public_homepage_data', $data, 300);
        }

        return $this->success($data, 'Public homepage data retrieved successfully.');
    }
}
