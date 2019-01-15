<?php

namespace Sidraw\GoogleAnalytics;

use Illuminate\Support\ServiceProvider;
use Sidraw\GoogleAnalytics\App\GoogleAnalytics;

class GoogleAnalyticsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/google-analytics.php' => config_path('google-analytics.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton('googleAnalytics', function () {
            return new GoogleAnalytics();
        });
    }
}
