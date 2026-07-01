<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\WatchController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\AboutUsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'welcome'])
    ->name('welcome');

Route::get('/watches/{identifier}', [PublicPageController::class, 'show'])
    ->name('public.watches.show');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Watch custom actions
        |--------------------------------------------------------------------------
        | Final route names:
        | admin.watches.mark-as-sold
        | admin.watches.update-status
        | admin.watches.toggle-visibility
        | admin.watches.duplicate
        */

        Route::patch('/watches/{watch}/mark-as-sold', [WatchController::class, 'markAsSold'])
            ->name('watches.mark-as-sold');

        Route::patch('/watches/{watch}/status', [WatchController::class, 'updateStatus'])
            ->name('watches.update-status');

        Route::patch('/watches/{watch}/visibility', [WatchController::class, 'toggleVisibility'])
            ->name('watches.toggle-visibility');

        Route::post('/watches/{watch}/duplicate', [WatchController::class, 'duplicate'])
            ->name('watches.duplicate');

        Route::resource('watches', WatchController::class)
            ->except(['create', 'edit', 'show']);

        Route::resource('transactions', TransactionController::class)
            ->except(['create', 'edit', 'show']);

        Route::get('/about-us', [AboutUsController::class, 'edit'])
            ->name('about-us.edit');

        Route::post('/about-us', [AboutUsController::class, 'update'])
            ->name('about-us.update');
    });

require __DIR__ . '/auth.php';