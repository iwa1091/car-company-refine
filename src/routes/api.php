<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authenticate;

use App\Http\Controllers\Public\MenuController;

// Public APIs
use App\Http\Controllers\Api\ReservationController as PublicReservationController;
use App\Http\Controllers\Api\ReservationCancelController;

// Admin session APIs (Next.js)
use App\Http\Controllers\Api\AdminSessionController;

// Admin APIs
use App\Http\Controllers\Api\AdminController as ApiAdminController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\AdminBlockController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CategoryController;

Route::get('/public/menu', [MenuController::class, 'apiPublicMenu']);

/*
|--------------------------------------------------------------------------
| Public APIs (for Next.js user pages)
|--------------------------------------------------------------------------
*/
Route::get('/reservations/month-schedule', [PublicReservationController::class, 'monthSchedule']);
Route::get('/business-hours/closed-dates', [PublicReservationController::class, 'closedDates']);
Route::get('/reservations/check', [PublicReservationController::class, 'checkAvailability']);
Route::post('/reservations', [PublicReservationController::class, 'store']);

// サービス一覧（予約フォーム用）
Route::get('/services', [ServiceController::class, 'apiList']);

// ✅ キャンセル（Nextページが叩くAPI）
// ✅ 重要：web.php を消しても route('reservations.cancel.show') が解決できるよう「ルート名」を付与
Route::prefix('reservations')->group(function () {
    Route::get('cancel/{token}', [ReservationCancelController::class, 'show'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('reservations.cancel.show');

    Route::post('cancel/{token}', [ReservationCancelController::class, 'cancel'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('reservations.cancel');
});

/*
|--------------------------------------------------------------------------
| Admin Session APIs (cookie session + CSRF 前提)
|--------------------------------------------------------------------------
| ※ これが「web ミドルウェア必須」ポイント
*/
Route::middleware('web')->group(function () {
    Route::get('/csrf', [AdminSessionController::class, 'csrf']);

    Route::prefix('admin')->group(function () {
        Route::post('session', [AdminSessionController::class, 'login']);

        Route::middleware([Authenticate::class . ':admin'])->group(function () {
            Route::get('me', [AdminSessionController::class, 'me']);
            Route::delete('session', [AdminSessionController::class, 'logout']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin APIs (for Next.js admin pages)
|--------------------------------------------------------------------------
| ※ cookie session を使うなら、ここも web ミドルウェア必須
*/
Route::middleware(['web', Authenticate::class . ':admin'])->prefix('admin')->group(function () {

    // timetable
    Route::get('timetable', [AdminReservationController::class, 'apiTimetable']);

    // blocks
    Route::post('blocks', [AdminBlockController::class, 'store']);
    Route::put('blocks/{id}', [AdminBlockController::class, 'update']);
    Route::delete('blocks/{id}', [AdminBlockController::class, 'destroy']);

    // reservations
    Route::get('reservations', [AdminReservationController::class, 'apiIndex']);
    Route::get('reservations/{id}', [AdminReservationController::class, 'apiShow']);
    Route::put('reservations/{id}', [AdminReservationController::class, 'apiUpdate']);
    Route::delete('reservations/{id}', [AdminReservationController::class, 'apiDestroy']);

    // services
    Route::get('services', [ServiceController::class, 'apiIndex']);
    Route::post('services', [ServiceController::class, 'apiStore']);
    Route::get('services/{service}', [ServiceController::class, 'apiShow']);
    Route::put('services/{service}', [ServiceController::class, 'apiUpdate']);
    Route::delete('services/{service}', [ServiceController::class, 'apiDestroy']);
    Route::patch('services/{service}/toggle', [ServiceController::class, 'apiToggle']);

    // categories
    Route::get('categories', [CategoryController::class, 'apiIndex']);
    Route::post('categories', [CategoryController::class, 'apiStore']);

    // schedules
    Route::get('schedules', [ApiAdminController::class, 'indexSchedules']);
    Route::post('schedules', [ApiAdminController::class, 'storeSchedule']);
    Route::put('schedules/{schedule}', [ApiAdminController::class, 'updateSchedule']);
    Route::delete('schedules/{schedule}', [ApiAdminController::class, 'destroySchedule']);
});

/*
|--------------------------------------------------------------------------
| Business Hours APIs
|--------------------------------------------------------------------------
*/
Route::prefix('business-hours')->group(function () {
    Route::get('/weekly', [BusinessHourController::class, 'getWeekly']);
    Route::get('/', [BusinessHourController::class, 'getHours']);

    Route::middleware(['web', Authenticate::class . ':admin'])->group(function () {
        Route::put('/weekly', [BusinessHourController::class, 'updateWeekly']);
        Route::put('/', [BusinessHourController::class, 'updateHours']);
    });
});
