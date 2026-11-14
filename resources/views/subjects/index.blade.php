@extends('layouts.app')

@section('title', '対象')

@section('content')
<div class="project-list home-page subjects-page">
    @if (session('status'))
        <p class="status-message success">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="status-message error">{{ $errors->first() }}</p>
    @endif

    <div class="home-sheet">
        <form method="POST" action="{{ route('subjects.store') }}" class="home-bar subject-create-form">
            @csrf
            <label class="home-search">
                <input type="text" name="name" placeholder="新しい対象の名前" required maxlength="255" aria-label="新しい対象の名前" autocomplete="off">
            </label>
            <button type="submit" class="home-create">対象を作る</button>
        </form>

        @forelse ($subjects as $subject)
            @php
                $renaming = $errors->has('name') && (string) old('subject_id') === (string) $subject->id;
            @endphp
            <section class="subject-card" id="subject-{{ $subject->id }}">
                <div @class(['subject-head', 'is-renaming' => $renaming])>
                    <h2 class="subject-name">{{ $subject->name }}</h2>
                    <form method="POST" action="{{ route('subjects.update', $subject) }}" class="subject-rename-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                        <input type="text" name="name" class="subject-name-input" value="{{ $renaming ? old('name') : $subject->name }}" required maxlength="255" aria-label="対象の名前">
                        <button type="submit" class="home-create subject-save">保存</button>
                        <button type="button" class="subject-cancel" data-action="cancel-rename">キャンセル</button>
                    </form>
                    <span class="subject-count">議題 {{ $subject->projects->count() }}</span>
                    <div class="subject-tools">
                        <button type="button" class="subject-icon" data-action="rename-subject" aria-label="名前を変える" title="名前を変える">
                            <i class="fas fa-edit" aria-hidden="true"></i>
                        </button>
                        @if ($subject->projects->isEmpty())
                            <form id="delete-subject-{{ $subject->id }}" method="POST" action="{{ route('subjects.destroy', $subject) }}" class="subject-delete-form" onsubmit="return confirm('この対象を削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="subject-icon subject-icon-delete" aria-label="削除" title="削除">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if ($subject->projects->isEmpty())
                    <p class="subject-empty">議題はありません</p>
                @else
                <div class="subject-topics">
                @foreach ($subject->projects as $topic)
                    <div class="subject-topic" id="project-{{ $topic->id }}">
                        <div class="topic-line">
                            <div class="topic-lead">
                                <a class="topic-name-link" href="{{ route('projects.show', $topic) }}">{{ $topic->name }}</a>
                                @if (!empty($topic->department) && $topic->department !== '選択なし')
                                    <span class="department-badge">{{ $topic->department }}</span>
                                @endif
                            </div>
                            <div class="topic-meta">
                                <span class="status-badge status-{{ $topic->status }}">{{ $topic->status }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>
                @endif
            </section>
        @empty
            <div class="home-empty">
                <p>対象はまだありません。上の欄から名前だけで作れます。</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-action="rename-subject"]').forEach((button) => {
    button.addEventListener('click', () => {
        const head = button.closest('.subject-head');
        head.classList.add('is-renaming');
        const input = head.querySelector('.subject-name-input');
        input.focus();
        input.select();
    });
});

document.querySelectorAll('[data-action="cancel-rename"]').forEach((button) => {
    button.addEventListener('click', () => {
        const head = button.closest('.subject-head');
        const name = head.querySelector('.subject-name').textContent;
        head.classList.remove('is-renaming');
        head.querySelector('.subject-name-input').value = name;
    });
});
</script>
@endpush
