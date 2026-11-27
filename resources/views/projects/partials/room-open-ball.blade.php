@php
    $viewerName = $viewerName ?? '';
    $balls = isset($balls) ? $balls : collect([$ball]);
    $primary = \App\Support\BallDisplay::displayBall($balls, $viewerName);
    $actionBall = \App\Support\BallDisplay::manageable($balls, $viewerName);
    $who = \App\Support\BallDisplay::whoGroup($balls, $viewerName);
    $dueLabel = \App\Support\BallDisplay::shortDue($primary);
    $tone = \App\Support\BallDisplay::tone($primary, $viewerName);
    $holds = $balls->contains(fn ($item) => $item->assignee === $viewerName);
    $dueDate = $primary->due_date?->toDateString() ?? '';
    $arrived = $dueDate !== '' && $dueDate <= \App\Support\BallDisplay::today();
    $isAuto = $actionBall && (int) ($autoBallId ?? 0) === (int) $actionBall->id;
@endphp
<li class="room-open-ball {{ $tone }}{{ $isAuto ? ' is-aimed' : '' }}"
    @if ($actionBall) data-ball-id="{{ $actionBall->id }}" @endif
    data-task="{{ $primary->task }}"
    data-held="{{ $holds ? '1' : '0' }}"
    data-due-arrived="{{ $arrived ? '1' : '0' }}"
    @if ($isAuto) data-auto-return="1" @endif>
    <span class="ball-mark" aria-hidden="true">●</span>
    <span class="ball-who">{{ $who }}</span>
    <span class="ball-due">{{ $dueLabel }}</span>
    <span class="ball-task" title="{{ $primary->task }}">{{ $primary->task }}</span>
    @if ($actionBall)
        <span class="room-open-actions">
            <button type="button" class="btn btn-sm" data-action="aim-return">返す</button>
            <button type="button" class="btn btn-sm" data-action="toggle-extend" data-ball-id="{{ $actionBall->id }}">期限を延ばす</button>
        </span>
    @endif
</li>
@if ($actionBall)
    <li class="ball-panel" id="extend-panel-{{ $actionBall->id }}" hidden>
        <form class="ball-extend-form" data-ball-id="{{ $actionBall->id }}">
            <input type="date" name="due_date" class="form-control" required value="{{ $primary->due_date?->toDateString() }}">
            <button type="submit" class="btn btn-primary btn-sm">この日に延ばす</button>
        </form>
    </li>
@endif
