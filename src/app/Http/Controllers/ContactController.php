<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactNotification;
use App\Mail\ContactAutoReply;

class ContactController extends Controller
{
    public function showForm()
    {
        return view('contact');
    }

    public function sendEmail(ContactRequest $request)
    {
        $validated = $request->validated();

        try {
            /* ================================
               1. 管理者宛メールを送信
            ================================= */
            Mail::to(config('mail.to.address'))
                ->send(new ContactNotification($validated));

            /* ================================
               2. お客様宛メールを送信（自動返信）
            ================================= */
            Mail::to($validated['email'])
                ->send(new ContactAutoReply($validated));

            return redirect()
                ->route('contact.form')
                ->with('success', 'お問い合わせありがとうございます。確認メールを送信しました。');

        } catch (\Exception $e) {

            Log::error('お問い合わせメール送信エラー：' . $e->getMessage());

            return back()
                ->with('error', 'メール送信中にエラーが発生しました。時間をおいて再度お試しください。')
                ->withInput();
        }
    }
}
