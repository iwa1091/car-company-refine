@extends('layout.app')

@section('title', '施術事例・お客様の声')

{{-- ページ専用CSS --}}
@section('styles')
  @vite(['resources/css/pages/gallery/gallery.css'])
@endsection

@section('content')
<div class="gallery-page-container">

    @php
        /*
         * Before / After と ギャラリーは DB を使わず
         * 静的配列としてここで定義する
         */

        $beforeAfterCases = collect([
            (object) [
                'title'       => 'ナチュラルなメンズ眉スタイリング',
                'description' => '自眉を活かしつつ、余分な毛だけを整えて清潔感をアップ。初めての方にもおすすめの自然なデザインです。',
            ],
            (object) [
                'title'       => '眉ワックス＋ラミネーションで立体感アップ',
                'description' => '毛流れを整えながら、眉ワックスで輪郭をくっきり。毎朝のメイク時間も短縮できる人気メニューです。',
            ],
            (object) [
                'title'       => 'まつげパーマとアイブロウのトータルケア',
                'description' => 'まつげと眉を同時にケアすることで、目元全体の印象がワンランクアップ。ナチュラルなのに華やかな仕上がりに。',
            ],
        ]);

        // ギャラリーは 4 種類のカードを静的配列で管理
        $galleryCards = [
            [
                'title'       => 'ハーブピーリング',
                'description' => '天然ハーブ×マイルドなケミカルビーリングに、エクソソーム＆ヒト幹細胞培養液を贅沢配合。　剝離なしで細胞レベルから肌を活性化。敏感肌にも優しく、ハリと透明感のある素肌へ導きます。',
                'image'       => 'img/herbalpeeling.webp',
            ],
            [
                'title'       => 'エレクトロポレーション',
                'description' => '特殊な電気バルスと温冷ケアで、美容成分を肌の深部まで浸透。　ホームケアでは届きにくい成分を届け、乾燥・くすみ・毛穴・たるみにアプローチ。美容液は肌状態に合わせてセレクトします。',
                'image'       => 'img/electroporation.webp',
            ],
            [
                'title'       => '毛穴洗浄',
                'description' => '毛穴に詰まった皮脂や汚れ、角栓を除去する施術です。全ての施術前に行うことで後の施術の効果を倍増させます。特に黒ずみ・ざらつきの改善に効果的です。',
                'image'       => 'img/pore_cropped.webp',
            ],
            [
                'title'       => '発毛＆育毛',
                'description' => 'オゾン・高周波・名のミスとなど５種の機能で皮脂バランスを整え、血行を促進。薄毛や育毛・発毛を目指す方に最適なケアで、頭皮のコリもほぐします。診断に基づき、状態に合わせた丁寧な施術を行います。',
                'image'       => 'img/hair_treatment_cropped.webp',
            ],
        ];
    @endphp

    {{-- Header --}}
    <div class="header-section">
        <h1 class="page-title">
            施術事例・お客様の声
        </h1>
        <p class="page-description">
            実際の施術事例とお客様からいただいた喜びの声をご紹介します。<br>
            あなたの理想の目元づくりの参考にしてください。
        </p>
    </div>

    {{-- Before / After --}}
    <section class="mb-20">
        <h2 class="page-title text-center mb-8">
            ビフォー・アフター
        </h2>

        <div class="grid-case">
            @foreach($beforeAfterCases as $case)
                <div class="card">
                    <div class="card-grid-images">

                        {{-- Before --}}
                        <div class="image-container">
                            <img src="{{ asset('img/before.png') }}"
                                 alt="{{ $case->title }} - Before"
                                 class="image-card">
                            <div class="badge badge-before">Before</div>
                        </div>

                        {{-- After --}}
                        <div class="image-container">
                            <img src="{{ asset('img/after.png') }}"
                                 alt="{{ $case->title }} - After"
                                 class="image-card">
                            <div class="badge badge-after">After</div>
                        </div>
                    </div>

                    <div class="card-content">
                        <h3 class="card-title">{{ $case->title }}</h3>
                        <p class="card-description">{{ $case->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Gallery --}}
    <section class="mb-20">
        <h2 class="page-title text-center mb-8">
            施術事例ギャラリー
        </h2>

        <div class="grid-gallery">
            @foreach($galleryCards as $card)
                <div class="card image-wrapper">
                    <div class="relative">
                        <img src="{{ asset($card['image']) }}"
                             alt="{{ $card['title'] }}"
                             class="image-card">
                    </div>

                    <div class="card-content">
                        <h3 class="card-title">{{ $card['title'] }}</h3>
                        <p class="card-description">
                            {{ $card['description'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Customer Reviews --}}
    <section class="mb-16">
        <h2 class="page-title text-center mb-8">
            お客様の声
        </h2>

        {{-- Googleマップ口コミ要約の注記 --}}
        <p class="text-center review-note mb-8">
            ※Googleマップなどでいただいたお声を要約して掲載しています。
        </p>

        <div class="grid-reviews">
            @foreach($reviews as $review)
                <div class="card card-review">
                    <div class="review-header">
                        <div>
                            <div class="review-name-age">
                                {{ $review->name }}
                                @if (!empty($review->age))
                                    <span class="review-age">({{ $review->age }})</span>
                                @endif
                            </div>

                            <div class="review-rating">
                                @for ($i = 0; $i < $review->rating; $i++)
                                    <img src="{{ asset('img/star.png') }}"
                                         class="star"
                                         alt="★">
                                @endfor
                            </div>
                        </div>

                        <img src="{{ asset('img/quote.png') }}"
                             alt="Quote"
                             class="quote">
                    </div>

                    <p class="review-comment">{{ $review->comment }}</p>

                    <div class="review-details">
                        <span>{{ $review->service }}</span>
                        <span>{{ $review->date }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    <section class="section-stats">
        <div class="stats-container">
            <h2 class="section-title text-center mb-12">
                Google口コミ お客様満足度
            </h2>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">9割越え</div>
                    <div class="stat-label">リピート率</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">1,200+</div>
                    <div class="stat-label">施術実績</div>
                </div>

                <div class="stat-item">
                    <div class="stat-value">★5</div>
                    <div class="stat-label">平均評価</div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
