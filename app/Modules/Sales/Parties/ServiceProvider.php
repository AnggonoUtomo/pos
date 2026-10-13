<?php

namespace App\Modules\Sales\Parties;

use App\Modules\Sales\Parties\Application\Actions\ManageItemPrices;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceRepository;
use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories\EloquentItemPriceRepository;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories\EloquentPartiesRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PartiesRepository::class, EloquentPartiesRepository::class);
        $this->app->bind(ItemPriceRepository::class, EloquentItemPriceRepository::class);
        $this->app->bind(ItemPriceCatalog::class, ManageItemPrices::class);
    }

    public function boot(): void
    {
        if (is_file(__DIR__.'/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        if (is_dir(__DIR__.'/Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}
