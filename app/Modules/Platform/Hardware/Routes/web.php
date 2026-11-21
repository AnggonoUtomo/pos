<?php

use App\Modules\Platform\Hardware\Presentation\Controllers\HardwareController;
use App\Modules\Platform\Hardware\Presentation\Controllers\LabelJobController;
use App\Modules\Platform\Hardware\Presentation\Controllers\ScannerController;
use App\Modules\Platform\Hardware\Presentation\Middleware\EnsureVirtualHardwareEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', EnsureVirtualHardwareEnabled::class, 'auth', 'verified'])
    ->prefix('platform/hardware/scanner')->name('platform.hardware.scanner.')->group(function (): void {
        Route::get('/', [ScannerController::class, 'index'])->name('index');
        Route::get('/resolve', [ScannerController::class, 'resolve'])->middleware('throttle:120,1')->name('resolve');
    });

Route::middleware(['web', EnsureVirtualHardwareEnabled::class, 'auth', 'verified'])
    ->prefix('platform/hardware')->name('platform.hardware.')->group(function (): void {
        Route::get('/', [HardwareController::class, 'index'])->name('index');
        Route::get('/catalog', [HardwareController::class, 'catalog'])->middleware('throttle:120,1')->name('catalog');
        Route::post('/printers', [HardwareController::class, 'store'])->name('printers.store');
        Route::patch('/printers/{printer}', [HardwareController::class, 'update'])->whereUlid('printer')->name('printers.update');
        Route::patch('/printers/{printer}/status', [HardwareController::class, 'status'])->whereUlid('printer')->name('printers.status');
        Route::delete('/printers/{printer}', [HardwareController::class, 'destroy'])->whereUlid('printer')->name('printers.destroy');
        Route::post('/printers/{printer}/restore', [HardwareController::class, 'restore'])->whereUlid('printer')->name('printers.restore');
        Route::get('/labels', [HardwareController::class, 'history'])->name('labels.index');
        Route::post('/labels', [LabelJobController::class, 'store'])->middleware('throttle:30,1')->name('labels.store');
        Route::post('/labels/{job}/dispatch', [LabelJobController::class, 'dispatch'])->whereUlid('job')->middleware('throttle:30,1')->name('labels.dispatch');
        Route::post('/labels/{job}/outcome', [LabelJobController::class, 'outcome'])->whereUlid('job')->name('labels.outcome');
        Route::post('/labels/{job}/reprint', [LabelJobController::class, 'reprint'])->whereUlid('job')->middleware('throttle:30,1')->name('labels.reprint');
    });
