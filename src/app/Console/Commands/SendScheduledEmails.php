<?php

namespace App\Console\Commands;

use App\Mail\ReservationReminderMail;
use App\Mail\ReservationThanksMail;
use App\Models\Reservation;
use App\Models\ScheduledEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * artisan コマンド名：
     *   php artisan emails:send-scheduled
     */
    protected $signature = 'emails:send-scheduled';

    /**
     * The console command description.
     */
    protected $description = 'scheduled_emails テーブルから送信対象のメールを取得して送信するコマンド';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $this->info('Checking scheduled_emails...');

        // pending かつ send_at が現在時刻以前のものを対象にする
        ScheduledEmail::pending()
            ->where('send_at', '<=', $now)
            ->orderBy('send_at')
            ->chunkById(100, function ($emails) {
                foreach ($emails as $scheduled) {
                    $this->processScheduledEmail($scheduled);
                }
            });

        $this->info('Done.');

        return Command::SUCCESS;
    }

    /**
     * 個々の ScheduledEmail レコードを処理する
     */
    protected function processScheduledEmail(ScheduledEmail $scheduled): void
    {
        // すでに送信済み or 失敗扱いの場合は何もしない（念のため）
        if ($scheduled->status !== 'pending') {
            return;
        }

        $this->line("Processing #{$scheduled->id} ({$scheduled->type}) to {$scheduled->email}");

        try {
            // polymorphic 関連から予約（Reservation）を取得
            $related = $scheduled->related;

            if (!$related) {
                // 関連レコードが削除されているなど
                Log::warning('[ScheduledEmail] 関連レコードが見つかりません。', [
                    'scheduled_email_id' => $scheduled->id,
                    'type'               => $scheduled->type,
                    'email'              => $scheduled->email,
                ]);

                $scheduled->update([
                    'status'        => 'failed',
                    'error_message' => '関連レコードが見つかりませんでした。',
                    'sent_at'       => now(),
                ]);

                return;
            }

            // メール送信の実行
            $this->sendEmailByType($scheduled, $related);

            // 正常に送信できたらステータスを更新
            $scheduled->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('[ScheduledEmail] メール送信中にエラーが発生しました。', [
                'scheduled_email_id' => $scheduled->id,
                'type'               => $scheduled->type,
                'email'              => $scheduled->email,
                'exception'          => $e->getMessage(),
            ]);

            $scheduled->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at'       => now(),
            ]);
        }
    }

    /**
     * type に応じて送信するメールを振り分ける
     *
     * 現状は Reservation のみを想定。
     */
    protected function sendEmailByType(ScheduledEmail $scheduled, $related): void
    {
        // 将来、別のモデル（Order など）が来る可能性もあるので、
        // まず Reservation かどうかを確認しておく
        if ($related instanceof Reservation) {
            $this->sendReservationEmail($scheduled, $related);
            return;
        }

        // 未対応の related_type の場合
        Log::warning('[ScheduledEmail] 未対応の related_type です。', [
            'scheduled_email_id' => $scheduled->id,
            'type'               => $scheduled->type,
            'related_type'       => $scheduled->related_type,
        ]);

        throw new \RuntimeException('未対応の related_type です。');
    }

    /**
     * 予約（Reservation）に関するメール送信を行う
     */
    protected function sendReservationEmail(ScheduledEmail $scheduled, Reservation $reservation): void
    {
        switch ($scheduled->type) {
            case 'reservation_reminder_2days':
                Mail::to($scheduled->email)
                    ->send(new ReservationReminderMail($reservation, 2));
                break;

            case 'reservation_reminder_1day':
                Mail::to($scheduled->email)
                    ->send(new ReservationReminderMail($reservation, 1));
                break;

            case 'reservation_thanks_3days':
                Mail::to($scheduled->email)
                    ->send(new ReservationThanksMail($reservation, 'after_3days'));
                break;

            case 'reservation_thanks_1month':
                Mail::to($scheduled->email)
                    ->send(new ReservationThanksMail($reservation, 'after_1month'));
                break;

            default:
                // 予約系の type なのに、未定義の場合はエラーにしてログへ
                Log::warning('[ScheduledEmail] 未対応の予約メール type です。', [
                    'scheduled_email_id' => $scheduled->id,
                    'type'               => $scheduled->type,
                ]);
                throw new \RuntimeException('未対応の予約メール type です。');
        }
    }
}
