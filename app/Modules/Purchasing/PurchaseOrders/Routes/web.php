<?php

use App\Modules\Purchasing\PurchaseOrders\Presentation\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('purchasing/purchase-orders')->name('purchasing.purchase-orders.')->group(function (): void {
    Route::get('data', [PurchaseOrderController::class, 'data'])->name('data');
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::put('{id}', [PurchaseOrderController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [PurchaseOrderController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [PurchaseOrderController::class, 'restore'])->whereUlid('id')->name('restore');
});
