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
}

function closeSubProjectModal() {
    document.getElementById('subProjectModal').style.display = 'none';
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