@php
    $historyItems = $histories instanceof \Illuminate\Support\Collection ? $histories : collect($histories);
    $historyCount = $historyItems->count();
@endphp
<div
    @if (!empty($historyId))
        id="history-content-{{ $historyId }}"
    @endif
    class="project-history {{ !empty($collapsed) && $collapsed ? 'collapsed' : '' }}"
    @if (!empty($collapsed) && $collapsed)
        style="display: none;"
    @endif
>
    @foreach ($historyItems as $index => $history)
        @php
            $authorName = (string) $history->author;
            $authorLength = mb_strlen($authorName);
            $authorLabel = mb_substr($authorName, 0, 4);
            $avatarCompact = $authorLength >= 3;
        @endphp
        <div class="history-item{{ $index >= 3 ? ' history-item-extra' : '' }}" data-history-id="{{ $history->id }}">
            <div
                class="author-avatar{{ $avatarCompact ? ' author-avatar--compact' : '' }}"
                data-author-name="{{ $authorName }}"
            >
                {{ $authorLabel }}
            </div>

            @if (!empty($history->status))
                <div class="bubble status-bubble">
                    <div class="content">
                        ステータスを「{{ $history->status }}」に変更
                    </div>
                </div>
            @endif

            @if (!empty($history->content))
                <div class="bubble">
                    <div
                        class="content {{ mb_strlen($history->content) > 100 ? 'expandable' : '' }}"
                        id="{{ $contentPrefix }}-{{ $history->id }}"
                    >
                        {!! nl2br(e($history->content)) !!}
                    </div>

                    @if (mb_strlen($history->content) > 100)
                        <div class="content-toggle" data-target="{{ $contentPrefix }}-{{ $history->id }}">
                            続きを読む
                        </div>
                    @endif
                </div>
            @endif

            <div class="date-actions">
                <span class="date">{{ $history->created_at->format('Y/m/d H:i') }}</span>
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
    @endforeach

    @if ($historyCount > 3)
        <button
            type="button"
            class="history-expand-hint"
            data-action="toggle-history-expand"
            aria-expanded="false"
            aria-label="コメントをさらに表示"
        >
            <span class="history-expand-chevron" aria-hidden="true"></span>
            <span class="history-expand-count">他{{ $historyCount - 3 }}件</span>
        </button>
    @endif
</div>
