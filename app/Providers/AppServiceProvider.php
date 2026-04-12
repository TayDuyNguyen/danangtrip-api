<?php

namespace App\Providers;

use App\Models\Rating;
use App\Observers\RatingObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
