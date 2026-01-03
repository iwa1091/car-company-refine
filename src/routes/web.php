<?php

use Illuminate\Support\Facades\Route;

// 共通ミドルウェア
use App\Http\Middleware\Authenticate;

// 一般（予約）
use App\Http\Controllers\ReservationController;
// 一般（予約キャンセル：メールリンク/トークン）
use App\Http\Controllers\ReservationCancelController;

// 管理者
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ScheduleController;

// サービス/カテゴリ（予約が依存するため残す）
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CategoryController;

// ★ Dashboard 側で参照されがちなため復活（Ziggyエラー回避）
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CustomerController;

/*
|--------------------------------------------------------------------------
| 一般（予約機能 + メニュー閲覧）
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('home'))->name('top');

/**
 * ✅ サービスメニューを一般ユーザーが閲覧できるようにする
 * - menu_price.blade.php を表示する想定
 * - 既存の ServiceController::publicIndex を利用（不一致を起こしにくい）
 */
Route::get('/menu_price', [ServiceController::class, 'publicIndex'])->name('menu_price');

// 予約ページ（Inertia/React）
Route::get('/reservation', [ReservationController::class, 'form'])->name('reservation.form');
Route::post('/reservation/store', [ReservationController::class, 'store'])->name('reservation.store');

/**
 * ✅ 予約キャンセル（メールリンク/トークン方式）
 * - 予約完了メールに付与した cancelUrl から遷移
 * - 確認画面 → 理由入力 → キャンセル確定（ユーザー/管理者へ通知）
 */
Route::get('/reservations/cancel/{token}', [ReservationCancelController::class, 'show'])
    ->name('reservations.cancel.show');

Route::post('/reservations/cancel/{token}', [ReservationCancelController::class, 'cancel'])
    ->name('reservations.cancel');


/*
|--------------------------------------------------------------------------
| 管理者（予約管理 + 営業時間管理）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // 管理者ログイン
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.store');

    // 管理者ログアウト
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    // admin guard 必須領域
    Route::middleware([Authenticate::class . ':admin'])->group(function () {

        // ダッシュボード
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 予約管理（Inertia）
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{id}', [AdminReservationController::class, 'update'])->name('reservations.update');
        Route::post('/reservations/{id}/delete', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // 営業時間（Inertia）
        Route::get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');

        // サービス/カテゴリ（必要最小限）
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::patch('services/{service}/toggle', [ServiceController::class, 'toggleActive'])->name('services.toggle');
        Route::resource('categories', CategoryController::class)->except(['show']);

        // スケジュール
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::get('/data', [ScheduleController::class, 'getData'])->name('data');
            Route::post('/weekly', [ScheduleController::class, 'storeOrUpdateWeekly'])->name('store.weekly');
            Route::post('/exception', [ScheduleController::class, 'storeOrUpdateException'])->name('store.exception');
            Route::delete('/exception', [ScheduleController::class, 'destroyException'])->name('destroy.exception');
        });

        // ★ Dashboard 互換（Ziggyエラー回避）
        Route::resource('products', AdminProductController::class);

        // 顧客一覧（Dashboard側にリンクが残っている可能性が高いので安全に残す）
        Route::get('/users', [CustomerController::class, 'index'])->name('users.index');
    });
});
