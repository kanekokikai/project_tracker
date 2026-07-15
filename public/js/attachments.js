document.addEventListener('DOMContentLoaded', () => {
    const attachmentModal = document.getElementById('attachment-modal');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const attachmentsCache = {};
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    if (!attachmentModal) {
        return;
    }

    setupDragAndDrop();
    setupFileUpload();
    setupModalClose();

    document.addEventListener('click', (event) => {
        const actionTarget = event.target.closest('[data-action="open-attachments"]');

        if (!actionTarget) {
            return;
        }

        event.stopPropagation();
        openAttachmentModal(actionTarget.dataset.projectId);
    });

    function updateAttachmentIcon(projectId, hasAttachments) {
        const iconSpan = document.querySelector(`.attachment-icon[data-project-id="${projectId}"]`);

        if (!iconSpan) {
            return;
        }

        if (hasAttachments) {
            iconSpan.classList.add('has-attachments');
        } else {
            iconSpan.classList.remove('has-attachments');
        }
    }

    function openAttachmentModal(projectId) {
        document.getElementById('project-id-input').value = projectId;
        loadAttachments(projectId);
        openAttachmentModalDisplay();
    }

    function openAttachmentModalDisplay() {
        attachmentModal.style.display = 'flex';
        attachmentModal.classList.add('is-open');
    }

    function closeAttachmentModal(modal) {
        modal.style.display = 'none';
        modal.classList.remove('is-open');
    }

    async function loadAttachments(projectId) {
        const attachmentsList = document.getElementById('attachments-list');
        const attachmentsTable = document.getElementById('attachments-table');
        const noAttachmentsMsg = document.querySelector('.no-attachments');

        attachmentsList.innerHTML = '';

        const now = Date.now();
        if (attachmentsCache[projectId] && now - attachmentsCache[projectId].timestamp < 3000) {
            displayAttachments(attachmentsCache[projectId].data, projectId);
            return;
        }

        noAttachmentsMsg.textContent = '読み込み中...';
        noAttachmentsMsg.style.display = 'block';
        attachmentsTable.style.display = 'none';

        try {
            const data = await apiRequest(`/projects/${projectId}/attachments`);

            attachmentsCache[projectId] = {
                data,
                timestamp: now,
            };

            displayAttachments(data, projectId);
        } catch (error) {
            attachmentsTable.style.display = 'none';
            noAttachmentsMsg.textContent = 'エラー：添付ファイルを取得できませんでした';
            noAttachmentsMsg.style.display = 'block';
        }
    }

    function displayAttachments(data, projectId) {
        const attachmentsList = document.getElementById('attachments-list');
        const attachmentsTable = document.getElementById('attachments-table');
        const noAttachmentsMsg = document.querySelector('.no-attachments');

        if (data.status !== 'success') {
            attachmentsTable.style.display = 'none';
            noAttachmentsMsg.textContent = 'エラー：添付ファイルを取得できませんでした';
            noAttachmentsMsg.style.display = 'block';
            return;
        }

        if (data.data.length === 0) {
            attachmentsTable.style.display = 'none';
            noAttachmentsMsg.textContent = '添付ファイルはありません';
            noAttachmentsMsg.style.display = 'block';
            return;
        }

        attachmentsTable.style.display = 'table';
        noAttachmentsMsg.style.display = 'none';

        data.data.forEach((file) => {
            const row = document.createElement('tr');
            const fileIcon = getFileIcon(file.file_type);

            row.innerHTML = `
                <td>
                    <div class="file-name" data-id="${file.id}" data-filename="${escapeHtml(file.original_file_name)}" data-filetype="${escapeHtml(file.file_type)}">
                        <i class="fas ${fileIcon} file-icon"></i>
                        ${escapeHtml(file.original_file_name)}
                    </div>
                </td>
                <td>${escapeHtml(file.file_size_formatted)}</td>
                <td>${formatDate(file.upload_date)}</td>
                <td class="file-actions">
                    <button class="download-btn" data-id="${file.id}" title="ダウンロード" type="button">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="delete-btn" data-id="${file.id}" data-name="${escapeHtml(file.original_file_name)}" title="削除" type="button">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;

            attachmentsList.appendChild(row);
        });

        attachmentsList.querySelectorAll('.file-name').forEach((fileName) => {
            fileName.addEventListener('dblclick', () => {
                const attachmentId = fileName.dataset.id;
                window.open(appUrl(`/attachments/${attachmentId}/download?view=1`), '_blank');
            });
        });

        attachmentsList.querySelectorAll('.download-btn').forEach((button) => {
            button.addEventListener('click', () => {
                downloadAttachment(button.dataset.id);
            });
        });

        attachmentsList.querySelectorAll('.delete-btn').forEach((button) => {
            button.addEventListener('click', () => {
                openDeleteConfirmModal(button.dataset.id, button.dataset.name);
            });
        });
    }

    function getFileIcon(fileType) {
        if (!fileType) {
            return 'fa-file';
        }

        if (fileType.includes('image')) {
            return 'fa-file-image';
        }

        if (fileType.includes('pdf')) {
            return 'fa-file-pdf';
        }

        if (fileType.includes('word') || fileType.includes('document')) {
            return 'fa-file-word';
        }

        if (fileType.includes('excel') || fileType.includes('spreadsheet')) {
            return 'fa-file-excel';
        }

        if (fileType.includes('zip') || fileType.includes('archive')) {
            return 'fa-file-archive';
        }

        if (fileType.includes('text')) {
            return 'fa-file-alt';
        }

        return 'fa-file';
    }

    function downloadAttachment(attachmentId) {
        window.location.href = appUrl(`/attachments/${attachmentId}/download`);
    }

    function openDeleteConfirmModal(attachmentId, fileName) {
        document.getElementById('delete-file-name').textContent = fileName;

        const confirmDeleteBtn = document.getElementById('confirm-delete');
        const newConfirmBtn = confirmDeleteBtn.cloneNode(true);
        confirmDeleteBtn.parentNode.replaceChild(newConfirmBtn, confirmDeleteBtn);

        newConfirmBtn.addEventListener('click', async () => {
            newConfirmBtn.disabled = true;
            newConfirmBtn.textContent = '削除中...';

            try {
                const response = await apiRequest(`/attachments/${attachmentId}`, {
                    method: 'DELETE',
                });

                if (response.status === 'success') {
                    closeAttachmentModal(deleteConfirmModal);

                    const projectId = document.getElementById('project-id-input').value;
                    delete attachmentsCache[projectId];

                    const attachmentRow = document.querySelector(`.delete-btn[data-id="${attachmentId}"]`)?.closest('tr');
                    if (attachmentRow) {
                        attachmentRow.remove();
                    }

                    const attachmentsList = document.getElementById('attachments-list');
                    const attachmentsTable = document.getElementById('attachments-table');
                    const noAttachmentsMsg = document.querySelector('.no-attachments');

                    if (attachmentsList.children.length === 0) {
                        attachmentsTable.style.display = 'none';
                        noAttachmentsMsg.textContent = '添付ファイルはありません';
                        noAttachmentsMsg.style.display = 'block';
                    }

                    updateAttachmentIcon(projectId, attachmentsList.children.length > 0);
                } else {
                    alert('削除に失敗しました: ' + (response.message || '不明なエラー'));
                }
            } catch (error) {
                alert(error.message || '削除に失敗しました');
            } finally {
                newConfirmBtn.disabled = false;
                newConfirmBtn.textContent = '削除';
            }
        });

        deleteConfirmModal.style.display = 'flex';
        deleteConfirmModal.classList.add('is-open');
    }

    function setupDragAndDrop() {
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
        const selectedFileName = document.getElementById('selected-file-name');
        const uploadBtn = document.getElementById('upload-btn');

        if (!dropArea || !fileInput || !selectedFileName || !uploadBtn) {
            return;
        }

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            dropArea.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'));
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'));
        });

        dropArea.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];

            if (!file) {
                return;
            }

            if (file.size > MAX_FILE_SIZE) {
                selectedFileName.textContent = `${file.name} (警告: ファイルサイズが大きすぎます)`;
                uploadBtn.disabled = true;
                return;
            }

            fileInput.files = event.dataTransfer.files;
            selectedFileName.textContent = file.name;
            uploadBtn.disabled = false;
        });
    }

    function setupFileUpload() {
        const fileInput = document.getElementById('file-input');
        const selectedFileName = document.getElementById('selected-file-name');
        const uploadBtn = document.getElementById('upload-btn');
        const uploadForm = document.getElementById('file-upload-form');
        const progressBar = document.getElementById('upload-progress');
        const progressBarInner = progressBar?.querySelector('.progress-bar');
        const uploadMessage = document.getElementById('upload-message');

        if (!fileInput || !selectedFileName || !uploadBtn || !uploadForm || !progressBar || !progressBarInner || !uploadMessage) {
            return;
        }

        fileInput.addEventListener('change', function () {
            if (this.files.length === 0) {
                selectedFileName.textContent = '選択されていません';
                uploadBtn.disabled = true;
                return;
            }

            const file = this.files[0];

            if (file.size > MAX_FILE_SIZE) {
                selectedFileName.textContent = `${file.name} (警告: ファイルサイズが大きすぎます)`;
                uploadBtn.disabled = true;
                uploadMessage.innerHTML = '<span class="upload-error">ファイルサイズは10MB以下である必要があります</span>';
                return;
            }

            selectedFileName.textContent = file.name;
            uploadBtn.disabled = false;
            uploadMessage.innerHTML = '';
        });

        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const projectId = document.getElementById('project-id-input').value;
            const formData = new FormData(uploadForm);

            uploadBtn.disabled = true;
            progressBar.style.display = 'block';
            progressBarInner.style.width = '0%';
            uploadMessage.innerHTML = '';

            try {
                const response = await uploadWithProgress(`/projects/${projectId}/attachments`, formData, (percent) => {
                    progressBarInner.style.width = `${percent}%`;
                });

                if (response.status === 'success') {
                    uploadMessage.innerHTML = '<span class="upload-success">ファイルがアップロードされました</span>';
                    uploadForm.reset();
                    selectedFileName.textContent = '選択されていません';
                    delete attachmentsCache[projectId];
                    loadAttachments(projectId);
                    updateAttachmentIcon(projectId, true);
                } else {
                    uploadMessage.innerHTML = `<span class="upload-error">エラー: ${escapeHtml(response.message || '不明なエラー')}</span>`;
                }
            } catch (error) {
                uploadMessage.innerHTML = `<span class="upload-error">${escapeHtml(error.message || 'アップロード中にエラーが発生しました')}</span>`;
            } finally {
                setTimeout(() => {
                    progressBar.style.display = 'none';
                }, 1000);
                uploadBtn.disabled = false;
            }
        });
    }

    function setupModalClose() {
        document.querySelectorAll('[data-close-attachment-modal]').forEach((closeBtn) => {
            closeBtn.addEventListener('click', () => {
                closeAttachmentModal(closeBtn.closest('.modal'));
            });
        });

        window.addEventListener('click', (event) => {
            if (event.target === attachmentModal) {
                closeAttachmentModal(attachmentModal);
            } else if (event.target === deleteConfirmModal) {
                closeAttachmentModal(deleteConfirmModal);
            }
        });

        const cancelDeleteBtn = document.getElementById('cancel-delete');
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', () => {
                closeAttachmentModal(deleteConfirmModal);
            });
        }
    }

    function uploadWithProgress(url, formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    onProgress((event.loaded / event.total) * 100);
                }
            });

            xhr.addEventListener('load', () => {
                let response = {};

                try {
                    response = JSON.parse(xhr.responseText);
                } catch (error) {
                    reject(new Error('レスポンスの解析に失敗しました'));
                    return;
                }

                if (xhr.status === 401) {
                    if (typeof window.showAuthModal === 'function') {
                        window.showAuthModal(response.message || 'ログインが必要です');
                    }

                    reject(new Error(response.message || '認証が必要です'));
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(response);
                    return;
                }

                reject(new Error(response.message || 'アップロードに失敗しました'));
            });

            xhr.addEventListener('error', () => {
                reject(new Error('アップロード中にエラーが発生しました'));
            });

            xhr.open('POST', appUrl(url));
            xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${year}/${month}/${day} ${hours}:${minutes}`;
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
