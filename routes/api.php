<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BodyMeasurementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkoutPlanController;
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

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        // Users
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::put('/{userId}', [UserController::class, 'updateProfile']);
            Route::get('/profile', [UserController::class, 'profile']);
            Route::get('/by-role', [UserController::class, 'byRole']);
            Route::get('/stats', [UserController::class, 'stats']);
            Route::post('/change-password', [UserController::class, 'changePassword']);
        });

        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::post('/roles/assign', [RoleController::class, 'assignToUser']);
        Route::apiResource('roles', RoleController::class);

        // Plans
        Route::post('plans/{planId}', [PlanController::class, 'delete']);
        Route::apiResource('plans', PlanController::class)->except(['destroy', 'edit']);

        // Workout Plans
        Route::post('workout-plans/{workoutPlanId}', [WorkoutPlanController::class, 'delete']);
        Route::apiResource('workout-plans', WorkoutPlanController::class)->except(['edit', 'destroy']);

        Route::prefix('members')->group(function () {
            Route::get('/stats', [MemberController::class, 'stats']);
            Route::get('/{id}', [MemberController::class, 'show']);

            // Trainer Assign or Remove
            Route::post('/{member}/assign-trainer', [MemberController::class, 'assignTrainer']);
            Route::post('/{member}/remove-trainer', [MemberController::class, 'removeTrainer']);

            Route::post('/delete/{id}', [MemberController::class, 'destroy']);
            Route::apiResource('/', MemberController::class)->except(['delete', 'edit']);

            // Member Body Measurements
            Route::post('/{member}/measurements', [BodyMeasurementController::class, 'store']);
            Route::put('measurements/{measurementId}', [BodyMeasurementController::class, 'update']);
            Route::get('{member}/measurements/progress', [BodyMeasurementController::class, 'progress']);

            // member workout plan
            Route::post('workout-plans/assign', [MemberController::class, 'assignToMember']);
            Route::get('{member}/workout-plan-details', [MemberController::class, 'memberWorkoutPlansDetails']);
        });

        Route::prefix('trainers')->group(function () {
            Route::get('/my-members', [TrainerController::class, 'myMembers']);
            Route::post('/{user}/assign-member', [TrainerController::class, 'assignMember']);
            Route::delete('/{user}/remove-member', [TrainerController::class, 'removeMember']);

            Route::apiResource('/', TrainerController::class);
        });

        // Subscriptions
        Route::prefix('subscriptions')->group(function () {
            Route::post('/{subscription}/freeze', [SubscriptionController::class, 'freeze']);
            Route::post('/{subscription}/unfreeze', [SubscriptionController::class, 'unfreeze']);
            Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew']);
            Route::apiResource('/', SubscriptionController::class);
        });

        // Payments
        Route::apiResource('payments', PaymentController::class);

        // Attendance
        Route::prefix('attendance')->group(function () {
            Route::get('/today', [AttendanceController::class, 'today']);
            Route::post('/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('/check-out/{attendance}', [AttendanceController::class, 'checkOut']);
            Route::get('/member/{member}', [AttendanceController::class, 'memberHistory']);
            Route::apiResource('/', AttendanceController::class);
        });

        // Equipment
        Route::get('equipment/stats', [EquipmentController::class, 'stats']);
        Route::get('equipment/due-maintenance', [EquipmentController::class, 'dueMaintenance']);
        Route::post('equipment/{equipment}/maintenance', [EquipmentController::class, 'logMaintenance']);
        Route::apiResource('equipment', EquipmentController::class);

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/revenue', [DashboardController::class, 'revenue']);
            Route::get('/members', [DashboardController::class, 'members']);
            Route::get('/attendance', [DashboardController::class, 'attendance']);
            Route::get('/subscriptions', [DashboardController::class, 'subscriptions']);
            Route::get('/payments', [DashboardController::class, 'paymentStats']);

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
