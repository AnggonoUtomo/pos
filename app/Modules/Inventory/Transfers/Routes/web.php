<?php

use App\Modules\Inventory\Transfers\Presentation\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/transfers')->name('inventory.transfers.')->group(function (): void {
    Route::get('data', [TransferController::class, 'data'])->name('data');
    Route::get('options', [TransferController::class, 'options'])->name('options');
    Route::get('/', [TransferController::class, 'index'])->name('index');
    Route::post('/', [TransferController::class, 'store'])->name('store');
    Route::put('{id}', [TransferController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [TransferController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [TransferController::class, 'restore'])->whereUlid('id')->name('restore');
    Route::post('{id}/post', [TransferController::class, 'post'])->whereUlid('id')->name('post');
});
