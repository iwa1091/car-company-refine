<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * 予約リマインド・サンクスメール送信用
         *
         * emails:send-scheduled コマンドは、
         * app/Console/Commands/SendScheduledEmails.php で定義します。
         * （毎分実行し、「scheduled_emails」テーブルから送信対象を取得して送信）
         */
        $schedule->command('emails:send-scheduled')
            ->everyMinute()
            ->withoutOverlapping(); // 送信処理が長引いても重複実行しないようにする
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // app/Console/Commands 配下の Artisan コマンドを自動読み込み
        $this->load(__DIR__ . '/Commands');

        // routes/console.php 内のコマンド定義も読み込み
        require base_path('routes/console.php');
    }
}
