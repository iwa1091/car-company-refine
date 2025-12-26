<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * 属性名（日本語）
     */
    public function attributes(): array
    {
        return [
            'email' => 'メールアドレス',
        ];
    }

    /**
     * メッセージ
     */
    public function messages(): array
    {
        return [
            'required' => ':attributeは必須です。',
            'email' => ':attributeは有効な形式で入力してください。'
        ];
    }

    /**
     * バリデーション前の整形（余計な不一致を防ぐ）
     * - 前後の空白除去
     * - 小文字化（メールは通常小文字扱いが安全）
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
        ]);
    }
}
