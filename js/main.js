// モーダル関連の関数
function openAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'block';
}

function closeAddProjectModal() {
    document.getElementById('addProjectModal').style.display = 'none';
}

function openProgressModal(projectId) {
    document.getElementById('progressProjectId').value = projectId;
    document.getElementById('progressModal').style.display = 'block';
}

function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
}

function openStatusModal(projectId) {
    document.getElementById('statusProjectId').value = projectId;
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// プロジェクト追加
document.getElementById('addProjectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/add_project.php', {
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

// 進捗追加
document.getElementById('addProgressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/add_progress.php', {
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

// ステータス変更
document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/change_status.php', {
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

// プロジェクト削除
function confirmDelete(projectId) {
    if (confirm('このプロジェクトを削除してもよろしいですか？')) {
        fetch('api/delete_project.php', {
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

// 履歴一覧モーダル関連
function openHistoryModal(projectId) {
    document.getElementById('historyModal').style.display = 'block';
    
    fetch('api/get_history.php?project_id=' + projectId)
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

// モーダルの外側をクリックした時に閉じる
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
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

// 子プロジェクト追加のフォーム送信処理
document.getElementById('addSubProjectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/add_sub_project.php', {
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

// 履歴の折りたたみ機能
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script loaded - Setting up fold/unfold");
    
    // クリックイベントをページ全体に設定し、動的に追加される要素にも対応
    document.body.addEventListener('click', function(e) {
        // toggle-historyクラスを持つ要素がクリックされたかチェック
        if (e.target.classList.contains('toggle-history') || e.target.closest('.toggle-history')) {
            const toggleElement = e.target.classList.contains('toggle-history') ? 
                                e.target : e.target.closest('.toggle-history');
            const projectId = toggleElement.getAttribute('data-project-id');
            
            console.log("Toggle clicked for project:", projectId);
            
            if (projectId) {
                const historyContent = document.getElementById(`history-content-${projectId}`);
                if (historyContent) {
                    // ログを追加
                    console.log("History content element:", historyContent);
                    
                    historyContent.classList.toggle('collapsed');
                    
                    // 直接スタイルを適用
                    if (historyContent.classList.contains('collapsed')) {
                        historyContent.style.display = 'none';
                        toggleElement.textContent = '▶';
                    } else {
                        historyContent.style.display = 'block';
                        toggleElement.textContent = '▼';
                    }
                    
                    console.log("Applied direct style:", historyContent.style.display);
                    console.log("Toggle state changed:", 
                        historyContent.classList.contains('collapsed') ? "collapsed" : "expanded");
                }
            }
            
            // イベントの伝播を停止
            e.stopPropagation();
        }
    });
    
    // 初期状態を設定する関数
    function initializeHistoryState() {
        console.log("Initializing history states");
        const childProjects = document.querySelectorAll('.child-project');
        
        childProjects.forEach(project => {
            const status = project.getAttribute('data-status');
            const toggle = project.querySelector('.toggle-history');
            
            if (toggle) {
                const projectId = toggle.getAttribute('data-project-id');
                const historyContent = document.getElementById(`history-content-${projectId}`);
                
                if (historyContent) {
                    if (status === '完了') {
                        historyContent.style.display = 'none';
                        historyContent.classList.add('collapsed');
                        toggle.textContent = '▶';
                        console.log("Set collapsed for completed project:", projectId);
                    } else {
                        historyContent.style.display = 'block';
                        historyContent.classList.remove('collapsed');
                        toggle.textContent = '▼';
                        console.log("Set expanded for in-progress project:", projectId);
                    }
                }
            }
        });
    }
    
    // 少し遅延させて初期化（DOMの読み込み完了を確実にするため）
    setTimeout(initializeHistoryState, 300);
});