<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes（JSONのみ）
|--------------------------------------------------------------------------
| Inertia ページは web.php で処理します。
|--------------------------------------------------------------------------
*/

// ============================================================
// 🕒 営業時間設定 API（BusinessHourController）
// ============================================================
use App\Http\Controllers\Admin\BusinessHourController;

Route::prefix('business-hours')->group(function () {
    // ReservationList.jsx が /api/business-hours/weekly を叩くため残す
    Route::get('/weekly', [BusinessHourController::class, 'getWeekly']);
    Route::put('/weekly', [BusinessHourController::class, 'updateWeekly']);

    // ReservationEdit.jsx が /api/business-hours を叩くため残す
    Route::get('/', [BusinessHourController::class, 'getHours']);
    Route::put('/', [BusinessHourController::class, 'updateHours']);
});


// ============================================================
// 🧑‍💼 管理者向け API（React 管理画面 fetch 用）
// ============================================================
//
// ※ 現状のフロントが /api/admin/... を叩いている前提で維持します。
//    （方式Aで /admin/api に寄せるのは後で段階的に）
// ============================================================

use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ServiceController;
// ✅ Schedule モデルが存在しない環境でも落ちないように、Schedule系は Api\AdminController に寄せる
use App\Http\Controllers\Api\AdminController as ApiAdminController;

Route::prefix('admin')->group(function () {

    // サービス管理 API（React管理画面用に残す）
    Route::get('services', [ServiceController::class, 'apiIndex']);
    Route::post('services', [ServiceController::class, 'apiStore']);
    Route::put('services/{service}', [ServiceController::class, 'apiUpdate']);
    Route::delete('services/{service}', [ServiceController::class, 'apiDestroy']);

    // 予約一覧/削除 API（ReservationList.jsx が使用）
    Route::get('reservations', [AdminReservationController::class, 'apiIndex']);
    Route::delete('reservations/{id}', [AdminReservationController::class, 'apiDestroy']);

    // ✅ スケジュール管理 API（Scheduleモデルを使わない実装に合わせる）
    // ※ ルートパラメータ名 {schedule} は従来の形のままでも、Controller側が型ヒント無しなのでモデルバインドされません
    Route::get('schedules', [ApiAdminController::class, 'indexSchedules']);
    Route::post('schedules', [ApiAdminController::class, 'storeSchedule']);
    Route::put('schedules/{schedule}', [ApiAdminController::class, 'updateSchedule']);
    Route::delete('schedules/{schedule}', [ApiAdminController::class, 'destroySchedule']);
});


// ============================================================
// 🧾 一般ユーザー向け API（予約フォーム用）
// ============================================================

use App\Http\Controllers\Api\ReservationController as ApiReservationController;

Route::get('/reservations/month-schedule', [ApiReservationController::class, 'monthSchedule']);

// サービス一覧（予約フォームが参照している可能性が高いので残す）
Route::get('/services', [ServiceController::class, 'apiList']);

// 予約作成
Route::post('/reservations', [ApiReservationController::class, 'store']);

// 予約可能時間のチェック
Route::get('/reservations/check', [ApiReservationController::class, 'checkAvailability']);
