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


// 履歴の折りたたみ機能（簡素化版）
function toggleHistory(projectId) {
    const historyContent = document.getElementById(`history-content-${projectId}`);
    const toggleButton = document.querySelector(`.toggle-history[data-project-id="${projectId}"]`);
    
    if (historyContent) {
        if (historyContent.classList.contains('collapsed')) {
            historyContent.classList.remove('collapsed');
            toggleButton.textContent = '▼';
            historyContent.style.display = 'block';
        } else {
            historyContent.classList.add('collapsed');
            toggleButton.textContent = '▶';
            historyContent.style.display = 'none';
        }
    }
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
                    <div class="history-item">
                        <div class="history-header">
                            <span class="author">${hist.author}</span>
                            <span class="date">${new Date(hist.created_at).toLocaleString('ja-JP')}</span>
                        </div>
                        ${hist.status ? 
                            `<div class="status-change">ステータスを「${hist.status}」に変更</div>` : 
                            ''
                        }
                        ${hist.content ? 
                            `<div class="content">${hist.content}</div>` : 
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

// ページ読み込み完了時に実行
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
            'historyModal'
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
    
    // プロジェクト追加フォーム
    const addProjectForm = document.getElementById('addProjectForm');
    if (addProjectForm) {
        addProjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
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
    
    // トグルボタンのイベント設定
    const toggleButtons = document.querySelectorAll('.toggle-history');
    console.log(`Found ${toggleButtons.length} toggle buttons`);
    
    toggleButtons.forEach(button => {
        const projectId = button.getAttribute('data-project-id');
        console.log(`Setting up toggle for project ${projectId}`);
        
        // 新しいイベントを追加
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleHistory(projectId);
        });
    });
    
    // 初期状態の設定（完了プロジェクトの履歴を折りたたむ）
    const completedProjects = document.querySelectorAll('.child-project[data-status="完了"]');
    console.log(`Found ${completedProjects.length} completed projects to collapse`);
    
    completedProjects.forEach(project => {
        const toggleButton = project.querySelector('.toggle-history');
        if (toggleButton) {
            const projectId = toggleButton.getAttribute('data-project-id');
            const historyContent = document.getElementById(`history-content-${projectId}`);
            
            if (historyContent) {
                console.log(`Collapsing history for completed project ${projectId}`);
                historyContent.classList.add('collapsed');
                historyContent.style.display = 'none';
                toggleButton.textContent = '▶';
            }
        }
    });
    
    // モーダルの外側をクリックした時に閉じる
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };
});