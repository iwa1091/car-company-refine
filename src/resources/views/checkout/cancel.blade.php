@extends('layout.app')

@section('title', '決済キャンセル')

@section('content')
<div class="text-center py-20">
    <h1 class="text-3xl text-[var(--salon-brown)] mb-6 font-handwriting">決済をキャンセルしました</h1>
    <p class="text-gray-600 mb-10">もう一度購入する場合は、商品ページからお試しください。</p>
    <a href="{{ route('online-store.index') }}" class="btn-secondary">商品一覧に戻る</a>
</div>
@endsection
