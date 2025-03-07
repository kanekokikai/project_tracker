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

// ページ読み込み完了時に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded - Initializing toggle functionality");
    
    // 全てのトグルボタンに明示的にクリックイベントを追加
    const toggleButtons = document.querySelectorAll('.toggle-history');
    console.log(`Found ${toggleButtons.length} toggle buttons`);
    
    toggleButtons.forEach(button => {
        const projectId = button.getAttribute('data-project-id');
        console.log(`Setting up toggle for project ${projectId}`);
        
        // 既存のイベントをクリア
        button.removeEventListener('click', function(){});
        
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
});