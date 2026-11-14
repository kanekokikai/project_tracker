@php
    $historyItems = $histories instanceof \Illuminate\Support\Collection ? $histories : collect($histories);
    $imageItems = collect($logAttachments ?? $imageAttachments ?? [])->map(function ($attachment) {
        return (object) [
            'kind' => 'attachment',
            'at' => $attachment->uploaded_at,
            'tie' => $attachment->id,
            'attachment' => $attachment,
        ];
    });
    $logItems = $historyItems->map(function ($history) {
        return (object) [
            'kind' => 'history',
            'at' => $history->created_at,
            'tie' => $history->id,
            'history' => $history,
        ];
    })->concat($imageItems)->sortBy([
        ['at', 'asc'],
        ['tie', 'asc'],
    ])->values();
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
    @foreach ($logItems as $logItem)
        @if ($logItem->kind === 'attachment')
            @include('projects.partials.log-attachment', ['attachment' => $logItem->attachment])
            @continue
        @endif
        @include('projects.partials.history-item', [
            'history' => $logItem->history,
            'contentPrefix' => $contentPrefix ?? 'content',
            'viewerName' => $viewerName ?? ($currentMemberName ?? ''),
        ])
    @endforeach
</div>
