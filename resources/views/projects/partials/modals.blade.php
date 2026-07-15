<div id="addProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>新規プロジェクト作成</h3>
        <form id="addProjectForm">
            <div class="form-group">
                <label for="projectName">プロジェクト名</label>
                <input type="text" id="projectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="projectAuthor">作成者</label>
                <input type="text" id="projectAuthor" name="author" class="form-control" required>
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

<div id="subProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>サブプロジェクト作成</h3>
        <form id="addSubProjectForm">
            <input type="hidden" id="parentProjectId" name="parent_id">
            <div class="form-group">
                <label for="subProjectName">プロジェクト名</label>
                <input type="text" id="subProjectName" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="subProjectAuthor">作成者</label>
                <input type="text" id="subProjectAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="subTeamMemberInput">チームメンバー</label>
                <div id="subTeamMemberTags" class="team-member-tags"></div>
                <div class="member-add-row">
                    <input type="text" id="subTeamMemberInput" class="form-control" placeholder="名前を入力">
                    <button type="button" class="btn btn-primary" id="subTeamMemberAddBtn">追加</button>
                </div>
                <input type="hidden" id="subTeamMembers" name="team_members" value="[]">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">作成</button>
                <button type="button" class="btn" data-close-modal="subProjectModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<div id="editProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>プロジェクト編集</h3>
        <form id="editProjectForm">
            <input type="hidden" id="editProjectId" name="project_id">
            <div class="form-group">
                <label for="editProjectName">プロジェクト名</label>
                <input type="text" id="editProjectName" name="name" class="form-control" required>
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

<div id="progressModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>進捗追加</h3>
        <form id="addProgressForm">
            <input type="hidden" id="progressProjectId" name="project_id">
            <div class="form-group">
                <label for="progressAuthor">名前</label>
                <input type="text" id="progressAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="progressContent">進捗内容</label>
                <textarea id="progressContent" name="content" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">追加</button>
                <button type="button" class="btn" data-close-modal="progressModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<div id="editHistoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>進捗内容の編集</h3>
        <form id="editHistoryForm">
            <input type="hidden" id="editHistoryId" name="history_id">
            <div class="form-group">
                <label for="editHistoryAuthor">名前</label>
                <input type="text" id="editHistoryAuthor" name="author" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="editHistoryContent">進捗内容</label>
                <textarea id="editHistoryContent" name="content" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">更新</button>
                <button type="button" class="btn" data-close-modal="editHistoryModal">キャンセル</button>
            </div>
        </form>
    </div>
</div>
