{{-- resources/views/checkout/success.blade.php --}}
@extends('layout.app')

@section('title', '決済完了')

@section('content')
<div class="checkout-success">
    <h1 class="text-center text-green-600 mt-10 text-3xl font-bold">決済が完了しました</h1>
    <p class="text-center mt-6 text-gray-700">
        ご購入ありがとうございます。<br>
        ご登録のメールアドレスに購入内容を送信しました。
    </p>

    <div class="text-center mt-10">
        <a href="{{ route('online-store.index') }}" class="button button-primary">
            オンラインストアに戻る
        </a>
    </div>
</div>
@endsection
