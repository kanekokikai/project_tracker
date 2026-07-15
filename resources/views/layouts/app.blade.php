<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(url('/'), '/') }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    @stack('styles')
</head>
<body class="{{ $isAuthenticated ? 'is-authenticated' : 'is-guest' }} {{ !empty($hideSidebar) ? 'no-sidebar' : '' }}">
    @unless (!empty($hideSidebar))
        <aside class="sidebar" aria-label="プロジェクト一覧">
            <div class="sidebar-header">
                <div class="sidebar-header-title">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <h2>プロジェクト</h2>
                </div>
                <button type="button" class="sidebar-pin" title="サイドバーを固定" aria-label="サイドバーを固定">
                    <i class="fas fa-thumbtack" aria-hidden="true"></i>
                </button>
            </div>
            <div class="sidebar-content">
                <ul class="project-nav"></ul>
            </div>
        </aside>
        <div class="sidebar-overlay"></div>
    @endunless

    <header class="header">
        @unless (!empty($hideSidebar))
            <button type="button" class="sidebar-toggle" aria-label="プロジェクト一覧を開く" title="プロジェクト一覧" aria-expanded="false">
                <span class="sidebar-toggle-icon" aria-hidden="true">
                    <i class="fas fa-bars"></i>
                </span>
            </button>
        @endunless

        <div class="header-content">
            <h1>
                <img src="{{ asset('images/logo.png') }}" alt="ロゴ" class="app-logo">
                <span class="app-brand">
                    <span class="app-brand-name">@yield('header_title', 'プロジェクト管理')</span>
                    <span class="app-brand-sub">@yield('header_subtitle', 'Project Tracker')</span>
                </span>
            </h1>
            <nav class="header-actions" aria-label="アカウント">
                @if (!empty($isChatworkAdmin))
                    <a href="{{ route('projects.index') }}" class="header-nav-link">一覧へ戻る</a>
                @else
                    <a href="{{ route('chatwork.index') }}" class="header-nav-link" title="Chatwork TO設定">TO設定</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="logout-link">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    @include('partials.auth-modal')

    <main class="main-content {{ $isAuthenticated ? '' : 'blur-content' }}">
        @yield('content')
    </main>

    @stack('modals')

    <button type="button" class="back-to-top" id="backToTop" aria-label="ページ上部へ戻る" title="上部へ戻る" aria-hidden="true" tabindex="-1">
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>

    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
    @unless (!empty($hideSidebar))
        <script src="{{ asset('js/sidebar.js') }}?v={{ filemtime(public_path('js/sidebar.js')) }}"></script>
    @endunless
    <script src="{{ asset('js/back-to-top.js') }}?v={{ filemtime(public_path('js/back-to-top.js')) }}"></script>
    @stack('scripts')
</body>
</html>
