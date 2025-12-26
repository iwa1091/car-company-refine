<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * 認可設定（誰でも送信できるため true）
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|regex:/^[0-9\-]+$/|max:20',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:5',
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'お名前を入力してください。',
            'name.max'            => 'お名前は255文字以内で入力してください。',

            'phone.regex'         => '電話番号は数字とハイフンのみ使用できます。',
            'phone.max'           => '電話番号は20文字以内で入力してください。',

            'email.required'      => 'メールアドレスを入力してください。',
            'email.email'         => '正しい形式のメールアドレスを入力してください。',
            'email.max'           => 'メールアドレスは255文字以内で入力してください。',

            'subject.required'    => '件名を入力してください。',
            'subject.max'         => '件名は255文字以内で入力してください。',

            'message.required'    => 'お問い合わせ内容を入力してください。',
            'message.min'         => 'お問い合わせ内容は5文字以上でご記入ください。',
        ];
    }
}
