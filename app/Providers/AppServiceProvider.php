<?php

namespace App\Providers;

use App\Services\NotificationCenterService;
use App\Services\FeatureToggleService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            $view->with('aiFeaturesEnabled', app(FeatureToggleService::class)->aiFeaturesEnabled());
        });

        View::composer('layouts.app', function ($view) {
            $view->with(app(NotificationCenterService::class)->getSharedData());
        });
    }
}