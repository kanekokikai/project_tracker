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
@php
    $showSidebar = request()->routeIs('projects.show');
@endphp
<body class="{{ $isAuthenticated ? 'is-authenticated' : 'is-guest' }} {{ $showSidebar ? '' : 'no-sidebar' }}">
    @if ($showSidebar)
        <aside class="sidebar" aria-label="プロジェクト一覧">
            <div class="sidebar-header">
                <div class="sidebar-header-title">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <h2>議題</h2>
                </div>
                <button type="button" class="sidebar-pin" title="サイドバーを固定" aria-label="サイドバーを固定">
                    <i class="fas fa-thumbtack" aria-hidden="true"></i>
                </button>
            </div>
            <div class="sidebar-content">
                <ul class="sidebar-source" hidden>
                    @foreach ($sidebarTopics ?? [] as $topic)
                        <li data-project-id="{{ $topic->id }}" data-status="{{ $topic->status }}" data-href="{{ route('projects.show', $topic) }}">{{ $topic->name }}</li>
                    @endforeach
                </ul>
                <ul class="project-nav"></ul>
            </div>
        </aside>
        <div class="sidebar-overlay"></div>
    @endif

    @unless (!empty($hideSidebar))
        <aside class="activity-log-panel" id="activityLogPanel" aria-label="ログ" aria-hidden="true">
            <div class="activity-log-header">
                <div class="activity-log-header-title">
                    <i class="fas fa-stream" aria-hidden="true"></i>
                    <h2>ログ</h2>
                </div>
                <button type="button" class="activity-log-close" id="activityLogClose" aria-label="ログを閉じる" title="閉じる">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="activity-log-content" id="activityLogContent">
                <p class="activity-log-empty">読み込み中...</p>
            </div>
        </aside>
        <div class="activity-log-overlay" id="activityLogOverlay"></div>
    @endunless

    <header class="header">
        @if ($showSidebar)
            <button type="button" class="sidebar-toggle" aria-label="議題一覧を開く" title="議題一覧" aria-expanded="false">
                <span class="sidebar-toggle-icon" aria-hidden="true">
                    <i class="fas fa-bars"></i>
                </span>
            </button>
        @endif

        <div class="header-content">
            <a href="{{ route('projects.index') }}" class="header-logo" aria-label="ホーム">
                <img src="{{ asset('images/logo.png') }}" alt="" class="app-logo">
            </a>

            <nav class="header-nav" aria-label="メインメニュー">
                <a href="{{ route('projects.index') }}" class="header-nav-link {{ request()->routeIs('projects.index', 'projects.show') ? 'is-current' : '' }}" @if(request()->routeIs('projects.index', 'projects.show')) aria-current="page" @endif>
                    <i class="fas fa-home" aria-hidden="true"></i>
                    <span>ホーム</span>
                </a>
                <a href="{{ route('subjects.index') }}" class="header-nav-link {{ request()->routeIs('subjects.*') ? 'is-current' : '' }}" @if(request()->routeIs('subjects.*')) aria-current="page" @endif>
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    <span>対象</span>
                </a>
                <a href="{{ route('chatwork.index') }}" class="header-nav-link {{ request()->routeIs('chatwork.*') ? 'is-current' : '' }}" @if(request()->routeIs('chatwork.*')) aria-current="page" @endif>
                    <i class="fas fa-user-friends" aria-hidden="true"></i>
                    <span>社員管理</span>
                </a>
            </nav>

            <div class="header-account">
                @if (!empty($currentMemberName))
                    <span class="header-member">
                        <span class="header-member-mark" aria-hidden="true">{{ mb_substr($currentMemberName, 0, 1) }}</span>
                        <span class="header-member-name">{{ $currentMemberName }}</span>
                    </span>
                @endif
                @unless (!empty($hideSidebar))
                    <button type="button" class="activity-log-toggle" id="activityLogToggle" aria-label="ログを開く" title="ログ" aria-expanded="false" aria-controls="activityLogPanel">
                        <i class="fas fa-stream" aria-hidden="true"></i>
                        <span class="activity-log-toggle-label">ログ</span>
                    </button>
                @endunless
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="logout-link">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        <span>ログアウト</span>
                    </button>
                </form>
            </div>
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
    @if ($showSidebar)
        <script src="{{ asset('js/sidebar.js') }}?v={{ filemtime(public_path('js/sidebar.js')) }}"></script>
    @endif
    @unless (!empty($hideSidebar))
        <script src="{{ asset('js/activity-log.js') }}?v={{ filemtime(public_path('js/activity-log.js')) }}"></script>
    @endunless
    <script src="{{ asset('js/back-to-top.js') }}?v={{ filemtime(public_path('js/back-to-top.js')) }}"></script>
    <script>
        window.currentMemberName = @json($currentMemberName ?? '');
        if (window.currentMemberName) {
            try {
                localStorage.setItem('projectTrackerLastAuthor', window.currentMemberName);
            } catch (error) {}
            if (typeof window.setLastAuthor === 'function') {
                window.setLastAuthor(window.currentMemberName);
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
