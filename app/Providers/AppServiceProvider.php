<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Observers\RatingObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Rating::observe(RatingObserver::class);

        // Centralized cache invalidation for the unified homepage cached payload
        $clearHomeCache = function () {
            Cache::forget('public_homepage_data');
        };

        Setting::saved($clearHomeCache);
        Setting::deleted($clearHomeCache);

        Category::saved($clearHomeCache);
        Category::deleted($clearHomeCache);

        Location::saved($clearHomeCache);
        Location::deleted($clearHomeCache);

        TourCategory::saved($clearHomeCache);
        TourCategory::deleted($clearHomeCache);

        Tour::saved($clearHomeCache);
        Tour::deleted($clearHomeCache);

        BlogPost::saved($clearHomeCache);
        BlogPost::deleted($clearHomeCache);

        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        $limitNone = app()->environment('testing');

        // Standard limiter
        RateLimiter::for('api.standard', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.standard', 60))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Strict limiter (for sensitive actions)
        RateLimiter::for('api.strict', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.strict', 5))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Auth limiter
        RateLimiter::for('api.auth', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.auth', 10))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Admin/Admin Dashboard limiter
        RateLimiter::for('api.admin', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.admin', 30))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Uploads limiter
        RateLimiter::for('api.uploads', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.uploads', 20))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Exports limiter
        RateLimiter::for('api.exports', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.exports', 10))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Resend Verification/OTP limiter
        RateLimiter::for('api.resend', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.resend', 3))
                    ->by($request->user()?->id ?: $request->ip());
        });

        // Webhook callbacks limiter
        RateLimiter::for('api.callbacks', function (Request $request) use ($limitNone) {
            return $limitNone
                ? Limit::none()
                : Limit::perMinute(config('api.rate_limits.callbacks', 20))
                    ->by($request->ip());
        });
    }
}
