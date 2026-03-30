<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'check', 'throttle:api'])->group(function () {

        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/revenue', [DashboardController::class, 'revenue']);
            Route::get('/members', [DashboardController::class, 'members']);
            Route::get('/attendance', [DashboardController::class, 'attendance']);
            Route::get('/subscriptions', [DashboardController::class, 'subscriptions']);
            Route::get('/payments', [DashboardController::class, 'paymentStats']);
            Route::get('/payments', [DashboardController::class, 'equipmentStats']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/expiring-subscriptions', [ReportController::class, 'expiringSubscriptions']);
            Route::get('/expired-subscriptions', [ReportController::class, 'expiredSubscriptions']);
            Route::get('/inactive-members', [ReportController::class, 'inactiveMembers']);
            Route::get('/revenue', [ReportController::class, 'revenue']);
            Route::get('/attendance', [ReportController::class, 'attendance']);
        });
    });
});
