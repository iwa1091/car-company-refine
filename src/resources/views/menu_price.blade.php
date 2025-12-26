@extends('layout.app')

@section('title', 'メニュー・料金')

{{-- ページ専用CSS（新デザイン） --}}
@section('styles')
    @vite(['resources/css/pages/menu_price/menu_price.css'])
@endsection

@section('content')
<div class="menu-page-container">
    <div class="menu-inner">

        {{-- ページヘッダー --}}
        <div class="menu-header">
            <h1 class="menu-title">メニュー・料金</h1>
            <p class="menu-description">
                お客様のご希望に合わせて、様々なメニューをご用意しております。<br>
                すべて丁寧なカウンセリング付きです。
            </p>
        </div>

        {{-- カテゴリごとにループ --}}
        @forelse ($categories as $category)
            @php
                // 有効なサービスのみ取得
                $activeServices = $category->services->where('is_active', true);
            @endphp

            @if ($activeServices->isNotEmpty())
                <section class="menu-section">
                    <h2 class="section-title">{{ $category->name }}</h2>

                    {{-- カテゴリ説明 --}}
                    @if($category->description)
                        <p class="category-description">{{ $category->description }}</p>
                    @endif

                    {{-- サービス一覧 --}}
                    <div class="menu-grid">
                        @foreach ($activeServices as $service)
                            <div class="menu-card @if($service->is_popular) menu-card-popular @endif">

                                {{-- 人気No.1バッジ --}}
                                @if($service->is_popular)
                                    <span class="popular-badge">人気No.1</span>
                                @endif

                                {{-- サービス画像 --}}
                                @if($service->image)
                                    <div class="card-image">
                                        <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}">

                                        {{-- 特徴バッジ --}}
                                        @if(!empty($service->features))
                                            <div class="feature-badges">
                                                @foreach($service->features as $feature)
                                                    <span class="feature-badge">{{ $feature }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- タイトル・説明 --}}
                                <div class="card-header">
                                    <h3 class="card-title">{{ $service->name }}</h3>

                                    @if($service->description)
                                        <p class="card-description">{!! nl2br(e($service->description)) !!}</p>
                                    @endif
                                </div>

                                {{-- 価格と予約ボタン --}}
                                <div class="card-content">

                                    <div class="card-price-info">
                                        <span class="card-price">¥{{ number_format($service->price) }}</span>
                                        <div class="card-duration">
                                            <span class="duration-text">{{ $service->duration_minutes }}分</span>
                                        </div>
                                    </div>

                                    <a href="{{ route('reservation.form', ['service_id' => $service->id]) }}"
                                       class="button-primary btn-reserve">
                                        予約する
                                    </a>

                                </div>

                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        @empty
            <p class="no-service">現在、登録されているメニューはありません。</p>
        @endforelse

        {{-- 注意事項 --}}
        <section class="notes-section">
            <h3 class="notes-title">ご注意事項</h3>

            <div class="notes-grid">
                <div class="note-item">
                    <p>・駐車場をご用意しております。店舗前「2番」をご利用ください。</p>
                    <p>・前のお客様の施術状況により、お待ちいただくことがございます。<br>
                        （駐車場は交代でのご利用にご協力ください。）</p>
                </div>

                <div class="note-item">
                    <p>・ご来店はご予約時間ちょうどを目安にお越しください。</p>
                    <p>・5分以上前のご来店はご遠慮いただいております。</p>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection
