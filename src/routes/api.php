<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| ここでは JSON API のみを扱います。
| Inertia ページは web.php で処理します。
|--------------------------------------------------------------------------
*/

// ============================================================
// 🔐 ログイン中ユーザー情報（必要な場合に使用）
// ============================================================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// ============================================================
// 🕒 営業時間設定 API（BusinessHourController）
// ============================================================
use App\Http\Controllers\Admin\BusinessHourController;

Route::prefix('business-hours')->group(function () {
    Route::get('/weekly', [BusinessHourController::class, 'getWeekly']);
    Route::put('/weekly', [BusinessHourController::class, 'updateWeekly']);

    Route::get('/', [BusinessHourController::class, 'getHours']);
    Route::put('/', [BusinessHourController::class, 'updateHours']);
});


// ============================================================
// 🧑‍💼 管理者向け API
// ============================================================
//
// ※ 認証（auth:sanctum + admin guard）は必要なら後で追加できます。
//    現在はフロントの React 管理画面が動作するように公開しています。
// ============================================================

use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CustomerController;

Route::prefix('admin')->group(function () {

    // ============================================
    // 🗂 サービス管理 API
    // ============================================
    Route::get('services', [ServiceController::class, 'apiIndex']);
    Route::post('services', [ServiceController::class, 'apiStore']);
    Route::put('services/{service}', [ServiceController::class, 'apiUpdate']);
    Route::delete('services/{service}', [ServiceController::class, 'apiDestroy']);

    // ============================================
    // 📅 管理：予約一覧 API（React 管理画面用）
    // ============================================
    Route::get('reservations', [AdminReservationController::class, 'apiIndex']);
    Route::delete('reservations/{id}', [AdminReservationController::class, 'apiDestroy']);

    // ============================================
    // 👤 顧客管理 API
    // ============================================
    Route::get('customers', [CustomerController::class, 'apiIndex']);
    Route::get('customers/{id}', [CustomerController::class, 'apiShow']);
    Route::put('customers/{id}', [CustomerController::class, 'apiUpdate']);
    Route::delete('customers/{id}', [CustomerController::class, 'apiDestroy']);
});


// ============================================================
// 🧾 一般ユーザー向け API（予約フォーム・メニュー表示）
// ============================================================

use App\Http\Controllers\Api\ReservationController as ApiReservationController;

// サービス一覧（メニュー・料金ページ用の JSON データ）
Route::get('/services', [ServiceController::class, 'apiList']);

// 予約作成
Route::post('/reservations', [ApiReservationController::class, 'store']);

// 予約可能時間のチェック
Route::get('/reservations/check', [ApiReservationController::class, 'checkAvailability']);
