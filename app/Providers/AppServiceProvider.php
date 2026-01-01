<?php

namespace App\Providers;

use App\Services\CommonService;
use App\Classes\NotificationSentClass;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('sentNotification', function () {
            return new NotificationSentClass();
        });

        $this->app->singleton('CommonService', function () {
            return new CommonService();
        });


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);
    }
}
