<div id="status-dropdown" class="status-dropdown" style="display: none;">
    @foreach (\App\Models\Project::STATUSES as $status)
        <div class="status-option" data-status="{{ $status }}">{{ $status }}</div>
    @endforeach
</div>
