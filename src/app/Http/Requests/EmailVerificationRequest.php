<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailVerificationRequest extends FormRequest
{
    /**
     * バリデーションルール
     *
     * @return array
     */
    public function rules()
    {
        return [
            // ユーザーIDが必須で、usersテーブルに存在する必要がある
            'id' => 'required|exists:users,id',

            // ハッシュが必須で、文字列として処理
            'hash' => 'required|string',
        ];
    }

    /**
     * 認証の許可
     *
     * @return bool
     */
    public function authorize()
    {
        return true;  // 認証を許可
    }
}
