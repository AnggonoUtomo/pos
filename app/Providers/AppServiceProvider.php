<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (glob(app_path('Modules/*/*/ServiceProvider.php')) ?: [] as $providerPath) {
            $category = basename(dirname(dirname($providerPath)));
            $module = basename(dirname($providerPath));
            $providerClass = "App\\Modules\\{$category}\\{$module}\\ServiceProvider";

            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
