{{-- /resources/views/admin/login.blade.php --}}
@extends('layout.app')

@section('title', '管理者ログイン')
@section('no_header', true)
@section('no_footer', true)

@section('styles')
    @vite([
        'resources/css/layout/app-shell.css',
        'resources/css/pages/admin/login/login.css',
    ])
@endsection

@section('content')
<div class="admin-login">
    <div class="admin-login__bg" aria-hidden="true"></div>

    <div class="admin-login__card">
        <div class="admin-login__head">
            <div class="admin-login__badge">REFINE</div>
            <h1 class="admin-login__title">管理者ログイン</h1>
            <p class="admin-login__subtitle">管理画面へアクセスするにはログインしてください</p>
        </div>

        {{-- フラッシュ/エラー表示（既存挙動は壊さず、表示だけ追加） --}}
        @if (session('status'))
            <div class="admin-login__alert admin-login__alert--info" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="admin-login__alert admin-login__alert--error" role="alert">
                <p class="admin-login__alert-title">入力内容をご確認ください</p>
                <ul class="admin-login__alert-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="admin-login__form" novalidate>
            @csrf

            {{-- メールアドレス --}}
            <div class="admin-login__field">
                <label for="email" class="admin-login__label">メールアドレス</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    class="admin-login__input @error('email') is-invalid @enderror"
                    placeholder="admin@example.com"
                >
                @error('email')
                    <p class="admin-login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div class="admin-login__field">
                <label for="password" class="admin-login__label">パスワード</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="admin-login__input @error('password') is-invalid @enderror"
                    placeholder="••••••••"
                >
                @error('password')
                    <p class="admin-login__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- ログインボタン --}}
            <button type="submit" class="admin-login__button">
                ログイン
            </button>
        </form>
    </div>
</div>
@endsection
