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
        $this->deleteStaleViteHotFile();
    }

    private function deleteStaleViteHotFile(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $hotUrl = trim((string) file_get_contents($hotFile));
        $host = parse_url($hotUrl, PHP_URL_HOST);
        $port = parse_url($hotUrl, PHP_URL_PORT);

        if (! is_string($host) || ! is_int($port)) {
            @unlink($hotFile);

            return;
        }

        $connectionHost = trim($host, '[]');

        if (filter_var($connectionHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $connectionHost = "[{$connectionHost}]";
        }

        $connection = @fsockopen($connectionHost, $port, $errorCode, $errorMessage, 0.05);

        if (is_resource($connection)) {
            fclose($connection);

            return;
        }

        @unlink($hotFile);
    }
}
