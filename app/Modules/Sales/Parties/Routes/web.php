<?php

use App\Modules\Sales\Parties\Presentation\Controllers\CustomerController;
use App\Modules\Sales\Parties\Presentation\Controllers\CustomerLevelController;
use Illuminate\Support\Facades\Route;

foreach (['customers' => CustomerController::class, 'customer-levels' => CustomerLevelController::class] as $path => $controller) {
    Route::middleware(['web', 'auth', 'verified'])->prefix('sales/'.$path)->name('sales.'.$path.'.')->group(function () use ($controller): void {
        Route::get('data', [$controller, 'data'])->name('data');
        Route::get('/', [$controller, 'index'])->name('index');
        Route::post('/', [$controller, 'store'])->name('store');
        Route::put('{id}', [$controller, 'update'])->whereUlid('id')->name('update');
        Route::delete('{id}', [$controller, 'destroy'])->whereUlid('id')->name('destroy');
        Route::post('{id}/restore', [$controller, 'restore'])->whereUlid('id')->name('restore');
    });
}
