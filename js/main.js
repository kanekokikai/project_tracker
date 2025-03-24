// fetch関数に共通のオプションを設定（キャッシュ制御を追加）
function fetchWithCache(url, options = {}) {
    const defaultOptions = {
        cache: 'no-store', // キャッシュを使用しない
        headers: {
            'Pragma': 'no-cache',
            'Cache-Control': 'no-cache'
        }
    };
    
    // ヘッダーをマージ
    const mergedOptions = { ...defaultOptions, ...options };
    if (options.headers) {
        mergedOptions.headers = { ...defaultOptions.headers, ...options.headers };
    }
    
    return fetch(url, mergedOptions);
}

// モーダル関連の関数
function openAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'block';
    
    // フォームをリセット
    document.getElementById('addProjectForm').reset();
    document.getElementById('teamMembers').value = '[]';
    document.getElementById('teamMemberTags').innerHTML = '';
    
    // スタイルを直接適用
    const inputField = document.getElementById('projectName');
    if (inputField) {
        inputField.style.width = '100%';
        inputField.style.padding = '0.8rem';
    }
}


function closeAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'none';
}

function openProgressModal(projectId) {
    document.getElementById('progressProjectId').value = projectId;
    document.getElementById('progressModal').style.display = 'block';
    
    // スタイルを直接適用
    const authorField = document.getElementById('progressAuthor');
    const contentField = document.getElementById('progressContent');
    
    if (authorField) {
        authorField.style.width = '100%';
        authorField.style.padding = '0.8rem';
    }
    
    if (contentField) {
        contentField.style.width = '100%';
        contentField.style.minHeight = '150px';
        contentField.style.padding = '0.8rem';
    }
}

function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
}

function openStatusModal(projectId) {
    document.getElementById('statusProjectId').value = projectId;
    document.getElementById('statusModal').style.display = 'block';
    
    // スタイルを直接適用
    const authorField = document.getElementById('statusAuthor');
    const statusField = document.getElementById('newStatus');
    
    if (authorField) {
        authorField.style.width = '100%';
        authorField.style.padding = '0.8rem';
    }
    
    if (statusField) {
        statusField.style.width = '100%';
        statusField.style.padding = '0.8rem';
    }
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// 履歴の折りたたみ機能（直接DOM操作版）
function toggleHistoryDirect(projectId) {
    console.log("Direct toggle for project: " + projectId);
    
    var historyContent = document.getElementById('history-content-' + projectId);
    var toggleButton = document.querySelector('.toggle-history[data-project-id="' + projectId + '"]');
    
    if (!historyContent) {
        console.error("History content not found for project ID: " + projectId);
        return false;
    }
    
    // コンピュテッドスタイルを取得して実際の表示状態を確認
    var computedStyle = window.getComputedStyle(historyContent);
    var isHidden = computedStyle.display === 'none';
    
    if (isHidden) {
        // 履歴を展開
        historyContent.style.display = 'block';
        toggleButton.textContent = '▼';
        console.log("Expanded history directly for project ID: " + projectId);
    } else {
        // 履歴を折りたたむ
        historyContent.style.display = 'none';
        toggleButton.textContent = '▶';
        console.log("Collapsed history directly for project ID: " + projectId);
    }
    
    // イベントの伝播を止める
    return false;
}


// 履歴一覧モーダル関連
function openHistoryModal(projectId) {
    document.getElementById('historyModal').style.display = 'block';
    
    // 読み込み中表示
    document.getElementById('historyList').innerHTML = '<p>読み込み中...</p>';
    
    fetchWithCache('api/get_history.php?project_id=' + projectId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const historyList = document.getElementById('historyList');
                historyList.innerHTML = data.history.map(hist => `
                    <div class="history-item-list" data-history-id="${hist.id}">
                        <div class="history-header">
                            <span class="author">${hist.author}</span>
                            <div class="history-actions">
                                <span class="date">${new Date(hist.created_at).toLocaleString('ja-JP')}</span>
                                <div class="inline-actions">
                                    <i class="fas fa-edit mini-btn edit-btn" onclick="openEditHistoryModal(${hist.id})" title="編集"></i>
                                    <i class="fas fa-trash-alt mini-btn delete-btn" onclick="confirmDeleteHistory(${hist.id})" title="削除"></i>
                                </div>
                            </div>
                        </div>
                        ${hist.status ? 
                            `<div class="status-change">
                                ステータスを「${hist.status}」に変更
                            </div>` : 
                            ''
                        }
                        ${hist.content ? 
                            `<div class="content">
                                ${hist.content.replace(/\n/g, '<br>')}
                            </div>` : 
                            ''
                        }
                    </div>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('historyList').innerHTML = '<p>履歴の取得に失敗しました。</p>';
        });
}




// 履歴モーダルを閉じる関数
function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
}


// プロジェクト削除
function confirmDelete(projectId) {
    if (confirm('このプロジェクトを削除してもよろしいですか？')) {
        fetchWithCache('api/delete_project.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ project_id: projectId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('エラーが発生しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました');
        });
    }
}

// ステータスによる絞り込み
function filterByStatus(status) {
    const projectCards = document.querySelectorAll('.project-card');
    
    projectCards.forEach(card => {
        if (status === 'all') {
            card.style.display = 'block';
        } else {
            const cardStatus = card.querySelector('.status-badge').textContent.trim();
            card.style.display = cardStatus === status ? 'block' : 'none';
        }
    });
}

// 子プロジェクトモーダル関連の関数
function openSubProjectModal(projectId) {
    document.getElementById('parentProjectId').value = projectId;
    document.getElementById('subProjectModal').style.display = 'block';
    
    // スタイルを直接適用
    const nameField = document.getElementById('subProjectName');
    const authorField = document.getElementById('subProjectAuthor');
    
    if (nameField) {
        nameField.style.width = '100%';
        nameField.style.padding = '0.8rem';
        nameField.style.fontSize = '1rem';
        nameField.style.boxSizing = 'border-box';
    }
    
    if (authorField) {
        authorField.style.width = '100%';
        authorField.style.padding = '0.8rem';
        authorField.style.fontSize = '1rem';
        authorField.style.boxSizing = 'border-box';
    }
}


// 履歴編集モーダル関連
function openEditHistoryModal(historyId) {
    // 履歴データの取得
    fetchWithCache('api/get_history_detail.php?history_id=' + historyId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // フォームに値をセット
                document.getElementById('editHistoryId').value = data.history.id;
                document.getElementById('editHistoryAuthor').value = data.history.author;
                document.getElementById('editHistoryContent').value = data.history.content || '';
                
                // モーダルを表示
                document.getElementById('editHistoryModal').style.display = 'block';
            } else {
                alert('エラーが発生しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('履歴の取得に失敗しました');
        });
}

function closeEditHistoryModal() {
    document.getElementById('editHistoryModal').style.display = 'none';
}

// 履歴削除確認
function confirmDeleteHistory(historyId) {
    console.log("削除しようとしている履歴ID:", historyId); // デバッグ用

    if (!historyId || isNaN(parseInt(historyId))) {
        console.error("無効な履歴ID:", historyId);
        alert('有効な履歴IDが指定されていません');
        return;
    }

    if (confirm('この進捗を削除してもよろしいですか？')) {
        // URLエンコードされたフォームデータを使用
        const formData = new FormData();
        formData.append('history_id', historyId);
        
        console.log("送信するデータ:", historyId); // デバッグ用
        
        // 履歴の削除
        fetchWithCache('api/delete_history.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log("サーバーからのレスポンスステータス:", response.status); // デバッグ用
            return response.json();
        })
        .then(data => {
            console.log("処理結果:", data); // デバッグ用
            if (data.success) {
                alert('進捗が削除されました');
                location.reload();
            } else {
                console.error("削除エラー:", data.message);
                alert('エラーが発生しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('削除処理中のエラー:', error);
            alert('履歴の削除に失敗しました');
        });
    }
}

// ページ読み込み完了時に実行 - すべての初期化処理を1つのリスナーにまとめる
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded - Initializing functionality");
    
    // ページロード時のパフォーマンス最適化
    performance.mark('app-init-start');

    // 非同期でリソースをプリロード
    setTimeout(() => {
        // モーダル要素の事前初期化
        const modals = [
            'addProjectModal', 
            'progressModal', 
            'statusModal', 
            'subProjectModal', 
            'historyModal',
            'editHistoryModal'
        ];
        
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (modal) {
                // モーダルの初期スタイルを設定
                if (window.getComputedStyle(modal).display !== 'none') {
                    modal.style.display = 'none';
                }
            }
        });
        
        performance.mark('app-init-end');
        performance.measure('app-initialization', 'app-init-start', 'app-init-end');
        console.log('App initialized');
    }, 100);
    

// 検索バーの機能設定
const memberSearch = document.getElementById('memberSearch');
const clearSearch = document.getElementById('clearSearch');
const searchModeToggle = document.getElementById('searchModeToggle');

if (memberSearch && clearSearch && searchModeToggle) {
    // 検索モード切り替え
    searchModeToggle.addEventListener('change', function() {
        if (this.checked) {
            // プロジェクト名検索モード
            memberSearch.placeholder = "プロジェクト名で検索...";
        } else {
            // 名前検索モード（デフォルト）
            memberSearch.placeholder = "名前で検索...";
        }
        
        // 現在の検索語で再検索
        if (memberSearch.value.trim() !== '') {
            performSearch(memberSearch.value.trim());
        }
    });
    
    // 検索機能
    memberSearch.addEventListener('input', function() {
        const searchValue = this.value.trim();
        
        // クリアボタンの表示/非表示
        if (searchValue.length > 0) {
            clearSearch.style.display = 'block';
        } else {
            clearSearch.style.display = 'none';
        }
        
        // 検索の実行
        performSearch(searchValue);
    });
    
    // クリアボタン
    clearSearch.addEventListener('click', function() {
        memberSearch.value = '';
        this.style.display = 'none';
        performSearch(''); // 検索をクリア
    });
}

// 検索実行関数
function performSearch(searchValue) {
    const isProjectSearch = document.getElementById('searchModeToggle').checked;
    
    if (isProjectSearch) {
        // プロジェクト名検索
        filterByProjectName(searchValue);
    } else {
        // 名前検索
        filterByMemberName(searchValue);
    }
}

// プロジェクト名での検索フィルタリング関数
function filterByProjectName(name) {
    const projectCards = document.querySelectorAll('.project-card');
    let hasVisibleProjects = false;
    
    if (name === '') {
        // 空の検索語の場合はすべて表示（ただしステータスフィルターは維持）
        projectCards.forEach(card => {
            const currentStatus = document.getElementById('statusFilter').value;
            if (currentStatus === 'all') {
                card.style.display = 'block';
            } else {
                const cardStatus = card.querySelector('.status-badge').textContent.trim();
                card.style.display = cardStatus === currentStatus ? 'block' : 'none';
            }
        });
        return;
    }
    
    projectCards.forEach(card => {
        // プロジェクト名を取得
        const projectNameElement = card.querySelector('.project-name');
        let shouldShow = false;
        
        if (projectNameElement) {
            // プロジェクト名のテキストを取得（ただしチームメンバーツールチップを除く）
            const projectNameText = projectNameElement.childNodes[0].nodeValue || projectNameElement.textContent;
            if (projectNameText.toLowerCase().includes(name.toLowerCase())) {
                shouldShow = true;
            }
        }
        
        // ステータスフィルターも考慮する
        const currentStatus = document.getElementById('statusFilter').value;
        if (shouldShow && (currentStatus === 'all' || 
            card.querySelector('.status-badge').textContent.trim() === currentStatus)) {
            card.style.display = 'block';
            hasVisibleProjects = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    // 検索結果がない場合のメッセージ表示
    updateNoResultsMessage(hasVisibleProjects);
}

// メンバー名での検索フィルタリング関数
function filterByMemberName(name) {
    const projectCards = document.querySelectorAll('.project-card');
    let hasVisibleProjects = false;
    
    if (name === '') {
        // 空の検索語の場合はすべて表示（ただしステータスフィルターは維持）
        projectCards.forEach(card => {
            const currentStatus = document.getElementById('statusFilter').value;
            if (currentStatus === 'all') {
                card.style.display = 'block';
            } else {
                const cardStatus = card.querySelector('.status-badge').textContent.trim();
                card.style.display = cardStatus === currentStatus ? 'block' : 'none';
            }
        });
        return;
    }
    
    projectCards.forEach(card => {
        // チームメンバー情報を取得
        const teamMembersElement = card.querySelector('.project-name .team-members-tooltip');
        let shouldShow = false;
        
        if (teamMembersElement) {
            // チームメンバーの名前を全て取得
            const memberElements = teamMembersElement.querySelectorAll('.team-member-name');
            memberElements.forEach(element => {
                if (element.textContent.toLowerCase().includes(name.toLowerCase())) {
                    shouldShow = true;
                }
            });
        }
        
        // 履歴内の作成者も検索
        const authorAvatars = card.querySelectorAll('.author-avatar');
        authorAvatars.forEach(avatar => {
            const authorName = avatar.getAttribute('data-author-name');
            if (authorName && authorName.toLowerCase().includes(name.toLowerCase())) {
                shouldShow = true;
            }
        });
        
        // ステータスフィルターも考慮する
        const currentStatus = document.getElementById('statusFilter').value;
        if (shouldShow && (currentStatus === 'all' || 
            card.querySelector('.status-badge').textContent.trim() === currentStatus)) {
            card.style.display = 'block';
            hasVisibleProjects = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    // 検索結果がない場合のメッセージ表示
    updateNoResultsMessage(hasVisibleProjects);
}

// 検索結果がない場合のメッセージ表示・非表示を処理する関数
function updateNoResultsMessage(hasVisibleProjects) {
    const noResultsMessage = document.getElementById('noSearchResults');
    if (!hasVisibleProjects) {
        if (!noResultsMessage) {
            const message = document.createElement('div');
            message.id = 'noSearchResults';
            message.className = 'no-results-message';
            message.textContent = '検索結果がありません';
            document.querySelector('.project-list').appendChild(message);
        }
    } else if (noResultsMessage) {
        noResultsMessage.remove();
    }
}


    // モーダル要素の存在確認（デバッグ用）
    console.log("Modal elements check:");
    console.log("addProjectModal exists:", !!document.getElementById('addProjectModal'));
    console.log("progressModal exists:", !!document.getElementById('progressModal'));
    console.log("statusModal exists:", !!document.getElementById('statusModal'));
    console.log("subProjectModal exists:", !!document.getElementById('subProjectModal'));
    console.log("historyModal exists:", !!document.getElementById('historyModal'));
    console.log("editHistoryModal exists:", !!document.getElementById('editHistoryModal'));
    
// プロジェクト追加フォーム
const addProjectForm = document.getElementById('addProjectForm');
if (addProjectForm) {
    // フォームのsubmitイベントを削除して、ボタンのクリックイベントに置き換え
    const submitButton = addProjectForm.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            const formData = new FormData(addProjectForm);
            
            // チームメンバー情報が空でないか確認
            if (document.getElementById('teamMembers').value === '') {
                document.getElementById('teamMembers').value = '[]';
            }
            
            fetchWithCache('api/add_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('エラーが発生しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            });
        });
    }
    
    // フォームのsubmitイベントを無効化
    addProjectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        return false;
    });
}


// チームメンバー入力欄のセットアップ
setupTeamMemberInput('teamMemberInput', 'teamMemberTags', 'teamMembers');
setupTeamMemberInput('editTeamMemberInput', 'editTeamMemberTags', 'editTeamMembers');

// 作成者入力欄の変更を検知してチームメンバーに追加
const projectAuthor = document.getElementById('projectAuthor');
if (projectAuthor) {
    projectAuthor.addEventListener('change', addAuthorToTeam);
    projectAuthor.addEventListener('blur', addAuthorToTeam);
}

// 編集モーダルのチームメンバー入力処理
const editTeamMemberInput = document.getElementById('editTeamMemberInput');
if (editTeamMemberInput) {
    editTeamMemberInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            const value = this.value.trim();
            if (value) {
                try {
                    const currentMembers = JSON.parse(document.getElementById('editTeamMembers').value);
                    if (!currentMembers.includes(value)) {
                        currentMembers.push(value);
                        document.getElementById('editTeamMembers').value = JSON.stringify(currentMembers);
                        
                        const tagsContainer = document.getElementById('editTeamMemberTags');
                        const tag = document.createElement('div');
                        tag.className = 'team-member-tag';
                        tag.innerHTML = `
                            ${value}
                            <span class="delete-tag" data-index="${currentMembers.length - 1}">×</span>
                        `;
                        tagsContainer.appendChild(tag);
                        
                        // 削除ボタンにイベントリスナーを追加
                        tag.querySelector('.delete-tag').addEventListener('click', function() {
                            const idx = parseInt(this.getAttribute('data-index'));
                            const members = JSON.parse(document.getElementById('editTeamMembers').value);
                            members.splice(idx, 1);
                            document.getElementById('editTeamMembers').value = JSON.stringify(members);
                            this.parentElement.remove();
                            // 他のタグのデータインデックスを更新
                            updateDataIndexes('editTeamMemberTags');
                        });
                    }
                } catch (e) {
                    console.error('チームメンバーデータの操作エラー:', e);
                }
                this.value = '';
            }
            return false;
        }
    });
}


    // 進捗追加フォーム
    const addProgressForm = document.getElementById('addProgressForm');
    if (addProgressForm) {
        addProgressForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetchWithCache('api/add_progress.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('エラーが発生しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            });
        });
    }
    
    // ステータス変更フォーム
    const changeStatusForm = document.getElementById('changeStatusForm');
    if (changeStatusForm) {
        changeStatusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetchWithCache('api/change_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('エラーが発生しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            });
        });
    }
    
    // 子プロジェクト追加フォーム
    const addSubProjectForm = document.getElementById('addSubProjectForm');
    if (addSubProjectForm) {
        addSubProjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetchWithCache('api/add_sub_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('エラーが発生しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
            });
        });
    }
    
// 履歴編集フォーム
const editHistoryForm = document.getElementById('editHistoryForm');
if (editHistoryForm) {
    editHistoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetchWithCache('api/edit_history.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('進捗が更新されました');
                location.reload();
            } else {
                alert('エラーが発生しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('履歴の更新に失敗しました');
        });
    });
}

// プロジェクト編集フォーム
const editProjectForm = document.getElementById('editProjectForm');
if (editProjectForm) {
    editProjectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetchWithCache('api/edit_project.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('プロジェクトが更新されました');
                location.reload();
            } else {
                alert('エラーが発生しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('プロジェクトの更新に失敗しました');
        });
    });
}




    // トグルボタンのイベント設定
    const toggleButtons = document.querySelectorAll('.toggle-history');
    console.log(`Found ${toggleButtons.length} toggle buttons`);
    
    toggleButtons.forEach(button => {
        const projectId = button.getAttribute('data-project-id');
        console.log(`Setting up toggle for project ${projectId}`);
        
        // クリックイベントを追加
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`Toggle button clicked for project ${projectId}`);
            toggleHistory(projectId);
        });
    });


// チームメンバータグの追加と削除を管理する関数
function setupTeamMemberInput(inputId, tagsId, hiddenInputId) {
    const input = document.getElementById(inputId);
    const tagsContainer = document.getElementById(tagsId);
    const hiddenInput = document.getElementById(hiddenInputId);
    
    if (!input || !tagsContainer || !hiddenInput) return;
    
    // 現在のメンバーリスト
    let members = [];
    
    // hiddenInputの初期値があれば読み込む - 常に最新の値を取得するために関数化
    function loadMembers() {
      try {
        if (hiddenInput.value && hiddenInput.value !== '[]') {
          members = JSON.parse(hiddenInput.value);
        }
      } catch (e) {
        console.error('チームメンバーデータの解析エラー:', e);
      }
    }
    
    // 初期値の読み込みと描画
    loadMembers();
    renderTags();
    
    // Enterキーで入力を処理 - フォーム送信を防止
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        
        // 最新の値を読み込み直す（他の場所で更新されている可能性があるため）
        loadMembers();
        
        const value = input.value.trim();
        if (value && !members.includes(value)) {
          members.push(value);
          renderTags();
          updateHiddenInput();
          input.value = '';
        }
        return false;
      }
    });

    // タグを描画する関数
    function renderTags() {
      tagsContainer.innerHTML = '';
      
      members.forEach((member, index) => {
        const tag = document.createElement('div');
        tag.className = 'team-member-tag';
        tag.innerHTML = `
          ${member}
          <span class="delete-tag" data-index="${index}">×</span>
        `;
        tagsContainer.appendChild(tag);
        
        // 削除ボタンにイベントリスナーを追加
        tag.querySelector('.delete-tag').addEventListener('click', function() {
          const idx = parseInt(this.getAttribute('data-index'));
          members.splice(idx, 1);
          renderTags();
          updateHiddenInput();
        });
      });
    }

    // hiddenInputを更新する関数
    function updateHiddenInput() {
      hiddenInput.value = JSON.stringify(members);
    }
}

  // data-indexを更新する関数
  function updateDataIndexes(containerId) {
    const container = document.getElementById(containerId);
    const tags = container.querySelectorAll('.team-member-tag');
    tags.forEach((tag, index) => {
      tag.querySelector('.delete-tag').setAttribute('data-index', index);
    });
  }
  
    
// 初期状態の設定（完了プロジェクトの履歴を折りたたむ）
setTimeout(function() {
    const completedProjects = document.querySelectorAll('.child-project[data-status="完了"]');
    console.log(`Found ${completedProjects.length} completed projects to collapse`);
    
    completedProjects.forEach(project => {
        const projectId = project.querySelector('.toggle-history').getAttribute('data-project-id');
        const historyContent = document.getElementById('history-content-' + projectId);
        const toggleButton = project.querySelector('.toggle-history');
        
        if (historyContent) {
            console.log(`Collapsing history for completed project ${projectId}`);
            historyContent.style.display = 'none';
            toggleButton.textContent = '▶';
        }
    });
}, 100); // 少し遅延させて確実にDOM要素が利用可能になってから実行    

    
    // 認証フォーム
    const authForm = document.getElementById('authForm');
    if (authForm) {
        authForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const authMessage = document.getElementById('authMessage');
            
            // パスワードが空でないか確認
            if (!password) {
                authMessage.textContent = 'パスワードを入力してください';
                return;
            }
            
            // フォームデータを作成
            const formData = new FormData();
            formData.append('password', password);
            
            // 認証APIを呼び出し
            fetchWithCache('api/authenticate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 認証成功
                    const authOverlay = document.querySelector('.auth-overlay');
                    authOverlay.classList.add('authenticated');
                    
                    // モーダルを閉じる
                    setTimeout(() => {
                        document.getElementById('authModal').style.display = 'none';
                        document.querySelector('.main-content').classList.remove('blur-content');
                    }, 500);
                } else {
                    // 認証失敗
                    authMessage.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                authMessage.textContent = '認証中にエラーが発生しました';
            });
        });
    }
    
    // モーダルの外側をクリックした時に閉じる（認証モーダル以外）
    window.onclick = function(event) {
        if (event.target.classList.contains('modal') && event.target.id !== 'authModal') {
            event.target.style.display = 'none';
        }
    };
});

// ステータスドロップダウンを表示
function showStatusDropdown(element, event) {
    event.stopPropagation(); // イベントの伝播を停止
    
    const projectId = element.getAttribute('data-project-id');
    const rect = element.getBoundingClientRect();
    const dropdown = document.getElementById('status-dropdown');
    
    // 位置を設定
    dropdown.style.top = (window.scrollY + rect.bottom + 5) + 'px';
    dropdown.style.left = (rect.left) + 'px';
    
    // 表示
    dropdown.style.display = 'block';
    
    // すべてのステータスオプションにクリックイベントを追加
    const options = dropdown.querySelectorAll('.status-option');
    options.forEach(option => {
      option.onclick = function() {
        const newStatus = this.getAttribute('data-status');
        updateProjectStatus(projectId, newStatus);
        dropdown.style.display = 'none';
      };
    });
    
    // データ属性にプロジェクトIDを設定
    dropdown.setAttribute('data-project-id', projectId);
  }
  
// ドキュメント内の任意の場所をクリックしたときにドロップダウンを非表示
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('status-dropdown');
    if (dropdown && dropdown.style.display === 'block') {  // dropdownの存在確認を追加
      dropdown.style.display = 'none';
    }
  });

  // プロジェクトのステータスを更新する関数
  function updateProjectStatus(projectId, newStatus) {
    // Ajaxリクエストを作成
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/update_status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    // リクエスト完了時の処理
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.success) {
            // 成功した場合、ページをリロード
            location.reload();
          } else {
            alert('ステータスの更新に失敗しました: ' + response.message);
          }
        } catch (e) {
          alert('エラーが発生しました: ' + e.message);
        }
      } else {
        alert('リクエストエラー: ' + xhr.status);
      }
    };
    
    // リクエスト失敗時の処理
    xhr.onerror = function() {
      alert('ネットワークエラーが発生しました');
    };
    
    // リクエスト送信
    xhr.send('project_id=' + projectId + '&status=' + newStatus);
  }


  // 長いコンテンツを展開/折りたたむ関数
function toggleContent(contentId) {
    const contentElement = document.getElementById(contentId);
    const toggleButton = contentElement.nextElementSibling;
    
    if (contentElement.classList.contains('expanded')) {
        // 折りたたむ
        contentElement.classList.remove('expanded');
        toggleButton.textContent = '続きを読む';
    } else {
        // 展開する
        contentElement.classList.add('expanded');
        toggleButton.textContent = '折りたたむ';
    }
}

// プロジェクト名編集モーダルの関数
function openEditProjectModal(projectId, projectName) {
    console.log("プロジェクト編集モーダルを開きます: ID=", projectId);
    
    // 基本情報を設定
    document.getElementById('editProjectId').value = projectId;
    document.getElementById('editProjectName').value = projectName;
    
    // APIでプロジェクト情報を取得
    fetch(`api/get_project_details.php?project_id=${projectId}`)
      .then(response => response.json())
      .then(data => {
        console.log("取得したプロジェクト情報:", data);
        
        // チームメンバー情報を取得して表示
        if (data.success && data.project && data.project.team_members) {
          try {
            const members = JSON.parse(data.project.team_members);
            console.log("メンバー:", members);
            
            // hidden入力にチームメンバー情報をセット
            document.getElementById('editTeamMembers').value = data.project.team_members;
            
            // チームメンバータグを表示
            const tagsContainer = document.getElementById('editTeamMemberTags');
            tagsContainer.innerHTML = '';
            
            // 各メンバーのタグを生成
            if (Array.isArray(members) && members.length > 0) {
              members.forEach((member, index) => {
                const tag = document.createElement('div');
                tag.className = 'team-member-tag';
                tag.textContent = member;
                
                // 削除ボタンを追加
                const deleteBtn = document.createElement('span');
                deleteBtn.className = 'delete-tag';
                deleteBtn.textContent = '×';
                deleteBtn.setAttribute('data-index', index);
                deleteBtn.onclick = function() {
                  // メンバーを削除
                  const idx = parseInt(this.getAttribute('data-index'));
                  const members = JSON.parse(document.getElementById('editTeamMembers').value);
                  members.splice(idx, 1);
                  document.getElementById('editTeamMembers').value = JSON.stringify(members);
                  this.parentElement.remove();
                  updateDataIndexes('editTeamMemberTags');
                };
                
                tag.appendChild(deleteBtn);
                tagsContainer.appendChild(tag);
              });
            }
          } catch (e) {
            console.error('JSONパースエラー:', e);
          }
        }
      })
      .catch(error => console.error('データ取得エラー:', error));
    
    // モーダルを表示
    document.getElementById('editProjectModal').style.display = 'block';
  }




// 作成者を自動的にチームメンバーに追加する関数
function addAuthorToTeam() {
    const authorField = document.getElementById('projectAuthor');
    const teamMembersHidden = document.getElementById('teamMembers');
    
    if (!authorField || !teamMembersHidden) return;
    
    const authorName = authorField.value.trim();
    if (!authorName) return;
    
    // 現在のメンバーリストを取得
    let members = [];
    try {
      if (teamMembersHidden.value) {
        members = JSON.parse(teamMembersHidden.value);
      }
    } catch (e) {
      console.error('チームメンバーデータの解析エラー:', e);
    }
    
    // 作成者が既にリストにない場合のみ追加
    if (!members.includes(authorName)) {
      members.push(authorName);
      teamMembersHidden.value = JSON.stringify(members);
      
      // setupTeamMemberInputの関数を再利用して描画を更新
      const tagsContainer = document.getElementById('teamMemberTags');
      if (tagsContainer) {
        tagsContainer.innerHTML = '';
        members.forEach((member, index) => {
          const tag = document.createElement('div');
          tag.className = 'team-member-tag';
          tag.innerHTML = `
            ${member}
            <span class="delete-tag" data-index="${index}">×</span>
          `;
          tagsContainer.appendChild(tag);
          
          // 削除ボタンにイベントリスナーを追加
          tag.querySelector('.delete-tag').addEventListener('click', function() {
            const currentMembers = JSON.parse(teamMembersHidden.value);
            const idx = parseInt(this.getAttribute('data-index'));
            currentMembers.splice(idx, 1);
            teamMembersHidden.value = JSON.stringify(currentMembers);
            this.parentElement.remove();
            // 他のタグのデータインデックスを更新
            updateDataIndexes('teamMemberTags');
          });
        });
      }
    }
}



  // すべてのフォームでエンターキーが押されたときの処理
document.addEventListener('DOMContentLoaded', function() {
    // プロジェクト追加・編集フォーム内のテキスト入力でエンターキーを無効化
    const forms = ['addProjectForm', 'editProjectForm', 'addSubProjectForm'];
    
    forms.forEach(formId => {
      const form = document.getElementById(formId);
      if (form) {
        // フォーム内のすべてのinputにイベントリスナーを追加
        const inputs = form.querySelectorAll('input[type="text"]');
        inputs.forEach(input => {
          // チームメンバー入力以外のテキスト入力でエンターキーを無効化
          if (input.id !== 'teamMemberInput' && input.id !== 'editTeamMemberInput') {
            input.addEventListener('keydown', function(e) {
              if (e.key === 'Enter') {
                e.preventDefault();
                return false;
              }
            });
          }
        });
      }
    });
  });

  // モーダルを閉じる共通関数
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
    }
  }
  
  // 各モーダルを閉じる関数
  function closeAddProjectModal() {
    closeModal('addProjectModal');
  }
  
  function closeSubProjectModal() {
    closeModal('subProjectModal');
  }
  
  function closeProgressModal() {
    closeModal('progressModal');
  }
  
  function closeStatusModal() {
    closeModal('statusModal');
  }
  
  function closeHistoryModal() {
    closeModal('historyModal');
  }
  
  function closeEditHistoryModal() {
    closeModal('editHistoryModal');
  }
  
  function closeEditProjectModal() {
    closeModal('editProjectModal');
  }
  
  // DOMContentLoaded内でキャンセルボタンのイベントリスナーを追加
  document.addEventListener('DOMContentLoaded', function() {
    // 各モーダルのキャンセルボタン設定
    const modalCancelConfig = [
      { modalId: 'addProjectModal', buttonSelector: 'button[onclick="closeAddProjectModal()"]' },
      { modalId: 'subProjectModal', buttonSelector: 'button[onclick="closeSubProjectModal()"]' },
      { modalId: 'progressModal', buttonSelector: 'button[onclick="closeProgressModal()"]' },
      { modalId: 'statusModal', buttonSelector: 'button[onclick="closeStatusModal()"]' },
      { modalId: 'historyModal', buttonSelector: 'button[onclick="closeHistoryModal()"]' },
      { modalId: 'editHistoryModal', buttonSelector: 'button[onclick="closeEditHistoryModal()"]' },
      { modalId: 'editProjectModal', buttonSelector: 'button[onclick="closeEditProjectModal()"]' }
    ];
  
    // 各モーダルのキャンセルボタンにイベントリスナーを追加
    modalCancelConfig.forEach(config => {
      const button = document.querySelector(config.buttonSelector);
      if (button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          closeModal(config.modalId);
        });
      }
    });
  });


  function initSidebar() {
    console.log('サイドバー初期化開始');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarPin = document.querySelector('.sidebar-pin');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const projectNav = document.querySelector('.project-nav');
    
    // 要素チェック
    if (!sidebarToggle || !sidebar || !sidebarPin || !sidebarOverlay || !projectNav) {
        console.error('サイドバー要素が見つかりません');
        return;
    }
    
    // ピン留め状態を取得
    const isPinned = localStorage.getItem('sidebarPinned') === 'true';
    
    // ピン留め状態を復元
    if (isPinned) {
        sidebar.classList.add('pinned');
        sidebar.classList.add('active');
        sidebarPin.classList.add('active');
    }
    
    // サイドバーを開く
    sidebarToggle.addEventListener('click', function() {
        if (sidebar.classList.contains('pinned')) return;
        
        sidebar.classList.add('active');
        sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // 背景スクロール防止
        
        // プロジェクトリストを生成（初回のみ）
        if (projectNav.children.length === 0) {
            generateProjectList();
        }
    });
    
    // サイドバーを閉じる
    function closeSidebar() {
        if (sidebar.classList.contains('pinned')) return;
        
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = ''; // 背景スクロールを戻す
    }
    
    // ピン留め切り替え
    sidebarPin.addEventListener('click', function() {
        sidebar.classList.toggle('pinned');
        this.classList.toggle('active');
        
        if (sidebar.classList.contains('pinned')) {
            // ピン留め状態を保存
            localStorage.setItem('sidebarPinned', 'true');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = ''; // 背景スクロールを戻す
        } else {
            // ピン留め解除
            localStorage.setItem('sidebarPinned', 'false');
            // サイドバーを閉じる
            sidebar.classList.remove('active');
        }
    });
    
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    // 初回表示時にプロジェクトリストを生成（ピン留め時）
    if (isPinned && projectNav.children.length === 0) {
        generateProjectList();
    }
    
    // プロジェクトリストを生成する関数
    function generateProjectList() {
        const parentProjects = document.querySelectorAll('.parent-project');
        
        parentProjects.forEach(project => {
            const projectTitle = project.querySelector('.project-name');
            if (!projectTitle) return;
            
            // プロジェクト名を取得（チームメンバーの表示を除いた純粋なテキスト）
            const projectName = projectTitle.childNodes[0].nodeValue.trim() || projectTitle.textContent.trim();
            
            // プロジェクトIDを取得（後でスクロール位置の特定に使用）
            const projectId = project.querySelector('.attachment-icon').getAttribute('data-project-id');
            
            // リストアイテムを作成
            const listItem = document.createElement('li');
            listItem.className = 'project-nav-item';
            listItem.textContent = projectName;
            listItem.setAttribute('data-project-id', projectId);
            
            // クリックイベントを設定
            listItem.addEventListener('click', function() {
                // プロジェクトへスクロール
                const targetProject = document.querySelector(`.project-card .attachment-icon[data-project-id="${projectId}"]`).closest('.project-card');
                
                // ヘッダー高さを取得（ヘッダーの高さが変わっても対応できるよう）
                const headerHeight = document.querySelector('.header').offsetHeight;
                
                // スムーズにスクロール - ヘッダー分のオフセットを設定
                window.scrollTo({
                    top: targetProject.offsetTop - headerHeight - 20, // ヘッダー高さ + 余白20px分
                    behavior: 'smooth'
                });
                
                // サイドバーを閉じる（ピン留めされていない場合）
                if (!sidebar.classList.contains('pinned')) {
                    closeSidebar();
                }
                
                // プロジェクトリストのアクティブ状態を更新
                document.querySelectorAll('.project-nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                listItem.classList.add('active');
                
                // ハイライト効果を追加
                targetProject.classList.add('highlight-project');
                setTimeout(() => {
                    targetProject.classList.remove('highlight-project');
                }, 2000);
            });
            
            projectNav.appendChild(listItem);
        });
    }
}


// 既存のDOMContentLoadedイベントリスナーに追加
document.addEventListener('DOMContentLoaded', function() {
    // 既に存在するDOMContentLoadedイベントの末尾に以下を追加
    // サイドバー初期化
    setTimeout(initSidebar, 500);
});