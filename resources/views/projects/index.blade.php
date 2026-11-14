@extends('layouts.app')

@section('title', 'プロジェクト管理')

@section('content')
<div class="project-list home-page">
    @if (session('status'))
        <p class="status-message success">{{ session('status') }}</p>
    @endif

    <div class="home-sheet">
        <div class="home-bar">
            <div @class(['home-stat', 'has-balls' => $myBallCount > 0])>
                <strong>{{ $myBallCount }}</strong>
                <span>あなたのボール</span>
            </div>

            <div class="home-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="text" id="memberSearch" placeholder="議題・メンバー・コメント" autocomplete="off" aria-label="議題、メンバー、コメントを検索">
                <i class="fas fa-times-circle home-search-clear" id="clearSearch" style="display: none;" role="button" aria-label="検索を消す"></i>
            </div>

            <select id="departmentFilter" class="home-select" aria-label="部署">
                <option value="all" @selected($departmentFilter === 'all')>すべての部署 ({{ $departmentCounts['all'] ?? 0 }})</option>
                <option value="選択なし" @selected($departmentFilter === '選択なし')>選択なし ({{ $departmentCounts['選択なし'] ?? 0 }})</option>
                @foreach ($departments as $department)
                    @if ($department !== '選択なし')
                        <option value="{{ $department }}" @selected($departmentFilter === $department)>{{ $department }} ({{ $departmentCounts[$department] ?? 0 }})</option>
                    @endif
                @endforeach
            </select>

            <select id="statusFilter" class="home-select" aria-label="ステータス">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }} ({{ $statusCounts[$status] ?? 0 }})</option>
                @endforeach
                <option value="all" @selected($statusFilter === 'all')>すべてのステータス ({{ $statusCounts['all'] ?? 0 }})</option>
            </select>

            <label class="home-chip" title="ボールがある議題だけ表示します">
                <input type="checkbox" id="hasBallToggle">
                <span>ボールあり</span>
            </label>

            <select id="holderFilter" class="home-select home-select-person" aria-label="ボール保持者">
                <option value="">ボール保持者</option>
                @foreach ($ballHolders as $holder)
                    <option value="{{ $holder['name'] }}">{{ $holder['label'] }}@if ($holder['count'] > 0) ({{ $holder['count'] }})@endif</option>
                @endforeach
            </select>

            <label class="home-chip">
                <input type="checkbox" id="myBallToggle">
                <span>自分だけ</span>
            </label>

            <button class="home-create" type="button" data-action="open-add-project">議題を作る</button>
        </div>

        <div class="home-topic-sheet">
    @forelse ($topics as $topic)
        @php
            $searchMembers = collect($topic->team_members ?? [])
                ->merge($topic->openBalls->pluck('assignee'))
                ->filter()
                ->unique()
                ->implode(' ');
            $hasMyBall = $viewerName !== '' && $viewerName !== '不明'
                && $topic->openBalls->contains(fn ($ball) => $ball->assignee === $viewerName);
            $latestLine = $topic->latestCommentLine();
        @endphp
        <div @class(['project-card', 'topic-row'])
             id="project-{{ $topic->id }}"
             data-status="{{ $topic->status }}"
             data-project-id="{{ $topic->id }}"
             data-search-name="{{ $topic->name }}"
             data-search-subject="{{ $topic->subject->name ?? '' }}"
             data-search-members="{{ $searchMembers }}"
             data-has-my-ball="{{ $hasMyBall ? '1' : '0' }}"
             data-has-ball="{{ $topic->openBalls->isNotEmpty() ? '1' : '0' }}"
             data-ball-holders='@json($topic->openBalls->pluck('assignee')->unique()->values())'
             data-room-url="{{ route('projects.show', $topic) }}">
            <div class="topic-line">
                <div class="topic-lead">
                    <h2 class="topic-title">
                        @if ($topic->subject)
                            <span class="topic-kicker">
                                <span class="topic-quote" aria-hidden="true">「</span><a class="topic-subject-link" href="{{ route('subjects.index') }}#project-{{ $topic->id }}">{{ $topic->subject->name }}</a><span class="topic-quote" aria-hidden="true">」</span>
                                <span class="topic-join">の</span>
                            </span>
                        @endif
                        <span class="topic-name">
                            @if ($topic->subject)
                                <span class="topic-quote" aria-hidden="true">「</span>
                            @endif
                            <a class="topic-name-link"
                               href="{{ route('projects.show', $topic) }}"
                               @if (!empty($topic->team_members)) title="{{ implode('、', $topic->team_members) }}" @endif>{{ $topic->name }}</a>
                            @if ($topic->subject)
                                <span class="topic-quote" aria-hidden="true">」</span>
                            @endif
                        </span>
                        @if ($topic->subject)
                            <span class="topic-join">について</span>
                        @endif
                    </h2>
                    @if (!empty($topic->department) && $topic->department !== '選択なし')
                        <span class="department-badge">{{ $topic->department }}</span>
                    @endif
                    @if ($topic->attachments_count > 0)
                        <span class="attachment-icon has-attachments"
                              data-action="open-attachments"
                              data-project-id="{{ $topic->id }}"
                              role="button"
                              tabindex="0"
                              aria-label="添付ファイル">
                            <i class="fas fa-paperclip"></i>
                        </span>
                    @endif
                </div>
                <div class="topic-meta">
                    <span class="status-badge status-{{ $topic->status }} clickable-status"
                          data-action="show-status-dropdown"
                          data-project-id="{{ $topic->id }}"
                          data-status="{{ $topic->status }}"
                          role="button"
                          tabindex="0"
                          aria-label="ステータスを変更">
                        {{ $topic->status }}
                        <i class="fas fa-caret-down status-caret"></i>
                    </span>
                    <i class="fas fa-edit action-icon"
                       data-action="edit-project"
                       data-project-id="{{ $topic->id }}"
                       data-project-name="{{ $topic->name }}"
                       data-tooltip="編集"
                       title="編集"
                       aria-label="議題を編集"
                       role="button"
                       tabindex="0"></i>
                    <i class="fas fa-trash-alt action-icon delete-icon"
                       data-action="delete-project"
                       data-project-id="{{ $topic->id }}"
                       data-tooltip="削除"
                       title="削除"
                       aria-label="削除"
                       role="button"
                       tabindex="0"></i>
                </div>
            </div>

            @if ($topic->openBalls->isNotEmpty())
                <ul class="ball-list">
                    @foreach (\App\Support\BallDisplay::groups($topic->openBalls, $viewerName) as $balls)
                        @include('projects.partials.ball-line', ['balls' => $balls, 'viewerName' => $viewerName])
                    @endforeach
                </ul>
            @endif
            @if ($latestLine !== '')
                <p class="topic-preview">{{ $latestLine }}</p>
            @endif
            <p class="topic-comment-hit" hidden></p>
        </div>
    @empty
        <div class="home-empty">
            <p>表示できる議題がありません。</p>
        </div>
    @endforelse
        <div class="home-empty" id="topicFilterEmpty" hidden>
            <p>条件に合う議題がありません。</p>
        </div>
        </div>
    </div>
</div>

@push('modals')
    @include('projects.partials.modals')
    @include('projects.partials.attachment-modals')
@endpush

@include('projects.partials.status-dropdown')
@endsection

@push('scripts')
<script src="{{ asset('js/projects.js') }}?v={{ filemtime(public_path('js/projects.js')) }}"></script>
<script src="{{ asset('js/attachments.js') }}?v={{ filemtime(public_path('js/attachments.js')) }}"></script>
@endpush
