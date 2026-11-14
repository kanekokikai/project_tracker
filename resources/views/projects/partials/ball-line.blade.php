@php
    $viewerName = $viewerName ?? '';
    $withActions = $withActions ?? false;
    $balls = isset($balls) ? $balls : collect([$ball]);
    $primary = \App\Support\BallDisplay::displayBall($balls, $viewerName);
    $actionBall = $withActions ? \App\Support\BallDisplay::manageable($balls, $viewerName) : null;
    $who = \App\Support\BallDisplay::whoGroup($balls, $viewerName);
    $dueLabel = \App\Support\BallDisplay::dueLabel($primary);
    $tone = \App\Support\BallDisplay::tone($primary, $viewerName);
    $holds = $balls->contains(fn ($item) => $item->assignee === $viewerName);
    if ($holds && ! str_contains($tone, 'ball-mine')) {
        $tone = trim($tone.' ball-mine');
    }
    $type = \App\Support\BallDisplay::typeOf($balls);
    $assignees = $balls->pluck('assignee')->values();
@endphp
<li class="ball-line {{ $tone }}"
    data-assignee="{{ $primary->assignee }}"
    data-assignees='@json($assignees)'
    data-ball-type="{{ $type }}">
    <span class="ball-mark" aria-hidden="true">●</span>
    <span class="ball-who">{{ $who }}</span>
    <span class="ball-type">{{ $type }}</span>
    <span class="ball-due">{{ $dueLabel }}</span>
    <span class="ball-task">{{ $primary->task }}</span>
    @if ($actionBall)
        <span class="ball-actions">
            <button type="button" class="btn btn-sm" data-action="toggle-done" data-ball-id="{{ $actionBall->id }}">できました</button>
            <button type="button" class="btn btn-sm" data-action="toggle-extend" data-ball-id="{{ $actionBall->id }}">期限を延ばす</button>
        </span>
    @endif
</li>
@if ($actionBall)
    <li class="ball-panel" id="done-panel-{{ $actionBall->id }}" hidden>
        <form class="ball-done-form" data-ball-id="{{ $actionBall->id }}">
            <div class="done-templates">
                @foreach (\App\Support\BallDisplay::DONE_TEMPLATES as $template)
                    <button type="button" class="btn btn-sm" data-action="use-template">{{ $template }}</button>
                @endforeach
            </div>
            <textarea name="content" class="form-control" rows="2" placeholder="できたことを書く" required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">できました</button>
        </form>
    </li>
    <li class="ball-panel" id="extend-panel-{{ $actionBall->id }}" hidden>
        <form class="ball-extend-form" data-ball-id="{{ $actionBall->id }}">
            <input type="date" name="due_date" class="form-control" required value="{{ $primary->due_date?->toDateString() }}">
            <button type="submit" class="btn btn-primary btn-sm">この日に延ばす</button>
        </form>
    </li>
@endif
