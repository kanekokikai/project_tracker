</main>
<!-- 添付ファイルモーダル -->
<div id="attachment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="visibility: hidden;">添付ファイル</h2>
            <span class="close">&times;</span>
        </div>            
        <div class="modal-body">
            <div class="upload-section">
                <h3>ファイルをアップロード</h3>
                <div id="drop-area" class="drop-area">
                    <form id="file-upload-form" enctype="multipart/form-data">
                        <input type="hidden" id="project-id-input" name="project_id">
                        <div class="file-input-container">
                            <input type="file" id="file-input" name="file" class="file-input">
                            <p>ファイルをドラッグ＆ドロップするか、<label for="file-input" class="file-input-label">ファイルを選択</label></p>
                            <p id="selected-file-name" class="selected-file">選択されていません</p>
                        </div>
                        <button type="submit" id="upload-btn" class="upload-button" disabled>アップロード</button>
                    </form>
                    <div id="upload-progress" class="progress-bar-container" style="display: none;">
                        <div class="progress-bar"></div>
                    </div>
                    <div id="upload-message"></div>
                </div>
            </div>
            
            <div class="attachments-list-section">
                <h3>ファイル一覧</h3>
                <div id="attachments-container">
                    <p class="no-attachments">添付ファイルはありません</p>
                    <table id="attachments-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>ファイル名</th>
                                <th>サイズ</th>
                                <th>アップロード日</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="attachments-list">
                            <!-- ここに添付ファイルが動的に追加されます -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ファイルプレビューモーダル -->
<div id="file-preview-modal" class="modal">
    <div class="modal-content preview-content">
        <div class="modal-header">
            <h2 id="preview-title">ファイルプレビュー</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="file-preview-container">
                <!-- ここにファイルプレビューが表示されます -->
            </div>
            <div class="preview-actions">
                <a id="preview-download" class="preview-btn" href="#" target="_blank" download>ダウンロード</a>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル（重複を削除）-->
<div id="delete-confirm-modal" class="modal">
    <div class="modal-content delete-confirm-content">
        <div class="modal-header">
            <h2>削除の確認</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>この添付ファイルを削除してもよろしいですか？</p>
            <p id="delete-file-name"></p>
            <div class="button-container">
                <button id="cancel-delete" class="cancel-button">キャンセル</button>
                <button id="confirm-delete" class="delete-button">削除</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $basePath; ?>/js/main.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo $basePath; ?>/js/attachment_scripts.js?v=<?php echo time(); ?>"></script>

<script>
// 部署フィルター処理
function filterByDepartment(department) {
    // URLを作成
    let currentUrl = new URL(window.location.href);
    
    // 既存のステータスパラメータを保持
    let status = currentUrl.searchParams.get('status');
    
    // URLパラメータをリセット
    currentUrl.search = '';
    
    // 新しいパラメータを設定
    if (department) {
        currentUrl.searchParams.set('department', department);
    }
    
    // ステータスパラメータを保持
    if (status) {
        currentUrl.searchParams.set('status', status);
    }
    
    // ページをリロード
    window.location.href = currentUrl.toString();
}

// プロジェクト新規作成モーダルが開かれたとき
document.addEventListener('DOMContentLoaded', function() {
    
// URL から department パラメータを取得
const urlParams = new URLSearchParams(window.location.search);
const department = urlParams.get('department');

// 部署フィルターのセレクトボックスを取得
const departmentFilter = document.getElementById('departmentFilter');

// 現在の部署を選択状態にする
if (department && departmentFilter) {
    for (let i = 0; i < departmentFilter.options.length; i++) {
        if (departmentFilter.options[i].value === department) {
            departmentFilter.selectedIndex = i;
            break;
        }
    }
}    
    
// プロジェクト編集モーダルが開かれたときの処理
window.openEditProjectModal = function(projectId, projectName) {
    document.getElementById('editProjectId').value = projectId;
    document.getElementById('editProjectName').value = projectName;
    
    // チームメンバータグをクリア
    const editTagsContainer = document.getElementById('editTeamMemberTags');
    editTagsContainer.innerHTML = '';
    
    // 部署情報とチームメンバー情報を取得
    fetch(`api/get_project.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // サブプロジェクトかどうかチェック（parent_idがあればサブプロジェクト）
                const isSubProject = data.project.parent_id !== null;
                const departmentFormGroup = document.querySelector('#editProjectModal .form-group:has(#editProjectDepartment)');
                
                // サブプロジェクトの場合は部署選択を非表示
                if (isSubProject && departmentFormGroup) {
                    departmentFormGroup.style.display = 'none';
                } else if (departmentFormGroup) {
                    departmentFormGroup.style.display = 'block';
                    
                    // 部署設定（メインプロジェクトの場合のみ）
                    const department = data.project.department || '選択なし';
                    const departmentSelect = document.getElementById('editProjectDepartment');
                    
                    for (let i = 0; i < departmentSelect.options.length; i++) {
                        if (departmentSelect.options[i].value === department) {
                            departmentSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
                
                // チームメンバー設定
                if (data.project.team_members) {
                    const members = JSON.parse(data.project.team_members);
                    document.getElementById('editTeamMembers').value = JSON.stringify(members);
                    
                    members.forEach(member => {
                        const tag = document.createElement('div');
                        tag.className = 'member-tag';
                        tag.innerHTML = `
                            ${member}
                            <span class="remove-tag" onclick="removeMemberTag(this, 'edit')">&times;</span>
                        `;
                        editTagsContainer.appendChild(tag);
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
    
    document.getElementById('editProjectModal').style.display = 'flex';
};


// 既存の編集プロジェクトフォーム送信ハンドラを上書き
const editProjectForm = document.getElementById('editProjectForm');
if (editProjectForm) {
    editProjectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const projectId = document.getElementById('editProjectId').value;
        
        // サブプロジェクトかどうかを確認
        fetch(`api/get_project.php?id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const formData = new FormData();
                    formData.append('project_id', projectId);
                    formData.append('name', document.getElementById('editProjectName').value);
                    formData.append('team_members', document.getElementById('editTeamMembers').value);
                    
                    // サブプロジェクトの場合
                    if (data.project.parent_id !== null) {
                        // 親プロジェクトの部署を取得して設定するか、そのまま現在の部署を維持
                        formData.append('department', data.project.department || '選択なし');
                    } else {
                        // メインプロジェクトの場合はフォームから部署を取得
                        formData.append('department', document.getElementById('editProjectDepartment').value);
                    }
                    
                    fetch('api/edit_project.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeEditProjectModal();
                            location.reload();
                        } else {
                            alert(data.message || 'エラーが発生しました。');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('通信エラーが発生しました。');
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('プロジェクト情報の取得に失敗しました。');
            });
    });
}


// 既存の新規プロジェクトフォーム送信ハンドラを上書き
const addProjectForm = document.getElementById('addProjectForm');
if (addProjectForm) {
    addProjectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('name', document.getElementById('projectName').value);
        formData.append('author', document.getElementById('projectAuthor').value);
        formData.append('team_members', document.getElementById('teamMembers').value);
        formData.append('department', document.getElementById('projectDepartment').value);
        
        fetch('api/add_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddProjectModal();
                location.reload();
            } else {
                alert(data.message || 'エラーが発生しました。');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました。');
        });
    });
}   
});
</script>

</body>
</html>