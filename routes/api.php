<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/user')->controller(UserController::class)->group(function () {
    Route::get('/', 'index')->name('user.index');

    Route::post('/', 'store')->name('user.store');

    Route::prefix('/{id}')->group(function () {
        Route::get('/', 'show')->name('user.show');

        Route::put('/', 'update')->name('user.update');

        Route::delete('/', 'destroy')->name('user.destroy');
    });
});
