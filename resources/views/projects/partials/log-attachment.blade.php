@php
    $fileUrl = route('attachments.download', $attachment).($attachment->opensInBrowser() ? '?view=1' : '');
@endphp
<div
    class="history-item history-item-image"
    data-attachment-id="{{ $attachment->id }}"
    data-log-at="{{ $attachment->uploaded_at->toIso8601String() }}"
    data-log-tie="{{ $attachment->id }}"
    data-log-kind="attachment"
    data-log-name="{{ $attachment->original_file_name }}"
    data-log-date="{{ $attachment->uploaded_at->format('Y/n/j') }}"
    data-log-time="{{ $attachment->uploaded_at->format('H:i') }}"
>
    <div class="author-avatar author-avatar--n2" title="添付">添付</div>
    <div class="cw-post">
        <div class="cw-meta">
            <span class="author-name">添付</span>
            <div class="date-actions">
                <span class="date-ymd">{{ $attachment->uploaded_at->format('Y/n/j') }}</span>
                <span class="date">{{ $attachment->uploaded_at->format('H:i') }}</span>
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
