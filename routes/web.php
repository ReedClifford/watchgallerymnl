<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\WatchController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicPageController;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AboutUsController;


Route::get('/', [PublicPageController::class, 'welcome'])
    ->name('welcome');
Route::get('/watches/{identifier}', [PublicPageController::class, 'show'])
    ->name('public.watches.show');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::patch('/watches/{watch}/mark-as-sold', [WatchController::class, 'markAsSold'])
            ->name('watches.mark-as-sold');

        Route::resource('watches', WatchController::class)
            ->except(['create', 'edit', 'show']);

        Route::resource('transactions', TransactionController::class)
        ->except(['create', 'edit', 'show']);



          Route::get('/about-us', [AboutUsController::class, 'edit'])
            ->name('about-us.edit');

        Route::post('/about-us', [AboutUsController::class, 'update'])
            ->name('about-us.update');
    });

require __DIR__.'/auth.php';
