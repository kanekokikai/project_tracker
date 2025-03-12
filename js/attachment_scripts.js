// 添付ファイル機能のJavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('添付ファイル機能の初期化を開始');
    
    // モーダル要素
    const attachmentModal = document.getElementById('attachment-modal');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const filePreviewModal = document.getElementById('file-preview-modal');
    
    if (!attachmentModal) {
        console.error('添付ファイルモーダルが見つかりません');
        return;
    }
    
    // プロジェクト名の前のクリップアイコンにイベントリスナーを追加する
    function addAttachmentIconListeners() {
        console.log('クリップアイコンへのイベントリスナー追加を開始');
        
        const attachmentIcons = document.querySelectorAll('.attachment-icon');
        console.log(`${attachmentIcons.length}個のクリップアイコンが見つかりました`);
        
        attachmentIcons.forEach(function(icon) {
            const projectId = icon.getAttribute('data-project-id');
            console.log(`プロジェクトID: ${projectId} のアイコンにリスナーを設定`);
            
            // すでにリスナーが設定されているか確認（バグ防止）
            icon.removeEventListener('click', iconClickHandler);
            
            // クリックイベントリスナーを追加
            icon.addEventListener('click', iconClickHandler);
            
            // 添付ファイルの有無を確認してアイコンの色を変更
            checkAttachments(projectId, icon);
        });
    }
    
    // アイコンクリック時のイベントハンドラ
    function iconClickHandler(e) {
        e.stopPropagation(); // 親要素のクリックイベントを阻止
        const projectId = this.getAttribute('data-project-id');
        console.log(`プロジェクトID: ${projectId} のアイコンがクリックされました`);
        openAttachmentModal(projectId);
    }
    
    // 添付ファイルの有無を確認
    function checkAttachments(projectId, iconSpan) {
        fetch(`api/get_attachments.php?project_id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data.length > 0) {
                    iconSpan.classList.add('has-attachments');
                    console.log(`プロジェクトID: ${projectId} には添付ファイルがあります`);
                } else {
                    iconSpan.classList.remove('has-attachments');
                    console.log(`プロジェクトID: ${projectId} には添付ファイルがありません`);
                }
            })
            .catch(error => {
                console.error('添付ファイル情報の取得に失敗しました:', error);
            });
    }
    
    // 添付ファイルモーダルを開く
    function openAttachmentModal(projectId) {
        console.log(`プロジェクトID: ${projectId} の添付ファイルモーダルを開きます`);
        
        // プロジェクトIDをフォームに設定
        document.getElementById('project-id-input').value = projectId;
        
        // モーダルのタイトルを更新（プロジェクト名は表示しない）
        document.querySelector('#attachment-modal .modal-header h2').textContent = `添付ファイル`;
        
        // ファイル一覧を取得して表示
        loadAttachments(projectId);
        
        // モーダルを表示
        attachmentModal.style.display = 'block';
    }
    
    // 添付ファイル一覧を読み込む
    function loadAttachments(projectId) {
        const attachmentsList = document.getElementById('attachments-list');
        const attachmentsTable = document.getElementById('attachments-table');
        const noAttachmentsMsg = document.querySelector('.no-attachments');
        
        if (!attachmentsList || !attachmentsTable || !noAttachmentsMsg) {
            console.error('添付ファイル一覧の要素が見つかりません');
            return;
        }
        
        // リセット
        attachmentsList.innerHTML = '';
        
        fetch(`api/get_attachments.php?project_id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                console.log('添付ファイル一覧の取得結果:', data);
                
                if (data.status === 'success') {
                    if (data.data.length > 0) {
                        // 添付ファイルがある場合
                        attachmentsTable.style.display = 'table';
                        noAttachmentsMsg.style.display = 'none';
                        
                        data.data.forEach(file => {
                            const row = document.createElement('tr');
                            
                            // ファイルタイプに応じたアイコンを選択
                            let fileIcon = 'fa-file';
                            if (file.file_type.includes('image')) {
                                fileIcon = 'fa-file-image';
                            } else if (file.file_type.includes('pdf')) {
                                fileIcon = 'fa-file-pdf';
                            } else if (file.file_type.includes('word') || file.file_type.includes('document')) {
                                fileIcon = 'fa-file-word';
                            } else if (file.file_type.includes('excel') || file.file_type.includes('spreadsheet')) {
                                fileIcon = 'fa-file-excel';
                            } else if (file.file_type.includes('zip') || file.file_type.includes('archive')) {
                                fileIcon = 'fa-file-archive';
                            } else if (file.file_type.includes('text')) {
                                fileIcon = 'fa-file-alt';
                            }
                            
                            row.innerHTML = `
                                <td>
                                    <div class="file-name" data-id="${file.id}" data-filename="${file.original_file_name}" data-filetype="${file.file_type}">
                                        <i class="fas ${fileIcon} file-icon"></i>
                                        ${file.original_file_name}
                                    </div>
                                </td>
                                <td>${file.file_size_formatted}</td>
                                <td>${formatDate(file.upload_date)}</td>
                                <td class="file-actions">
                                    <button class="download-btn" data-id="${file.id}" title="ダウンロード">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="delete-btn" data-id="${file.id}" data-name="${file.original_file_name}" title="削除">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            `;
                            
                            attachmentsList.appendChild(row);
                        });
                        
// ファイル名のダブルクリックイベントリスナーを追加
attachmentsList.querySelectorAll('.file-name').forEach(fileName => {
    fileName.addEventListener('dblclick', function() {
        const attachmentId = this.getAttribute('data-id');
        // 別タブでファイルを表示
        window.open(`api/download_attachment.php?attachment_id=${attachmentId}&view=1`, '_blank');
    });
});

                        // ダウンロードボタンのイベントリスナーを追加
                        attachmentsList.querySelectorAll('.download-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const attachmentId = this.getAttribute('data-id');
                                downloadAttachment(attachmentId);
                            });
                        });
                        
                        // 削除ボタンのイベントリスナーを追加
                        attachmentsList.querySelectorAll('.delete-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const attachmentId = this.getAttribute('data-id');
                                const fileName = this.getAttribute('data-name');
                                openDeleteConfirmModal(attachmentId, fileName);
                            });
                        });
                        
                    } else {
                        // 添付ファイルがない場合
                        attachmentsTable.style.display = 'none';
                        noAttachmentsMsg.style.display = 'block';
                    }
                } else {
                    // エラー処理
                    console.error('添付ファイル一覧の取得に失敗しました:', data.message);
                    attachmentsTable.style.display = 'none';
                    noAttachmentsMsg.textContent = 'エラー：添付ファイルを取得できませんでした';
                    noAttachmentsMsg.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('添付ファイル一覧の取得に失敗しました:', error);
                attachmentsTable.style.display = 'none';
                noAttachmentsMsg.textContent = 'エラー：添付ファイルを取得できませんでした';
                noAttachmentsMsg.style.display = 'block';
            });
    }
    
    // ファイルプレビュー
    function previewFile(attachmentId, fileName, fileType) {
        const previewTitle = document.getElementById('preview-title');
        const previewContainer = document.getElementById('file-preview-container');
        const previewDownload = document.getElementById('preview-download');
        
        previewTitle.textContent = fileName;
        previewContainer.innerHTML = '<p>読み込み中...</p>';
        
        // ダウンロードリンクを設定
        previewDownload.href = `api/download_attachment.php?attachment_id=${attachmentId}`;
        
        // ファイルタイプに応じたプレビュー
        if (fileType.includes('image')) {
            // 画像の場合は直接表示
            const img = document.createElement('img');
            img.onload = function() {
                previewContainer.innerHTML = '';
                previewContainer.appendChild(img);
            };
            img.onerror = function() {
                previewContainer.innerHTML = '<p class="file-error">画像の読み込みに失敗しました</p>';
            };
            img.src = `api/download_attachment.php?attachment_id=${attachmentId}`;
        } else if (fileType.includes('text') || fileType.includes('javascript') || fileType.includes('json') || fileType.includes('xml') || fileType.includes('html') || fileType.includes('css')) {
            // テキストファイルの場合はテキスト表示
            fetch(`api/download_attachment.php?attachment_id=${attachmentId}`)
                .then(response => response.text())
                .then(text => {
                    previewContainer.innerHTML = `<div class="text-preview">${escapeHtml(text)}</div>`;
                })
                .catch(error => {
                    previewContainer.innerHTML = '<p class="file-error">ファイルの読み込みに失敗しました</p>';
                });
        } else {
            // その他のファイルはプレビュー不可
            previewContainer.innerHTML = '<p>このファイル形式はプレビューできません。ダウンロードしてご確認ください。</p>';
        }
        
        // モーダルを表示
        filePreviewModal.style.display = 'block';
    }
    
    // HTMLエスケープ
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // ファイルダウンロード
    function downloadAttachment(attachmentId) {
        window.location.href = `api/download_attachment.php?attachment_id=${attachmentId}`;
    }
    
    // 削除確認モーダルを開く
    function openDeleteConfirmModal(attachmentId, fileName) {
        document.getElementById('delete-file-name').textContent = fileName;
        
        // 削除確認ボタンのイベントリスナーをリセット
        const confirmDeleteBtn = document.getElementById('confirm-delete');
        const newConfirmBtn = confirmDeleteBtn.cloneNode(true);
        confirmDeleteBtn.parentNode.replaceChild(newConfirmBtn, confirmDeleteBtn);
        
        // 新しいイベントリスナーを追加
        newConfirmBtn.addEventListener('click', function() {
            deleteAttachment(attachmentId);
        });
        
        // モーダルを表示
        deleteConfirmModal.style.display = 'block';
    }
    
    // 添付ファイル削除
    function deleteAttachment(attachmentId) {
        const formData = new FormData();
        formData.append('attachment_id', attachmentId);
        
        fetch('api/delete_attachment.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 削除成功
                    deleteConfirmModal.style.display = 'none';
                    
                    // ファイル一覧を再読み込み
                    const projectId = document.getElementById('project-id-input').value;
                    loadAttachments(projectId);
                    
                    // 添付ファイルアイコンの状態を更新
                    const iconSpan = document.querySelector(`.attachment-icon[data-project-id="${projectId}"]`);
                    if (iconSpan) {
                        checkAttachments(projectId, iconSpan);
                    }
                } else {
                    // エラー処理
                    alert('削除に失敗しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('添付ファイルの削除に失敗しました:', error);
                alert('削除に失敗しました');
            });
    }
    
    // ドラッグ＆ドロップ機能の設定
    function setupDragAndDrop() {
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
        const selectedFileName = document.getElementById('selected-file-name');
        const uploadBtn = document.getElementById('upload-btn');
        
        if (!dropArea) {
            console.error('ドロップエリアが見つかりません');
            return;
        }
        
        // ドラッグオーバーイベント
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // ハイライト表示
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
        }
        
        // ドロップ処理
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            
            if (file) {
                // ファイル入力にファイルを設定
                fileInput.files = dt.files;
                
                // ファイル名を表示
                selectedFileName.textContent = file.name;
                
                // アップロードボタンを有効化
                uploadBtn.disabled = false;
            }
        }
    }
    
    // ファイルアップロード処理
    function setupFileUpload() {
        const fileInput = document.getElementById('file-input');
        const selectedFileName = document.getElementById('selected-file-name');
        const uploadBtn = document.getElementById('upload-btn');
        const uploadForm = document.getElementById('file-upload-form');
        const progressBar = document.getElementById('upload-progress');
        const progressBarInner = progressBar.querySelector('.progress-bar');
        const uploadMessage = document.getElementById('upload-message');
        
        if (!fileInput || !selectedFileName || !uploadBtn || !uploadForm || !progressBar || !progressBarInner || !uploadMessage) {
            console.error('ファイルアップロード用の要素が見つかりません');
            return;
        }
        
        // ファイル選択時の処理
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                selectedFileName.textContent = this.files[0].name;
                uploadBtn.disabled = false;
            } else {
                selectedFileName.textContent = '選択されていません';
                uploadBtn.disabled = true;
            }
        });
        
        // フォーム送信時の処理
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const projectId = document.getElementById('project-id-input').value;
            
            // ボタンを無効化
            uploadBtn.disabled = true;
            
            // プログレスバーの表示
            progressBar.style.display = 'block';
            progressBarInner.style.width = '0%';
            uploadMessage.innerHTML = '';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBarInner.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('アップロード結果:', response);
                        
                        if (response.status === 'success') {
                            // アップロード成功
                            uploadMessage.innerHTML = '<span class="upload-success">ファイルがアップロードされました</span>';
                            
                            // フォームをリセット
                            uploadForm.reset();
                            selectedFileName.textContent = '選択されていません';
                            
                            // ファイル一覧を再読み込み
                            loadAttachments(projectId);
                            
                            // 添付ファイルアイコンの状態を更新
                            const iconSpan = document.querySelector(`.attachment-icon[data-project-id="${projectId}"]`);
                            if (iconSpan) {
                                checkAttachments(projectId, iconSpan);
                            }
                        } else {
                            // エラー処理
                            uploadMessage.innerHTML = `<span class="upload-error">エラー: ${response.message}</span>`;
                        }
                    } catch (e) {
                        uploadMessage.innerHTML = '<span class="upload-error">レスポンスの解析に失敗しました</span>';
                    }
                } else {
                    uploadMessage.innerHTML = '<span class="upload-error">サーバーエラーが発生しました</span>';
                }
                
                // 完了後にプログレスバーを非表示に（少し待ってから）
                setTimeout(() => {
                    progressBar.style.display = 'none';
                }, 1000);
                
                // ボタンを再度有効化
                uploadBtn.disabled = false;
            });
            
            xhr.addEventListener('error', function() {
                uploadMessage.innerHTML = '<span class="upload-error">アップロード中にエラーが発生しました</span>';
                progressBar.style.display = 'none';
                uploadBtn.disabled = false;
            });
            
            xhr.open('POST', 'api/upload_attachment.php', true);
            xhr.send(formData);
        });
    }
    
    // モーダルを閉じるイベント
    function setupModalClose() {
        // 閉じるボタンでモーダルを閉じる
        document.querySelectorAll('.modal .close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });
        
        // モーダル外のクリックでモーダルを閉じる
        window.addEventListener('click', function(e) {
            if (e.target === attachmentModal) {
                attachmentModal.style.display = 'none';
            } else if (e.target === deleteConfirmModal) {
                deleteConfirmModal.style.display = 'none';
            } else if (e.target === filePreviewModal) {
                filePreviewModal.style.display = 'none';
            }
        });
        
        // 削除キャンセルボタン
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', function() {
                deleteConfirmModal.style.display = 'none';
            });
        }
    }
    
    // 日付フォーマット
    function formatDate(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}/${month}/${day} ${hours}:${minutes}`;
    }
    
    // 初期化処理
    function init() {
        console.log('添付ファイル機能の初期化中...');
        addAttachmentIconListeners();
        setupDragAndDrop();
        setupFileUpload();
        setupModalClose();
        console.log('添付ファイル機能の初期化完了');
    }
    
    // ページロード時に初期化
    init();
    
    // Ajaxでページ内容が更新された場合に再初期化するための公開メソッド
    window.reinitAttachments = init;
});