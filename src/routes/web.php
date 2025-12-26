<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 共通ミドルウェア
use App\Http\Middleware\Authenticate;

// 一般ページ
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;

// Stripe
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;

// 認証（React 用）
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;  // Fortify標準ではなくカスタムVerifyEmailControllerを使用

// 管理者
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ScheduleController;

// マイページ
use App\Http\Controllers\MypageReservationLinkController;
use App\Http\Controllers\MypageController;

/*
|--------------------------------------------------------------------------
| マイページ（認証 + メール認証済）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/mypage/link-reservation', [MypageReservationLinkController::class, 'link'])
        ->name('mypage.link-reservation');

    Route::get('/mypage', [MypageController::class, 'index'])
        ->name('mypage.index');
});

/*
|--------------------------------------------------------------------------
| 管理者ルート
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    Route::middleware([Authenticate::class . ':admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 予約関連
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{id}', [AdminReservationController::class, 'update'])->name('reservations.update');
        Route::post('/reservations/{id}/delete', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // 営業時間設定
        Route::get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');

        // 商品・メニュー・カテゴリ
        Route::resource('products', AdminProductController::class);
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

        // 顧客一覧（Admin/UserList.jsx と CustomerController@index 用）
        Route::get('/users', [CustomerController::class, 'index'])->name('users.index');
    });
});

/*
|--------------------------------------------------------------------------
| React 認証（ゲストのみ）
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {

    // Login
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Register
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Forgot Password（メール送信）
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    // Reset Password（パスワード再設定）
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| 認証済（メール認証前でもアクセス可）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // メール認証ページ（React）
    Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    // 認証メール再送
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // メール認証リンク（完了）
    Route::get(
        '/email/verify/{id}/{hash}',
        [VerifyEmailController::class, '__invoke'] // カスタム VerifyEmailController を使用
    )
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // プロフィール
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| 一般ページ
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('home'))->name('top');
Route::get('/menu_price', [ServiceController::class, 'publicIndex'])->name('menu_price');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');

/*
|--------------------------------------------------------------------------
| オンラインストア
|--------------------------------------------------------------------------
*/
Route::prefix('online-store')->name('online-store.')->group(function () {
    Route::get('/', [StoreController::class, 'index'])->name('index');
    Route::get('/products/{product}', [StoreController::class, 'show'])->name('show');
    Route::post('/checkout/{product}', [StripeController::class, 'checkout'])->name('checkout');
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['web', 'auth', Authenticate::class])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Stripe 戻り
|--------------------------------------------------------------------------
*/
Route::get('/checkout/success', [StoreController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel', [StoreController::class, 'cancel'])->name('checkout.cancel');

/*
|--------------------------------------------------------------------------
| お問い合わせ
|--------------------------------------------------------------------------
*/
Route::get('/contact', [ContactController::class, 'showForm'])->name('contact.form');
Route::post('/contact', [ContactController::class, 'sendEmail'])->name('contact.send');

/*
|--------------------------------------------------------------------------
| 予約ページ
|--------------------------------------------------------------------------
*/
Route::get('/reservation', [ReservationController::class, 'form'])->name('reservation.form');
Route::post('/reservation/store', [ReservationController::class, 'store'])->name('reservation.store');
