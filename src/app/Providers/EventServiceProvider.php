<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * イベントリスナーのマッピング
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,  // メール送信のリスナー
        ],
    ];

    /**
     * イベントサービスの登録。
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
