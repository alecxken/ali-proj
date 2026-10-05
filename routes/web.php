<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('login', [Api\AuthController::class, 'login'])->middleware('guest');

    Route::middleware('auth')->group(function () {
        Route::get('me', [Api\AuthController::class, 'me']);
        Route::post('logout', [Api\AuthController::class, 'logout']);
        Route::put('me/password', [Api\AuthController::class, 'password']);

        Route::get('dashboard', Api\DashboardController::class);

        Route::apiResource('projects', Api\ProjectController::class);
        Route::post('projects/{project}/milestones', [Api\MilestoneController::class, 'store']);
        Route::put('milestones/{milestone}', [Api\MilestoneController::class, 'update']);
        Route::delete('milestones/{milestone}', [Api\MilestoneController::class, 'destroy']);

        Route::get('projects/{project}/weekly-update', [Api\WeeklyUpdateController::class, 'show']);
        Route::put('projects/{project}/weekly-update', [Api\WeeklyUpdateController::class, 'save']);

        Route::apiResource('issues', Api\IssueController::class)->except('show');
        Route::apiResource('users', Api\UserController::class)->only(['index', 'store', 'update']);

        Route::get('reports/preview', [Api\ReportController::class, 'preview']);
        Route::get('reports', [Api\ReportController::class, 'history']);
        Route::post('reports', [Api\ReportController::class, 'generate']);
        Route::get('reports/{run}/download', [Api\ReportController::class, 'download'])->name('reports.download');
    });

    Route::any('{any}', fn () => response()->json(['message' => 'Not found'], 404))->where('any', '.*');
});

// Everything else is the Vue single-page app.
Route::view('/{any?}', 'app')->where('any', '.*')->name('app');
