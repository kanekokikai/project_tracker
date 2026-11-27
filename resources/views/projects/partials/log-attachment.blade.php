@php
    $fileUrl = route('attachments.download', $attachment).($attachment->opensInBrowser() ? '?view=1' : '');
    $uploader = trim((string) ($attachment->uploader_name ?? ''));
    $known = $uploader !== '' && $uploader !== '不明';
    $viewer = trim((string) ($viewerName ?? ''));
    $isMine = $known && $viewer !== '' && $viewer !== '不明' && $uploader === $viewer;
    $displayName = $known ? $uploader : '添付';
    $authorMark = mb_substr($displayName, 0, 4);
    $authorMarkLength = mb_strlen($authorMark);
@endphp
<div
    class="history-item history-item-image{{ $isMine ? ' history-item--mine' : '' }}{{ $known && ! $isMine ? ' history-item--other' : '' }}"
    data-attachment-id="{{ $attachment->id }}"
    data-log-at="{{ $attachment->uploaded_at->toIso8601String() }}"
    data-log-tie="{{ $attachment->id }}"
    data-log-kind="attachment"
    data-log-name="{{ $attachment->original_file_name }}"
    data-log-date="{{ $attachment->uploaded_at->format('Y/n/j') }}"
    data-log-time="{{ $attachment->uploaded_at->format('H:i') }}"
>
    <div
        class="author-avatar author-avatar--n{{ $authorMarkLength }}"
        title="{{ $displayName }}"
    >
        @if ($authorMarkLength <= 2){{ $authorMark }}@else<span>{{ mb_substr($authorMark, 0, 2) }}</span><span>{{ mb_substr($authorMark, 2) }}</span>@endif
    </div>
    <div class="cw-post">
        <div class="cw-meta">
            <span class="author-name">{{ $displayName }}</span>
            <div class="date-actions">
                <span class="date-ymd">{{ $attachment->uploaded_at->format('Y/n/j') }}</span>
                <span class="date">{{ $attachment->uploaded_at->format('H:i') }}</span>
                <div class="inline-actions">
                    <i class="fas fa-trash-alt mini-btn delete-btn"
                       data-action="delete-attachment"
                       data-attachment-id="{{ $attachment->id }}"
                       data-file-name="{{ $attachment->original_file_name }}"
                       title="削除"
                       aria-label="添付を削除"
                       role="button"
                       tabindex="0"></i>
                </div>
            </div>
        </div>
        @if ($attachment->isPreviewImage())
            <a class="log-image-link" href="{{ $fileUrl }}" target="_blank" rel="noopener" title="{{ $attachment->original_file_name }}">
                <img class="log-image-preview" src="{{ $fileUrl }}" alt="{{ $attachment->original_file_name }}" loading="lazy">
            </a>
            <span class="log-image-name">{{ $attachment->original_file_name }}</span>
        @else
            <a class="log-file-link" href="{{ $fileUrl }}" target="_blank" rel="noopener">
                <i class="fas fa-file" aria-hidden="true"></i>
                <span>{{ $attachment->original_file_name }}</span>
            </a>
        @endif
    </div>
</div>
