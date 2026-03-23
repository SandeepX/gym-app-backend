<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'Gym API is running.',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]));

    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:api', 'check', 'throttle:api'])->group(function () {

        Route::get('users', [UserController::class, 'index']);
        Route::get('users/by-role', [UserController::class, 'byRole']);
        Route::get('users/stats', [UserController::class, 'stats']);

        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::post('/roles/assign', [RoleController::class, 'assignToUser']);
        Route::apiResource('roles', RoleController::class);

        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile/{userId}', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        Route::prefix('members')->group(function () {
            Route::post('/{member}/assign-trainer', [MemberController::class, 'assignTrainer']);
            Route::delete('/{member}/remove-trainer', [MemberController::class, 'removeTrainer']);
            Route::get('/stats', [MemberController::class, 'stats']);
            Route::get('/{id}', [MemberController::class, 'show']);
            Route::post('/delete/{id}', [MemberController::class, 'destroy']);

            Route::apiResource('/', MemberController::class)->except(['show', 'delete', 'edit']);
        });

        Route::prefix('trainers')->group(function () {
            Route::get('/my-members', [TrainerController::class, 'myMembers']);
            Route::post('/{user}/assign-member', [TrainerController::class, 'assignMember']);
            Route::delete('/{user}/remove-member', [TrainerController::class, 'removeMember']);
            Route::apiResource('/', TrainerController::class);
        });

        // Plans
        Route::apiResource('plans', PlanController::class);

        // Subscriptions
        Route::prefix('subscriptions')->group(function () {
            Route::post('/{subscription}/freeze', [SubscriptionController::class, 'freeze']);
            Route::post('/{subscription}/unfreeze', [SubscriptionController::class, 'unfreeze']);
            Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew']);
            Route::apiResource('/', SubscriptionController::class);
        });

        // Payments
        Route::get('payments/stats', [PaymentController::class, 'stats'])->name('payments.stats');
        Route::apiResource('payments', PaymentController::class);

        // Attendance
        Route::prefix('attendance')->group(function () {
            Route::get('/today', [AttendanceController::class, 'today']);
            Route::post('/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('/check-out/{attendance}', [AttendanceController::class, 'checkOut']);
            Route::get('/member/{member}', [AttendanceController::class, 'memberHistory']);
            Route::apiResource('/', AttendanceController::class);
        });

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/revenue', [DashboardController::class, 'revenue']);
            Route::get('/members', [DashboardController::class, 'members']);
            Route::get('/attendance', [DashboardController::class, 'attendance']);
            Route::get('/subscriptions', [DashboardController::class, 'subscriptions']);
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/expiring-subscriptions', [ReportController::class, 'expiringSubscriptions']);
            Route::get('/expired-subscriptions', [ReportController::class, 'expiredSubscriptions']);
            Route::get('/inactive-members', [ReportController::class, 'inactiveMembers']);
            Route::get('/revenue', [ReportController::class, 'revenue']);
            Route::get('/attendance', [ReportController::class, 'attendance']);
        });
    });
});
