<?php

use App\Modules\Platform\OperationalSettings\Presentation\Controllers\OperationalSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('platform/operational-settings')->name('platform.operational-settings.')->group(function (): void {
    Route::get('/', [OperationalSettingsController::class, 'index'])->name('index');
    Route::put('/', [OperationalSettingsController::class, 'update'])->name('update');
});
