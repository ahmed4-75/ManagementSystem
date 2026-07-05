<?php

namespace App\Providers;

use App\Repositories\Contracts\ProjectInterface;
use App\Repositories\Contracts\StatusInterface;
use App\Repositories\Contracts\TaskInterface;
use App\Repositories\Contracts\UserInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\StatusRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProjectInterface::class,
            ProjectRepository::class
        );

        $this->app->bind(
            StatusInterface::class,
            StatusRepository::class
        );

        $this->app->bind(
            TaskInterface::class,
            TaskRepository::class
        );

        $this->app->bind(
            UserInterface::class,
            UserRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
