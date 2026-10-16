<?php

use App\Modules\Purchasing\Suppliers\Presentation\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('purchasing/suppliers')->name('purchasing.suppliers.')->group(function (): void {
    Route::get('data', [SupplierController::class, 'data'])->name('data');
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::put('{id}', [SupplierController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [SupplierController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [SupplierController::class, 'restore'])->whereUlid('id')->name('restore');
});
