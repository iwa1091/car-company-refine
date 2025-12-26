@extends('layout.app')

@section('title', $product->name . ' | 商品詳細')

@section('styles')
    @vite(['resources/css/pages/store/public_show.css'])
@endsection

@section('content')
<div class="product-detail-container">

    {{-- 左カラム：商品画像 --}}
    <div class="product-image-area">
        <img src="{{ asset('storage/' . $product->image_path) }}"
             alt="{{ $product->name }}"
             class="product-image">
    </div>

    {{-- 右カラム：商品情報 --}}
    <div class="product-info-area">

        {{-- ※ 戻るリンクはECサイトらしく「ページ上部右寄せ」へ --}}
        <div class="back-link-wrapper">
            <a href="{{ route('online-store.index') }}" class="back-link">
                ← 商品一覧へ戻る
            </a>
        </div>

        <h1 class="product-name">{{ $product->name }}</h1>

        <p class="product-description">
            {{ $product->description }}
        </p>

        <p class="product-price">
            ¥{{ number_format($product->price) }}
        </p>

        {{-- 在庫表示 --}}
        @if ($product->stock > 0)
            <p class="product-stock text-green-600">在庫: {{ $product->stock }} 点</p>
        @else
            <p class="product-stock text-red-500 font-bold">売り切れ</p>
        @endif

        {{-- 購入ボタン --}}
        <div class="buy-button-wrapper">
            @auth
                @if($product->stock > 0)
                    <form action="{{ route('online-store.checkout', $product->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="buy-button">
                            購入手続きへ
                        </button>
                    </form>
                @else
                    <button class="buy-button button-disabled" disabled>
                        売り切れ
                    </button>
                @endif
            @else
                <a href="{{ route('register') }}" class="buy-button">
                    購入には会員登録が必要です
                </a>
            @endauth
        </div>

    </div>
</div>
@endsection
