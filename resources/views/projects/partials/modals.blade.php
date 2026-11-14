<script type="application/json" id="subjectCatalog">@json($subjects->map(fn ($subject) => ['id' => $subject->id, 'name' => $subject->name])->values())</script>

<div id="addProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>議題を作る</h3>
        <form id="addProjectForm">
            <div class="form-group">
                <label for="projectName">議題名</label>
                <input type="text" id="projectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="projectSubjectQuery">対象</label>
                <div class="subject-picker" data-subject-picker>
                    <div class="subject-picker-field">
                        <input type="text" id="projectSubjectQuery" class="form-control subject-picker-input" placeholder="名前を入力" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="false" aria-controls="projectSubjectList" aria-autocomplete="list">
                        <div class="subject-picker-menu" id="projectSubjectList" hidden>
                            <div class="subject-picker-options" role="listbox"></div>
                            <button type="button" class="subject-picker-create">新規作成</button>
                        </div>
                    </div>
                    <input type="hidden" id="projectSubject" name="subject_id" value="">
                    <input type="hidden" id="newSubjectName" name="new_subject_name" value="">
                    <p class="subject-picker-status" hidden></p>
                </div>
            </div>
            <div class="form-group">
                <label for="projectDepartment">部署</label>
                <select id="projectDepartment" name="department" class="form-control">
                    @foreach ($departments as $department)
                        <option value="{{ $department }}">{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="teamMemberInput">チームメンバー</label>
                <div id="teamMemberTags" class="team-member-tags"></div>
                <div class="member-add-row">
                    <input type="text" id="teamMemberInput" class="form-control" placeholder="名前を入力">
                    <button type="button" class="btn btn-primary" id="teamMemberAddBtn">追加</button>
                </div>
                <input type="hidden" id="teamMembers" name="team_members" value="[]">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">作成</button>
                <button type="button" class="btn" data-close-modal="addProjectModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<div id="editProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>議題を編集</h3>
        <form id="editProjectForm">
            <input type="hidden" id="editProjectId" name="project_id">
            <div class="form-group">
                <label for="editProjectName">議題名</label>
                <input type="text" id="editProjectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="editProjectSubjectQuery">対象</label>
                <div class="subject-picker" data-subject-picker>
                    <div class="subject-picker-field">
                        <input type="text" id="editProjectSubjectQuery" class="form-control subject-picker-input" placeholder="名前を入力" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="false" aria-controls="editProjectSubjectList" aria-autocomplete="list">
                        <div class="subject-picker-menu" id="editProjectSubjectList" hidden>
                            <div class="subject-picker-options" role="listbox"></div>
                            <button type="button" class="subject-picker-create">新規作成</button>
                        </div>
                    </div>
                    <input type="hidden" id="editProjectSubject" name="subject_id" value="">
                    <input type="hidden" id="editNewSubjectName" name="new_subject_name" value="">
                    <p class="subject-picker-status" hidden></p>
                </div>
            </div>
            <div class="form-group" id="editDepartmentGroup">
                <label for="editProjectDepartment">部署</label>
                <select id="editProjectDepartment" name="department" class="form-control">
                    @foreach ($departments as $department)
                        <option value="{{ $department }}">{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="editTeamMemberInput">チームメンバー</label>
                <div id="editTeamMemberTags" class="team-member-tags"></div>
                <div class="member-add-row">
                    <input type="text" id="editTeamMemberInput" class="form-control" placeholder="名前を入力">
                    <button type="button" class="btn btn-primary" id="editTeamMemberAddBtn">追加</button>
                </div>
                <input type="hidden" id="editTeamMembers" name="team_members" value="[]">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">更新</button>
                <button type="button" class="btn" data-close-modal="editProjectModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<div id="editHistoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>コメントの編集</h3>
        <form id="editHistoryForm">
            <input type="hidden" id="editHistoryId" name="history_id">
            <div class="form-group">
                <label for="editHistoryContent">内容</label>
                <textarea id="editHistoryContent" name="content" class="form-control"></textarea>
            </div>
            <div class="form-group history-edit-actions">
                <button type="button" class="btn" id="reopenBallButton" hidden>ボールを戻す</button>
                <button type="submit" class="btn btn-primary">更新</button>
                <button type="button" class="btn" data-close-modal="editHistoryModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>
