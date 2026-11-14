@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="project-list room-page">
    <div class="room-jumps" id="roomJumps">
        <button type="button" class="room-jump" id="roomLogJump" aria-controls="roomLog" aria-label="議事ログを画面中央へ" title="議事ログを画面中央へ">
            <span>議事</span>
            <span>ログ</span>
        </button>
        <button type="button" class="room-jump" id="roomBallJump" aria-controls="throwBallForm" aria-label="ボールを投げるを画面中央へ" title="ボールを投げるを画面中央へ">
            <span>ボール</span>
        </button>
    </div>
    @if (session('status'))
        <p class="status-message success">{{ session('status') }}</p>
    @endif

    <p class="room-back">
        <a href="{{ route('projects.index') }}">ホーム</a>
        @if ($project->subject)
            <span aria-hidden="true">/</span>
            <a href="{{ route('subjects.index') }}#subject-{{ $project->subject_id }}">{{ $project->subject->name }}</a>
        @endif
    </p>

    <div class="project-card room-card room-sheet" data-project-id="{{ $project->id }}" data-status="{{ $project->status }}">
        <header class="room-head">
            <div class="room-lead">
                <div class="room-title-row">
                    <span @class(['attachment-icon', 'room-attach', 'has-attachments' => $project->attachments_count > 0])
                          data-action="open-attachments"
                          data-project-id="{{ $project->id }}"
                          role="button"
                          tabindex="0"
                          title="添付ファイル"
                          aria-label="添付ファイル">
                        <i class="fas fa-paperclip"></i>
                        <span>添付</span>
                    </span>
                    <h1 class="room-name">
                        @if ($project->subject)
                            <span class="room-kicker">
                                <span class="room-quote" aria-hidden="true">「</span><span class="room-subject">{{ $project->subject->name }}</span><span class="room-quote" aria-hidden="true">」</span>
                                <span class="room-of">の</span>
                            </span>
                        @endif
                        <span class="room-topic">
                            @if ($project->subject)
                                <span class="room-quote" aria-hidden="true">「</span>
                            @endif
                            {{ $project->name }}
                            @if ($project->subject)
                                <span class="room-quote" aria-hidden="true">」</span>
                            @endif
                        </span>
                        @if ($project->subject)
                            <span class="room-about">について</span>
                        @endif
                    </h1>
                </div>
                @if ((!empty($project->department) && $project->department !== '選択なし') || !empty($project->team_members))
                    <p class="room-sub">
                        @if (!empty($project->department) && $project->department !== '選択なし')
                            <span>{{ $project->department }}</span>
                        @endif
                        @if (!empty($project->team_members))
                            <span>{{ implode('、', $project->team_members) }}</span>
                        @endif
                    </p>
                @endif
            </div>
            <div class="room-meta">
                <span class="status-badge status-{{ $project->status }} clickable-status"
                      data-action="show-status-dropdown"
                      data-project-id="{{ $project->id }}"
                      data-status="{{ $project->status }}"
                      role="button"
                      tabindex="0"
                      aria-label="ステータスを変更">
                    {{ $project->status }}
                    <i class="fas fa-caret-down status-caret"></i>
                </span>
                <i class="fas fa-edit action-icon"
                   data-action="edit-project"
                   data-project-id="{{ $project->id }}"
                   data-project-name="{{ $project->name }}"
                   title="編集"
                   aria-label="議題を編集"
                   role="button"
                   tabindex="0"></i>
                <i class="fas fa-trash-alt action-icon delete-icon"
                   data-action="delete-project"
                   data-project-id="{{ $project->id }}"
                   title="削除"
                   aria-label="削除"
                   role="button"
                   tabindex="0"></i>
            </div>
        </header>

        @if ($siblings->isNotEmpty())
            <section class="room-block">
                <h2 class="room-label">同じ対象の議題</h2>
                <ul class="sibling-list">
                    @foreach ($siblings as $sibling)
                        <li>
                            <a href="{{ route('projects.show', $sibling) }}">{{ $sibling->name }}</a>
                            @php
                                $siblingGroups = \App\Support\BallDisplay::groups($sibling->openBalls, $viewerName);
                            @endphp
                            @if ($siblingGroups->isEmpty())
                                <span class="ball-empty">ボールなし</span>
                            @else
                                <span class="sibling-balls">
                                    @foreach ($siblingGroups->take(2) as $balls)
                                        {{ \App\Support\BallDisplay::whoGroup($balls, $viewerName) }}
                                        {{ \App\Support\BallDisplay::typeOf($balls) }}
                                        {{ \App\Support\BallDisplay::dueLabel(\App\Support\BallDisplay::displayBall($balls, $viewerName)) }}
                                        {{ $balls->first()->task }}@if (! $loop->last) ／ @endif
                                    @endforeach
                                    @if ($siblingGroups->count() > 2)
                                        ほか{{ $siblingGroups->count() - 2 }}件
                                    @endif
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="room-block">
            <div class="room-label-row">
                <h2 class="room-label">ボード</h2>
                <span @class(['room-questions', 'has-questions' => $unansweredQuestions > 0])>未回答の質問 {{ $unansweredQuestions }}件</span>
            </div>
            @php
                $ballGroups = \App\Support\BallDisplay::groups($openBalls, $viewerName);
            @endphp
            <ul class="ball-list">
                @forelse ($ballGroups as $balls)
                    @include('projects.partials.ball-line', [
                        'balls' => $balls,
                        'viewerName' => $viewerName,
                        'withActions' => true,
                    ])
                @empty
                    <li class="ball-empty">ボールなし</li>
                @endforelse
            </ul>
            @if ($ballGroups->count() >= 5)
                <p class="ball-hint">ボールが{{ $ballGroups->count() }}件あります。別の議題に分けて、同じ対象に付けられます。</p>
            @endif
        </section>

        <section class="room-block room-block-log" id="roomLog">
            <h2 class="room-label">議事ログ</h2>
            <div class="line-chat">
                @php
                    $latestHistory = $project->histories->last();
                    $latestAttachment = $logAttachments->last();
                    $latestIsAttachment = $latestAttachment && (
                        $latestHistory === null
                        || ($latestAttachment->uploaded_at && $latestHistory->created_at && $latestAttachment->uploaded_at->greaterThan($latestHistory->created_at))
                    );
                @endphp
                @if ($project->histories->isNotEmpty() || $logAttachments->isNotEmpty())
                    <p class="room-latest">
                        @if ($latestIsAttachment)
                            <span class="room-latest-label">最後の添付</span>
                            <span class="room-latest-name">{{ $latestAttachment->original_file_name }}</span>
                            <time datetime="{{ $latestAttachment->uploaded_at->toIso8601String() }}">{{ $latestAttachment->uploaded_at->format('Y/n/j') }} {{ $latestAttachment->uploaded_at->format('H:i') }}</time>
                        @else
                            <span class="room-latest-label">最後の発言</span>
                            <span class="room-latest-name">{{ trim((string) $latestHistory->author) !== '' ? $latestHistory->author : '不明' }}</span>
                            <time datetime="{{ $latestHistory->created_at->toIso8601String() }}">{{ $latestHistory->created_at->format('Y/n/j') }} {{ $latestHistory->created_at->format('H:i') }}</time>
                        @endif
                    </p>
                    @include('projects.partials.history-list', [
                        'histories' => $project->histories,
                        'logAttachments' => $logAttachments,
                        'contentPrefix' => 'content',
                        'viewerName' => $viewerName,
                    ])
                @else
                    <p class="room-empty">まだ議事ログはありません。</p>
                @endif

                <form id="roomCommentForm" class="room-compose">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <button type="button"
                            @class(['attachment-icon', 'room-compose-attach', 'has-attachments' => $project->attachments_count > 0])
                            data-action="open-attachments"
                            data-project-id="{{ $project->id }}"
                            title="添付ファイル"
                            aria-label="添付ファイル">
                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                    </button>
                    <label class="room-sr" for="roomComment">コメント</label>
                    <textarea id="roomComment" name="content" class="form-control" rows="1" required placeholder="メッセージ"></textarea>
                    <button type="submit" class="room-send" aria-label="送信">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </section>

        <form id="throwBallForm" class="room-throw">
            <h2 class="room-label">ボールを投げる</h2>
            <div class="form-group">
                <span class="field-label">誰に</span>
                <div class="assignee-list">
                    @foreach ($members as $member)
                        <label class="assignee-option">
                            <input type="checkbox" name="assignees[]" value="{{ $member->member_name }}">
                            {{ $member->member_name }}
                        </label>
                    @endforeach
                    <label class="assignee-option">
                        <input type="checkbox" name="assignees[]" value="{{ $externalAssignee }}">
                        {{ $externalAssignee }}
                    </label>
                </div>
            </div>
            <div class="ball-throw-grid">
                <label>
                    何を
                    <select name="ball_type" class="form-control" required>
                        @foreach ($ballTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ball-task-field">
                    依頼内容
                    <input type="text" name="task" class="form-control" required placeholder="機械を動かして確認してほしい">
                </label>
                <label>
                    いつまで
                    <input type="date" name="due_date" class="form-control">
                </label>
                <label class="no-due-option">
                    <input type="checkbox" name="no_due" value="1" id="noDueToggle">
                    期限なし
                </label>
            </div>
            <div class="room-throw-bar">
                <button type="submit" class="room-submit">投げる</button>
            </div>
        </form>
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
<script src="{{ asset('js/room.js') }}?v={{ filemtime(public_path('js/room.js')) }}"></script>
<script src="{{ asset('js/attachments.js') }}?v={{ filemtime(public_path('js/attachments.js')) }}"></script>
@endpush
