@extends('layout.app')

@section('title', 'オンラインストア')

@section('styles')
    {{-- オンラインストア専用CSS --}}
    @vite(['resources/css/pages/store/products_index.css'])

    {{-- Font Awesome --}}
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content')
<div class="store-page">
    <div class="store-container">

        {{-- ===========================
             ヘッダー
        ============================ --}}
        <div class="store-header">
            <h1 class="store-title">商品販売</h1>
            <p class="store-subtitle">
                サロンで使用している厳選されたアイケア商品を販売しております。<br>
                ご自宅でのケアにお役立てください。
            </p>
        </div>

        {{-- ===========================
             検索 & 並び替え
        ============================ --}}
        <div class="search-sort-container">
            <form action="{{ route('online-store.index') }}" method="GET" class="search-form">

                {{-- キーワード検索 --}}
                <div class="input-group">
                    <input type="text"
                           name="keyword"
                           class="keyword-input"
                           placeholder="商品名で検索"
                           value="{{ request('keyword') }}">
                </div>

                {{-- 並び替え --}}
                <div class="input-group">
                    <select name="sort" class="sort-select">
                        <option value="">価格で並び替え</option>
                        <option value="high_price" {{ request('sort') == 'high_price' ? 'selected' : '' }}>高い順に表示</option>
                        <option value="low_price" {{ request('sort') == 'low_price' ? 'selected' : '' }}>低い順に表示</option>
                    </select>
                </div>

                {{-- 検索ボタン --}}
                <button type="submit" class="search-button">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    検索
                </button>

            </form>
        </div>

        {{-- ===========================
             商品一覧
        ============================ --}}
        <section class="products-section">
            <div class="product-grid">

                {{-- ===========================
                     外部オンラインストア（固定リンク）
                ============================ --}}
                <div class="product-card store-external-card">
                    <a href="https://member.laesse0715.com/U100496"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="product-link">
                        <div class="product-image-container store-external-image-container">
                            <img
                                src="{{ asset('img/laesse-img.jpg') }}"
                                alt="外部オンラインストアへのリンク"
                                class="product-image store-external-image"
                            >
                        </div>
                    </a>
                </div>

                @forelse ($products as $product)

                    <div class="product-card">
                        <a href="{{ route('online-store.show', $product->id) }}" class="product-link">

                            {{-- 商品画像 --}}
                            <div class="product-image-container">
                                <img src="{{ asset('storage/' . $product->image_path) }}"
                                     alt="{{ $product->name }}"
                                     class="product-image">
                            </div>

                            {{-- 商品情報 --}}
                            <div class="product-info">

                                <h3 class="product-name">{{ $product->name }}</h3>

                                <p class="product-description">
                                    {{ Str::limit($product->description, 50) }}
                                </p>

                                <span class="product-price">¥{{ number_format($product->price) }}</span>

                                {{-- 在庫表示 --}}
                                @if($product->stock > 0)
                                    <p class="product-stock" style="color:#2d7a32;">
                                        在庫: {{ $product->stock }} 点
                                    </p>
                                @else
                                    <p class="product-stock" style="color:#c0392b; font-weight:600;">
                                        売り切れ
                                    </p>
                                @endif

                            </div>
                        </a>
                    </div>

                @empty
                    <p class="no-products-message">
                        {{-- 商品が見つかりませんでした。 --}}
                    </p>
                @endforelse

            </div>
        </section>

        {{-- ===========================
             ページネーション
        ============================ --}}
        <div class="pagination-content">
            {{ $products->appends(request()->query())->links() }}
        </div>

    </div>
</div>
@endsection
