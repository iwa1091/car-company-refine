@extends('layout.app')

@section('title', 'メニュー・料金')

{{-- ヘッダー無しフラグ（layout 側が対応している場合のみ有効） --}}
@section('no_header', true)
@section('no_footer', true)


@section('styles')
    @vite(['resources/css/pages/menu_price/menu_price.css'])
@endsection

@section('content')
<div class="menu-page-container">
    <div class="menu-inner">
        {{-- ページヘッダー（このページ固有の見出し。サイト共通ヘッダーとは別物） --}}
        <div class="menu-header">
            <h1 class="menu-title">メニュー・料金</h1>
            <p class="menu-description">
                お客様のご希望に合わせて、様々なメニューをご用意しております。<br>
            </p>
        </div>

        @forelse ($categories as $category)
            @php
                $activeServices = $category->services->where('is_active', true);
            @endphp

            @if ($activeServices->isNotEmpty())
                <section class="menu-section">
                    <h2 class="section-title">{{ $category->name }}</h2>

                    @if($category->description)
                        <p class="category-description">{{ $category->description }}</p>
                    @endif

                    <div class="menu-grid">
                        @foreach ($activeServices as $service)
                            <div class="menu-card @if($service->is_popular) menu-card-popular @endif">
                                @if($service->is_popular)
                                    <span class="popular-badge">人気No.1</span>
                                @endif

                                @if($service->image)
                                    <div class="card-image">
                                        <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}">

                                        @if(!empty($service->features))
                                            <div class="feature-badges">
                                                @foreach($service->features as $feature)
                                                    <span class="feature-badge">{{ $feature }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="card-header">
                                    <h3 class="card-title">{{ $service->name }}</h3>

                                    @if($service->description)
                                        <p class="card-description">{!! nl2br(e($service->description)) !!}</p>
                                    @endif
                                </div>

                                <div class="card-content">
                                    <div class="card-price-info">
                                        @if(!empty($service->price_text))
                                            <span class="card-price">{{ $service->price_text }}</span>
                                        @else
                                            <span class="card-price">¥{{ number_format($service->price) }}</span>
                                        @endif

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

        <section class="notes-section">
            <h3 class="notes-title">ご注意事項</h3>

            <div class="notes-grid">
                <div class="note-item">
                    <p>・料金は車のサイズにより変動します。こちらの詳細からご確認ください。</p>
                                        <a
                        href="https://www.keepercoating.jp/lineup/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="reservation-menu-help"
                    >
                        メニューの詳細をご確認後メニューを選択してください。
                    </a>
                </div>

        </section>

    </div>
</div>
@endsection
