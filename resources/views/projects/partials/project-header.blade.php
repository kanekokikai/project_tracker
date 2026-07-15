<div class="project-header">
    <div class="title-section">
        @if ($isChild)
            <h3 class="project-title">
                @include('projects.partials.project-title-content', ['project' => $project, 'isChild' => true])
            </h3>
        @else
            <h2 class="project-title">
                @include('projects.partials.project-title-content', ['project' => $project, 'isChild' => false])
            </h2>
        @endif
    </div>

    <div class="project-actions">
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
        <i class="fas fa-trash-alt action-icon delete-icon"
           data-action="delete-project"
           data-project-id="{{ $project->id }}"
           data-tooltip="削除"
           aria-label="削除"
           role="button"
           tabindex="0"></i>
        <i class="fas fa-edit action-icon"
           data-action="edit-project"
           data-project-id="{{ $project->id }}"
           data-project-name="{{ $project->name }}"
           data-tooltip="プロジェクト名編集"
           aria-label="プロジェクト編集"
           role="button"
           tabindex="0"></i>
    </div>
</div>
