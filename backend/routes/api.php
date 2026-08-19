<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvatarController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');

Route::middleware('jwt.guard')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('uploads/avatar', [AvatarController::class, 'store'])->middleware('throttle:30,1');

    Route::get('dashboard', DashboardController::class);
    Route::get('activity-logs', [ActivityLogController::class, 'index']);

    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);
    Route::apiResource('tasks', TaskController::class);
    Route::patch('tasks/{task}/move', [TaskController::class, 'move']);

    // Изменяющие операции по справочникам и аккаунты — только admin и manager.
    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('users', UserController::class);

        Route::post('employees', [EmployeeController::class, 'store']);
        Route::put('employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);

        Route::post('departments', [DepartmentController::class, 'store']);
        Route::put('departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);
    });
});
