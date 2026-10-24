<?php

namespace App\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Application\Actions\IssuePurchaseReturnStock;
use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Actions\ReceivePurchaseStock;
use App\Modules\Inventory\Stock\Application\Actions\ReceiveSaleReturnStock;
use App\Modules\Inventory\Stock\Application\Actions\WriteOperationalStock;
use App\Modules\Inventory\Stock\Application\Contracts\AdjustmentRepository;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseReturnStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\SaleReturnStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\SaleStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockUsage;
use App\Modules\Inventory\Stock\Application\Contracts\TransferStockWriter;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories\DatabaseStockAvailability;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories\EloquentAdjustmentRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories\EloquentStockReceiptRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories\EloquentStockRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransferStockWriter::class, WriteOperationalStock::class);
        $this->app->bind(AdjustmentRepository::class, EloquentAdjustmentRepository::class);
        $this->app->bind(SaleStockWriter::class, IssueStock::class);
        $this->app->bind(PurchaseStockWriter::class, ReceivePurchaseStock::class);
        $this->app->bind(PurchaseReturnStockWriter::class, IssuePurchaseReturnStock::class);
        $this->app->bind(SaleReturnStockWriter::class, ReceiveSaleReturnStock::class);
        $this->app->bind(StockReceiptRepository::class, EloquentStockReceiptRepository::class);
        $this->app->bind(StockAvailability::class,
            DatabaseStockAvailability::class);
        $this->app->bind(StockRepository::class, EloquentStockRepository::class);
        $this->app->bind(StockUsage::class, EloquentStockRepository::class);
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
