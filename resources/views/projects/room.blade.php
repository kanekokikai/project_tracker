@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="project-list room-page">
    <div class="room-jumps" id="roomJumps">
        <button type="button" class="room-jump" id="roomLogJump" aria-controls="roomLog" aria-label="議事ログを画面上部へ" title="議事ログを画面上部へ">
            <span>議事</span>
            <span>ログ</span>
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
                @if ($project->due_date)
                    @php $meetingDue = $project->due_date->toDateString(); @endphp
                    <span @class(['room-due', 'is-overdue' => \App\Support\TopicDue::isOverdue($meetingDue)])>{{ \App\Support\TopicDue::label($meetingDue) }}</span>
                @endif
                @if ($project->status === '進行中')
                    <button type="button" class="room-due-extend" data-action="toggle-topic-due">{{ $project->due_date ? '期限を延ばす' : '期限を付ける' }}</button>
                @endif
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

        @if ($project->status === '進行中')
            <form id="topicDueForm" class="room-due-form" hidden>
                <label class="room-sr" for="topicDueDate">会議の日</label>
                <input type="date" id="topicDueDate" name="due_date" class="form-control" required value="{{ $project->due_date?->toDateString() }}">
                <button type="submit" class="btn btn-primary btn-sm">{{ $project->due_date ? 'この日に延ばす' : 'この日にする' }}</button>
            </form>
        @endif

        @if ($siblings->isNotEmpty())
            <div class="room-siblings">
                <button type="button"
                        class="room-sibling-toggle"
                        data-action="toggle-siblings"
                        aria-expanded="false"
                        aria-controls="roomSiblings">
                    <span class="room-sibling-title">同じ対象の議題</span>
                    <span class="room-sibling-count">{{ $siblings->count() }}</span>
                </button>
                <ul class="sibling-list" id="roomSiblings" hidden>
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
            </div>
        @endif

        @if ($unansweredQuestions > 0)
            <p class="room-questions has-questions">未回答の質問 {{ $unansweredQuestions }}件</p>
        @endif

        @if ($scoreNames->isNotEmpty())
            <section class="room-block room-score-block" aria-label="得点ボード">
                <h2 class="room-label">得点</h2>
                <div class="room-score-scroll">
                    <table class="room-score">
                        <thead>
                            <tr>
                                @foreach ($scoreNames as $scoreName)
                                    <th scope="col">{{ $scoreName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach ($scoreNames as $scoreName)
                                    <td aria-label="{{ $scoreName }}の得点"></td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="room-block room-block-log" id="roomLog">
            <h2 class="room-label">議事ログ</h2>
            @php
                $ballGroups = \App\Support\BallDisplay::groups($openBalls, $viewerName);
                $visibleBallGroups = $ballGroups->take(3);
                $extraBallGroups = $ballGroups->slice(3)->values();
                $autoGroup = $ballGroups->first(function ($balls) use ($viewerName) {
                    $holds = $balls->contains(fn ($item) => $item->assignee === $viewerName);
                    $primary = \App\Support\BallDisplay::displayBall($balls, $viewerName);
                    $due = $primary->due_date?->toDateString();

                    return $holds && $due !== null && $due <= \App\Support\BallDisplay::today();
                });
                $autoBallId = $autoGroup
                    ? \App\Support\BallDisplay::manageable($autoGroup, $viewerName)?->id
                    : null;
            @endphp
            <div class="line-chat">
                @if ($ballGroups->isNotEmpty())
                    <div class="room-open" id="roomOpenBalls">
                        <ul class="room-open-list">
                            @foreach ($visibleBallGroups as $balls)
                                @include('projects.partials.room-open-ball', [
                                    'balls' => $balls,
                                    'viewerName' => $viewerName,
                                    'autoBallId' => $autoBallId,
                                ])
                            @endforeach
                        </ul>
                        @if ($extraBallGroups->isNotEmpty())
                            <button type="button" class="room-open-more" data-action="toggle-more-balls" aria-expanded="false" aria-controls="roomOpenMore">ほか{{ $extraBallGroups->count() }}件</button>
                            <ul class="room-open-list" id="roomOpenMore" hidden>
                                @foreach ($extraBallGroups as $balls)
                                    @include('projects.partials.room-open-ball', [
                                        'balls' => $balls,
                                        'viewerName' => $viewerName,
                                        'autoBallId' => $autoBallId,
                                    ])
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
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
                        'logBalls' => $logBalls,
                        'contentPrefix' => 'content',
                        'viewerName' => $viewerName,
                    ])
                @else
                    <p class="room-empty">まだ議事ログはありません。</p>
                @endif

                <form id="roomCommentForm" class="room-compose" data-mode="message" data-external="{{ $externalAssignee }}">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <button type="button"
                            @class(['attachment-icon', 'room-compose-attach', 'has-attachments' => $project->attachments_count > 0])
                            data-action="open-attachments"
                            data-project-id="{{ $project->id }}"
                            title="添付ファイル"
                            aria-label="添付ファイル">
                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="room-compose-attach room-compose-ball" id="roomBallButton" aria-label="ボールを投げる" aria-pressed="false" title="ボールを投げる">
                        <i class="fas fa-baseball-ball" aria-hidden="true"></i>
                    </button>
                    <div class="room-compose-field">
                        <div class="room-aim" id="roomAim" hidden>
                            <span id="roomAimText"></span>
                            <button type="button" id="roomAimClear" aria-label="普通のメッセージに戻す">×</button>
                        </div>
                        <label class="room-sr" for="roomComment">コメント</label>
                        <textarea id="roomComment" name="content" class="form-control" rows="1" required maxlength="2000" placeholder="メッセージ"></textarea>
                    </div>
                    <button type="submit" class="room-send" id="roomSend" aria-label="送信">
                        <svg class="room-send-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="room-send-label" hidden>送信</span>
                    </button>
                </form>
            </div>
        </section>

    </div>
</div>

<dialog id="throwBallDialog" class="room-throw-dialog" aria-labelledby="throwBallTitle">
    <form id="throwBallPicker">
        <h2 id="throwBallTitle">誰に投げる</h2>
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
        <div class="room-throw-when">
            <div class="room-throw-date">
                <span class="field-label" id="throwDueLabel">いつまで</span>
                <button type="button" class="room-throw-date-face" id="throwDueFace" aria-labelledby="throwDueLabel" aria-haspopup="dialog" aria-expanded="false">
                    <span class="room-throw-date-text is-empty" id="throwDueText">日付を選ぶ</span>
                    <svg class="room-throw-date-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3.5" y="5" width="17" height="15.5" rx="3" fill="none" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M3.5 9.5h17M8 3.5v3M16 3.5v3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </button>
                <input type="date" id="throwDueDate" tabindex="-1" aria-hidden="true">
            </div>
            <label class="no-due-option">
                <input type="checkbox" id="throwNoDue" value="1">
                期限なし
            </label>
        </div>
        <div class="room-cal" id="throwCalendar" hidden>
            <div class="room-cal-head">
                <button type="button" data-cal="prev" aria-label="前の月">‹</button>
                <span id="throwCalTitle"></span>
                <button type="button" data-cal="next" aria-label="次の月">›</button>
            </div>
            <div class="room-cal-week" aria-hidden="true">
                <span>日</span><span>月</span><span>火</span><span>水</span><span>木</span><span>金</span><span>土</span>
            </div>
            <div class="room-cal-days" id="throwCalDays"></div>
        </div>
        <div class="room-throw-actions">
            <button type="button" class="btn" id="throwBallCancel">キャンセル</button>
            <button type="submit" class="room-submit">この内容で書く</button>
        </div>
    </form>
</dialog>

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
