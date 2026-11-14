@php
    $contentPrefix = $contentPrefix ?? 'content';
    $authorName = trim((string) $history->author);
    $authorMark = mb_substr($authorName, 0, 4);
    $authorMarkLength = mb_strlen($authorMark);
    $historyKind = $history->kind ?? 'comment';
    $viewer = trim((string) ($viewerName ?? ''));
    $isChatComment = trim((string) ($history->content ?? '')) !== ''
        && ! in_array($historyKind, ['ball', 'done'], true);
    $isMine = $isChatComment
        && $viewer !== ''
        && $viewer !== '不明'
        && $authorName === $viewer;
    $displayName = $authorName !== '' ? $authorName : '不明';
@endphp
<div
    class="history-item{{ $isMine ? ' history-item--mine' : '' }}"
    data-history-id="{{ $history->id }}"
    data-log-at="{{ $history->created_at->toIso8601String() }}"
    data-log-tie="{{ $history->id }}"
    data-log-kind="history"
    data-log-name="{{ $displayName }}"
    data-log-date="{{ $history->created_at->format('Y/n/j') }}"
    data-log-time="{{ $history->created_at->format('H:i') }}"
>
    <div
        class="author-avatar author-avatar--n{{ $authorMarkLength }}"
        data-author-name="{{ $authorName }}"
        title="{{ $authorName }}"
    >
        @if ($authorMarkLength <= 2){{ $authorMark }}@else<span>{{ mb_substr($authorMark, 0, 2) }}</span><span>{{ mb_substr($authorMark, 2) }}</span>@endif
    </div>

    <div class="cw-post">
        <div class="cw-meta">
            <span class="author-name">{{ $displayName }}</span>
            <div class="date-actions">
                <span class="date-ymd">{{ $history->created_at->format('Y/n/j') }}</span>
                <span class="date">{{ $history->created_at->format('H:i') }}</span>
                <div class="inline-actions">
                    <i class="fas fa-trash-alt mini-btn delete-btn"
                       data-action="delete-history"
                       data-history-id="{{ $history->id }}"
                       title="削除"
                       aria-label="進捗を削除"
                       role="button"
                       tabindex="0"></i>
                    <i class="fas fa-edit mini-btn edit-btn"
                       data-action="edit-history"
                       data-history-id="{{ $history->id }}"
                       title="編集"
                       aria-label="進捗を編集"
                       role="button"
                       tabindex="0"></i>
                </div>
            </div>
        </div>

        @if ($historyKind === 'ball')
            <div class="bubble ball-bubble">
                <div class="content" id="{{ $contentPrefix }}-{{ $history->id }}">
                    <span class="bubble-kind">ボール</span>
                    {!! nl2br(e($history->content)) !!}
                </div>
            </div>
        @elseif ($historyKind === 'done')
            <div class="bubble done-bubble">
                <div class="content" id="{{ $contentPrefix }}-{{ $history->id }}">
                    <span class="bubble-kind">できた</span>
                    {!! nl2br(e($history->content)) !!}
                </div>
            </div>
        @else
            @if (!empty($history->status))
                <div class="bubble status-bubble">
                    <div class="content">
                        ステータスを「{{ $history->status }}」に変更
                    </div>
                </div>
            @endif

            @if (!empty($history->content))
                <div class="bubble">
                    <div class="content" id="{{ $contentPrefix }}-{{ $history->id }}">
                        {!! nl2br(e($history->content)) !!}
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
