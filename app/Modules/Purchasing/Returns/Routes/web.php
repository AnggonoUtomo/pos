<?php

use App\Modules\Purchasing\Returns\Presentation\Controllers\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('purchasing/returns')->name('purchasing.returns.')->group(function (): void {
    Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
    Route::get('data', [PurchaseReturnController::class, 'data'])->name('data');
    Route::get('purchases', [PurchaseReturnController::class, 'purchases'])->name('purchases');
    Route::get('purchases/{id}', [PurchaseReturnController::class, 'purchase'])->whereUlid('id')->name('purchase');
    Route::post('/', [PurchaseReturnController::class, 'store'])->name('store');
    Route::put('{id}', [PurchaseReturnController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [PurchaseReturnController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [PurchaseReturnController::class, 'restore'])->whereUlid('id')->name('restore');
    Route::post('{id}/post', [PurchaseReturnController::class, 'post'])->whereUlid('id')->name('post');
    Route::get('{id}/refund-quote', [PurchaseReturnController::class, 'refundQuote'])->whereUlid('id')->name('refund-quote');
    Route::post('{id}/refund', [PurchaseReturnController::class, 'refund'])->whereUlid('id')->name('refund');
});
