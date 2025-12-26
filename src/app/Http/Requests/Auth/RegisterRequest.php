<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * 認可
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
            'name' => ['required', 'string', 'max:255'],

            // メール
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],

            // 電話番号（任意）
            'phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],

            // ★ パスワード（min / max を明示）
            'password' => [
                'required', 'string', 'confirmed', 'min:8', 'max:255'],
        ];
    }

    /**
     * 属性名（日本語）
     */
    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'phone' => '電話番号',
            'password' => 'パスワード',
            'password_confirmation' => '確認用パスワード',
        ];
    }

    /**
     * メッセージ
     */
    public function messages(): array
    {
        return [
            'required' => ':attributeは必須です。',
            'string' => ':attributeは文字で入力してください。',
            'email' => ':attributeは有効な形式で入力してください。',
            'unique' => 'この:attributeは既に登録されています。',
            'confirmed' => 'パスワードと確認用パスワードが一致しません。',
            'lowercase' => ':attributeは小文字で入力してください。',

            // ★ パスワード文字数（要件どおり）
            'password.min' => ':attributeは:min文字以上で入力してください。',
            'password.max' => ':attributeは:max文字以内で入力してください。',

            // 電話番号
            'phone.regex' => '電話番号はハイフンなしの10〜11桁の数字で入力してください。',
        ];
    }

    /**
     * バリデーション前の整形
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');

        $this->merge([
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
            'phone' => is_string($phone) ? preg_replace('/\D+/', '', $phone) : $phone,
        ]);
    }
}
