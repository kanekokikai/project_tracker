<span class="attachment-icon"
      data-action="open-attachments"
      data-project-id="{{ $project->id }}"
      @class(['has-attachments' => $project->attachments_count > 0])
      role="button"
      tabindex="0"
      aria-label="添付ファイル">
    <i class="fas fa-paperclip"></i>
</span>

<span class="project-name">
    {{ $project->name }}

    @if (!empty($project->team_members))
        <span class="team-members-tooltip">
            @foreach ($project->team_members as $member)
                <span class="team-member-name">{{ $member }}</span>
            @endforeach
        </span>
    @endif
</span>

@if (!$isChild && !empty($project->department) && $project->department !== '選択なし')
    <span class="department-badge">{{ $project->department }}</span>
@endif

@if (!$isChild)
    <i class="fas fa-plus-circle action-icon add-sub-project"
       data-action="open-sub-project"
       data-project-id="{{ $project->id }}"
       data-tooltip="サブプロジェクト作成"
       aria-label="サブプロジェクト作成"
       role="button"
       tabindex="0"></i>
@endif

@if ($isChild)
    <span class="toggle-history"
          data-project-id="{{ $project->id }}"
          data-initial-state="{{ $project->status === '完了' ? 'collapsed' : 'expanded' }}">
        {{ $project->status === '完了' ? '▶' : '▼' }}
    </span>
@endif

<i class="fas fa-comment action-icon"
   data-action="open-progress"
   data-project-id="{{ $project->id }}"
   data-tooltip="コメント追加"
   aria-label="コメント追加"
   role="button"
   tabindex="0"></i>
