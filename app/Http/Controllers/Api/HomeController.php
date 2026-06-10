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
    private const CACHE_KEY = 'public_homepage_data_v2';

    private const LOCATIONS_CACHE_KEY = 'public_homepage_locations_v1';

    private const TOURS_CACHE_KEY = 'public_homepage_tours_v1';

    private const BLOGS_CACHE_KEY = 'public_homepage_blogs_v1';

    private const SECTION_LIMIT = 20;

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
        $cachedData = Cache::get(self::CACHE_KEY);
        if ($cachedData) {
            return $this->success($cachedData, 'Public homepage data retrieved successfully.');
        }

        // Fetch each service result and verify status before writing to cache
        $statisticsResult = $this->dashboardService->getOverviewStats();
        $categoriesResult = $this->categoryService->getPublicCategories();
        $featuredLocationsResult = $this->locationService->getFeaturedLocations(self::SECTION_LIMIT);
        $tourCategoriesResult = $this->tourCategoryService->getActiveCategories();
        $featuredToursResult = $this->tourService->getFeaturedTours(self::SECTION_LIMIT);
        $hotToursResult = $this->tourService->getHotTours(self::SECTION_LIMIT);
        $latestBlogsResult = $this->blogService->getPublicPosts(['page' => 1, 'per_page' => self::SECTION_LIMIT]);
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
            Cache::put(self::CACHE_KEY, $data, 300);
        }

        return $this->success($data, 'Public homepage data retrieved successfully.');
    }

    /**
     * Retrieve home location categories and featured locations only.
     * (Lấy riêng cụm danh mục địa điểm và địa điểm nổi bật)
     */
    public function locations(): JsonResponse
    {
        $cachedData = Cache::get(self::LOCATIONS_CACHE_KEY);
        if ($cachedData) {
            return $this->success($cachedData, 'Home location data retrieved successfully.');
        }

        $categoriesResult = $this->categoryService->getPublicCategories();
        $featuredLocationsResult = $this->locationService->getFeaturedLocations(self::SECTION_LIMIT);

        $hasError = ($categoriesResult['status'] ?? 200) !== 200
            || ($featuredLocationsResult['status'] ?? 200) !== 200;

        $data = [
            'categories' => $categoriesResult['data'] ?? [],
            'featured_locations' => $featuredLocationsResult['data'] ?? [],
        ];

        if (! $hasError) {
            Cache::put(self::LOCATIONS_CACHE_KEY, $data, 300);
        }

        return $this->success($data, 'Home location data retrieved successfully.');
    }

    /**
     * Retrieve home tour categories, featured tours, and hot tours only.
     * (Lấy riêng cụm danh mục tour, tour nổi bật và tour hot)
     */
    public function tours(): JsonResponse
    {
        $cachedData = Cache::get(self::TOURS_CACHE_KEY);
        if ($cachedData) {
            return $this->success($cachedData, 'Home tour data retrieved successfully.');
        }

        $tourCategoriesResult = $this->tourCategoryService->getActiveCategories();
        $featuredToursResult = $this->tourService->getFeaturedTours(self::SECTION_LIMIT);
        $hotToursResult = $this->tourService->getHotTours(self::SECTION_LIMIT);

        $hasError = ($tourCategoriesResult['status'] ?? 200) !== 200
            || ($featuredToursResult['status'] ?? 200) !== 200
            || ($hotToursResult['status'] ?? 200) !== 200;

        $data = [
            'tour_categories' => $tourCategoriesResult['data'] ?? [],
            'featured_tours' => $featuredToursResult['data'] ?? [],
            'hot_tours' => $hotToursResult['data'] ?? [],
        ];

        if (! $hasError) {
            Cache::put(self::TOURS_CACHE_KEY, $data, 300);
        }

        return $this->success($data, 'Home tour data retrieved successfully.');
    }

    /**
     * Retrieve home travel blog posts only.
     * (Lấy riêng cụm cẩm nang du lịch)
     */
    public function blogs(): JsonResponse
    {
        $cachedData = Cache::get(self::BLOGS_CACHE_KEY);
        if ($cachedData) {
            return $this->success($cachedData, 'Home blog data retrieved successfully.');
        }

        $latestBlogsResult = $this->blogService->getPublicPosts([
            'page' => 1,
            'per_page' => self::SECTION_LIMIT,
        ]);

        $hasError = ($latestBlogsResult['status'] ?? 200) !== 200;
        $data = [
            'latest_blogs' => $latestBlogsResult['data'] ?? null,
        ];

        if (! $hasError) {
            Cache::put(self::BLOGS_CACHE_KEY, $data, 300);
        }

        return $this->success($data, 'Home blog data retrieved successfully.');
    }
}
