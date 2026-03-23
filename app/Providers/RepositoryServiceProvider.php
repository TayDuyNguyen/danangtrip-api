<?php

namespace App\Providers;

use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\LocationRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\Eloquent\RatingImageRepository;
use App\Repositories\Eloquent\RatingRepository;
use App\Repositories\Eloquent\SearchLogRepository;
use App\Repositories\Eloquent\SubcategoryRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\RatingImageRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;
use App\Repositories\Interfaces\SubcategoryRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 * Registers repository interface bindings with Eloquent implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(SubcategoryRepositoryInterface::class, SubcategoryRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(SearchLogRepositoryInterface::class, SearchLogRepository::class);
        $this->app->bind(RatingRepositoryInterface::class, RatingRepository::class);
        $this->app->bind(RatingImageRepositoryInterface::class, RatingImageRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
