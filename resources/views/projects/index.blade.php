@extends('layouts.app')

@section('title', 'プロジェクト管理')

@section('content')
<div class="project-list">
    <div class="action-buttons">
        <button class="btn btn-primary" type="button" data-action="open-add-project">＋新規プロジェクト作成</button>

        <div class="search-wrapper">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="memberSearch" class="search-input" placeholder="名前で検索..." autocomplete="off">
                <label class="toggle-switch">
                    <input type="checkbox" id="searchModeToggle">
                    <span class="toggle-slider"></span>
                </label>
                <i class="fas fa-times-circle clear-search" id="clearSearch" style="display: none;"></i>
            </div>
        </div>

        <div class="filter-wrapper">
            <select id="departmentFilter" class="form-control">
                <option value="all" @selected($departmentFilter === 'all')>すべての部署</option>
                <option value="選択なし" @selected($departmentFilter === '選択なし')>選択なし</option>
                @foreach ($departments as $department)
                    @if ($department !== '選択なし')
                        <option value="{{ $department }}" @selected($departmentFilter === $department)>{{ $department }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="filter-wrapper">
            <select id="statusFilter" class="form-control">
                <option value="all">すべてのステータス</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @forelse ($parents as $project)
        <div class="project-card parent-project"
             data-status="{{ $project->status }}"
             data-project-id="{{ $project->id }}"
             data-is-child="0">
            @include('projects.partials.project-header', ['project' => $project, 'isChild' => false])

            @if ($project->histories->isNotEmpty())
                @include('projects.partials.history-list', [
                    'histories' => $project->histories,
                    'contentPrefix' => 'content',
                ])
            @endif

            @if ($project->children->isNotEmpty())
                <div class="sub-projects">
                    @foreach ($project->children as $childProject)
                        <div class="project-card child-project"
                             data-status="{{ $childProject->status }}"
                             data-project-id="{{ $childProject->id }}"
                             data-is-child="1">
                            @include('projects.partials.project-header', ['project' => $childProject, 'isChild' => true])

                            @if ($childProject->histories->isNotEmpty())
                                @include('projects.partials.history-list', [
                                    'histories' => $childProject->histories,
                                    'contentPrefix' => 'content-child',
                                    'historyId' => $childProject->id,
                                    'collapsed' => $childProject->status === '完了',
                                ])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="project-card">
            <p>表示できるプロジェクトがありません。</p>
        </div>
    @endforelse
</div>

@push('modals')
    @include('projects.partials.modals')
    @include('projects.partials.attachment-modals')
@endpush

@include('projects.partials.status-dropdown')
@endsection

@push('scripts')
<script src="{{ asset('js/projects.js') }}?v={{ filemtime(public_path('js/projects.js')) }}"></script>
<script src="{{ asset('js/attachments.js') }}?v={{ filemtime(public_path('js/attachments.js')) }}"></script>
@endpush
