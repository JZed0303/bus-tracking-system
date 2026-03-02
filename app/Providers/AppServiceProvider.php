<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Bus;
use App\Observers\BusObserver;
use App\Support\ThemeOverride;

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
         Bus::observe(BusObserver::class);
        Schema::defaultStringLength(191);

        view()->composer('layouts.head-css', function ($view) {
            $selectedPartial = ThemeOverride::DEFAULT_PARTIAL;

            if (Schema::hasTable('system_settings')) {
                $selectedPartial = ThemeOverride::getSelectedPartial();
            }

            $view->with('selectedThemeOverridePartial', $selectedPartial);
        });
    }
}
