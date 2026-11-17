<div class="form-group">
    <span class="member-picker-label" id="{{ $pickerId }}Label">チームメンバー</span>
    <div class="member-picker" id="{{ $pickerId }}" role="group" aria-labelledby="{{ $pickerId }}Label">
        @forelse ($employeeNames ?? [] as $name)
            <label class="member-picker-option">
                <input type="checkbox" value="{{ $name }}">
                <span>{{ $name }}</span>
            </label>
        @empty
            <p class="member-picker-empty">社員一覧が空です。先に社員を登録してください。</p>
        @endforelse
    </div>
    <input type="hidden" id="{{ $hiddenId }}" name="team_members" value="[]">
</div>
