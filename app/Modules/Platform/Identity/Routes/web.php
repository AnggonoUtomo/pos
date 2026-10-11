<?php

use App\Modules\Platform\Identity\Presentation\Controllers\AccessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('platform/identity')->name('platform.identity.')->group(function () {
    Route::get('/', [AccessController::class, 'index'])->name('index');
    Route::post('/roles', [AccessController::class, 'store'])->name('roles.store');
    Route::put('/roles/{id}', [AccessController::class, 'update'])->whereNumber('id')->name('roles.update');
    Route::delete('/roles/{id}', [AccessController::class, 'destroy'])->whereNumber('id')->name('roles.destroy');
    Route::put('/roles/{id}/permissions', [AccessController::class, 'permissions'])->whereNumber('id')->name('roles.permissions');
    Route::put('/users/{id}/roles', [AccessController::class, 'roles'])->whereNumber('id')->name('users.roles');
});
