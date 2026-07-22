(function () {
    const STORAGE_KEY = 'projectTrackerLastAuthor';

    const panel = document.getElementById('activityLogPanel');
    const overlay = document.getElementById('activityLogOverlay');
    const toggle = document.getElementById('activityLogToggle');
    const closeBtn = document.getElementById('activityLogClose');
    const content = document.getElementById('activityLogContent');

    if (!panel || !toggle || !content) {
        return;
    }

    const EVENT_META = {
        project_created: { icon: 'fa-folder-plus', label: '作成' },
        subproject_created: { icon: 'fa-sitemap', label: '作成' },
        project_deleted: { icon: 'fa-trash-alt', label: '削除' },
        project_renamed: { icon: 'fa-i-cursor', label: 'タイトル' },
        members_changed: { icon: 'fa-users', label: 'メンバー' },
        department_changed: { icon: 'fa-building', label: '部署' },
        status_changed: { icon: 'fa-exchange-alt', label: 'ステータス' },
        attachment_added: { icon: 'fa-paperclip', label: '添付' },
        attachment_removed: { icon: 'fa-unlink', label: '添付削除' },
        comment_added: { icon: 'fa-comment', label: 'コメント' },
        comment_edited: { icon: 'fa-comment-dots', label: '編集' },
        comment_deleted: { icon: 'fa-comment-slash', label: '削除' },
    };

    function getLastAuthor() {
        try {
            return (localStorage.getItem(STORAGE_KEY) || '').trim();
        } catch (error) {
            return '';
        }
    }

    function setLastAuthor(name) {
        const value = String(name || '').trim();

        if (!value) {
            return;
        }

        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openPanel() {
        panel.classList.add('active');
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        overlay?.classList.add('active');
        loadLogs();
    }

    function closePanel() {
        panel.classList.remove('active');
        panel.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        overlay?.classList.remove('active');
    }

    function togglePanel() {
        if (panel.classList.contains('active')) {
            closePanel();
            return;
        }

        openPanel();
    }

    async function loadLogs() {
        content.innerHTML = '<p class="activity-log-empty">読み込み中...</p>';

        try {
            const response = await fetch(appUrl('/activity-logs'), {
                credentials: 'same-origin',
                headers: typeof window.csrfHeaders === 'function'
                    ? window.csrfHeaders()
                    : { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.status === 401) {
                content.innerHTML = '<p class="activity-log-empty">ログイン後に表示できます</p>';
                if (typeof window.showAuthModal === 'function') {
                    window.showAuthModal('ログインが必要です');
                }
                return;
            }

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                content.innerHTML = `<p class="activity-log-empty">${escapeHtml(data.message || '読み込みに失敗しました')}</p>`;
                return;
            }

            renderLogs(data.logs || []);
        } catch (error) {
            content.innerHTML = `<p class="activity-log-empty">${escapeHtml(error.message || '読み込みに失敗しました')}</p>`;
        }
    }

    function renderLogs(logs) {
        if (!logs.length) {
            content.innerHTML = '<p class="activity-log-empty">まだログはありません</p>';
            return;
        }

        const list = document.createElement('ul');
        list.className = 'activity-log-list';

        logs.forEach((log) => {
            const meta = EVENT_META[log.event_type] || { icon: 'fa-circle', label: '操作' };
            const item = document.createElement('li');
            item.className = `activity-log-item type-${escapeHtml(log.event_type)}`;

            const projectHtml = log.project_name
                ? `<div class="activity-log-project">${escapeHtml(log.project_name)}</div>`
                : '';

            item.innerHTML = `
                <div class="activity-log-icon" title="${escapeHtml(meta.label)}">
                    <i class="fas ${meta.icon}" aria-hidden="true"></i>
                </div>
                <div class="activity-log-body">
                    <div class="activity-log-top">
                        <span class="activity-log-author">${escapeHtml(log.author)}</span>
                        <span class="activity-log-time">${escapeHtml(log.created_at_label || '')}</span>
                    </div>
                    <div class="activity-log-message">${escapeHtml(log.message)}</div>
                    ${projectHtml}
                </div>
            `;

            if (log.project_id) {
                item.classList.add('is-clickable');
                item.addEventListener('click', () => {
                    const target = document.querySelector(`.project-card[data-project-id="${log.project_id}"], .child-project[data-project-id="${log.project_id}"]`);

                    if (target) {
                        closePanel();
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('activity-log-highlight');
                        setTimeout(() => target.classList.remove('activity-log-highlight'), 1600);
                    }
                });
            }

            list.appendChild(item);
        });

        content.innerHTML = '';
        content.appendChild(list);
    }

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        togglePanel();
    });

    closeBtn?.addEventListener('click', closePanel);
    overlay?.addEventListener('click', closePanel);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panel.classList.contains('active')) {
            closePanel();
        }
    });

    window.getLastAuthor = getLastAuthor;
    window.setLastAuthor = setLastAuthor;
})();
