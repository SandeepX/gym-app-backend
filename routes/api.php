<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BodyMeasurementController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
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

        Route::get('trainers/my-members', [TrainerController::class, 'myMembers']);
        Route::post('trainers/{user}/assign-member', [TrainerController::class, 'assignMember']);
        Route::post('trainers/{user}/remove-member', [TrainerController::class, 'removeMember']);
        Route::apiResource('/trainers', TrainerController::class)->except(['edit', 'update']);

        // Subscriptions
        Route::post('subscriptions/{subscription}/freeze', [SubscriptionController::class, 'freeze']);
        Route::post('subscriptions/{subscription}/unfreeze', [SubscriptionController::class, 'unfreeze']);
        Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew']);
        Route::apiResource('/subscriptions', SubscriptionController::class)->except('edit');

        // Payments
        Route::apiResource('payments', PaymentController::class);

        // Attendance
        Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('attendance/check-out/{attendance}', [AttendanceController::class, 'checkout']);
        Route::get('attendance/member/{member}', [AttendanceController::class, 'memberHistory']);
        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::put('attendance/update/{attendanceId}', [AttendanceController::class, 'update']);

        // Equipment
        Route::get('equipment/due-maintenance', [EquipmentController::class, 'dueMaintenance']);
        Route::post('equipment/{equipment}/maintenance', [EquipmentController::class, 'logMaintenance']);
        Route::apiResource('equipment', EquipmentController::class);
    });
});
