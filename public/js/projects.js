document.addEventListener('DOMContentLoaded', () => {
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const memberSearch = document.getElementById('memberSearch');
    const clearSearch = document.getElementById('clearSearch');
    const searchModeToggle = document.getElementById('searchModeToggle');
    const addProgressForm = document.getElementById('addProgressForm');
    const editHistoryForm = document.getElementById('editHistoryForm');
    const addProjectForm = document.getElementById('addProjectForm');
    const addSubProjectForm = document.getElementById('addSubProjectForm');
    const editProjectForm = document.getElementById('editProjectForm');
    const projectAuthor = document.getElementById('projectAuthor');
    const subProjectAuthor = document.getElementById('subProjectAuthor');

    setupTeamMemberInput('teamMemberInput', 'teamMemberTags', 'teamMembers', 'teamMemberAddBtn');
    setupTeamMemberInput('subTeamMemberInput', 'subTeamMemberTags', 'subTeamMembers', 'subTeamMemberAddBtn');
    setupTeamMemberInput('editTeamMemberInput', 'editTeamMemberTags', 'editTeamMembers', 'editTeamMemberAddBtn');

    if (projectAuthor) {
        projectAuthor.addEventListener('change', addAuthorToTeam);
        projectAuthor.addEventListener('blur', addAuthorToTeam);
    }

    if (subProjectAuthor) {
        subProjectAuthor.addEventListener('change', addSubAuthorToTeam);
        subProjectAuthor.addEventListener('blur', addSubAuthorToTeam);
    }

    if (departmentFilter) {
        departmentFilter.addEventListener('change', (event) => {
            const currentUrl = new URL(window.location.href);
            const status = currentUrl.searchParams.get('status');

            currentUrl.search = '';
            currentUrl.searchParams.set('department', event.target.value);

            if (status) {
                currentUrl.searchParams.set('status', status);
            }

            window.location.href = currentUrl.toString();
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', (event) => {
            filterByStatus(event.target.value);
        });
        filterByStatus(statusFilter.value);
    }

    if (memberSearch && clearSearch && searchModeToggle) {
        searchModeToggle.addEventListener('change', () => {
            memberSearch.placeholder = searchModeToggle.checked
                ? 'プロジェクト名で検索...'
                : '名前で検索...';

            if (memberSearch.value.trim() !== '') {
                performSearch(memberSearch.value.trim());
            }
        });

        memberSearch.addEventListener('input', () => {
            const searchValue = memberSearch.value.trim();
            clearSearch.style.display = searchValue.length > 0 ? 'block' : 'none';
            performSearch(searchValue);
        });

        clearSearch.addEventListener('click', () => {
            memberSearch.value = '';
            clearSearch.style.display = 'none';
            performSearch('');
        });
    }

    document.querySelectorAll('.toggle-history').forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleChildProjectHistory(toggle.dataset.projectId);
        });
    });

    document.querySelectorAll('.child-project > .project-header.has-child-history').forEach((header) => {
        header.addEventListener('click', (event) => {
            if (
                event.target.closest('.project-actions')
                || event.target.closest('[data-action]')
                || event.target.closest('.toggle-history')
            ) {
                return;
            }

            const projectId = header.closest('.child-project')?.dataset.projectId;

            if (!projectId) {
                return;
            }

            toggleChildProjectHistory(projectId);
        });
    });

    document.querySelectorAll('.content-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const target = document.getElementById(toggle.dataset.target);

            if (!target) {
                return;
            }

            target.classList.toggle('expanded');
            toggle.textContent = target.classList.contains('expanded') ? '閉じる' : '続きを読む';
            requestAnimationFrame(drawHierarchyConnectors);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.dataset.closeModal);
        });
    });

    document.addEventListener('click', (event) => {
        const statusDropdown = document.getElementById('status-dropdown');

        if (statusDropdown && statusDropdown.style.display === 'block') {
            const clickedStatusOption = event.target.closest('.status-option');

            if (clickedStatusOption) {
                const projectId = statusDropdown.dataset.projectId;
                const newStatus = clickedStatusOption.dataset.status;

                statusDropdown.style.display = 'none';

                if (projectId && newStatus) {
                    updateProjectStatus(projectId, newStatus);
                }

                return;
            }

            if (!event.target.closest('[data-action="show-status-dropdown"]')) {
                statusDropdown.style.display = 'none';
            }
        }

        const actionTarget = event.target.closest('[data-action]');

        if (!actionTarget) {
            return;
        }

        const action = actionTarget.dataset.action;

        if (action === 'show-status-dropdown') {
            event.stopPropagation();
            showStatusDropdown(actionTarget);
            return;
        }

        if (action === 'open-progress') {
            openProgressModal(actionTarget.dataset.projectId);
        }

        if (action === 'toggle-history-expand') {
            event.preventDefault();
            toggleHistoryExpand(actionTarget);
            return;
        }

        if (action === 'open-add-project') {
            openAddProjectModal();
        }

        if (action === 'open-sub-project') {
            openSubProjectModal(actionTarget.dataset.projectId);
        }

        if (action === 'edit-project') {
            openEditProjectModal(actionTarget.dataset.projectId, actionTarget.dataset.projectName);
        }

        if (action === 'delete-project') {
            confirmDeleteProject(actionTarget.dataset.projectId);
        }

        if (action === 'edit-history') {
            openEditHistoryModal(actionTarget.dataset.historyId);
        }

        if (action === 'delete-history') {
            confirmDeleteHistory(actionTarget.dataset.historyId);
        }
    });

    if (addProgressForm) {
        addProgressForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(addProgressForm);
            rememberAuthorFromForm(formData);

            try {
                const response = await apiRequest('/histories', {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
            } catch (error) {
                alert(error.message || 'エラーが発生しました');
            }
        });
    }

    if (editHistoryForm) {
        editHistoryForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const historyId = document.getElementById('editHistoryId').value;
            const formData = new FormData(editHistoryForm);
            formData.append('_method', 'PUT');
            rememberAuthorFromForm(formData);

            try {
                const response = await apiRequest(`/histories/${historyId}`, {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
            } catch (error) {
                alert(error.message || 'エラーが発生しました');
            }
        });
    }

    if (addProjectForm) {
        addProjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const teamMembersInput = document.getElementById('teamMembers');
            if (teamMembersInput && teamMembersInput.value === '') {
                teamMembersInput.value = '[]';
            }

            try {
                const formData = new FormData(addProjectForm);
                rememberAuthorFromForm(formData);

                const response = await apiRequest('/projects', {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
            } catch (error) {
                alert(error.message || 'エラーが発生しました');
            }
        });
    }

    if (addSubProjectForm) {
        addSubProjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const teamMembersInput = document.getElementById('subTeamMembers');
            if (teamMembersInput && teamMembersInput.value === '') {
                teamMembersInput.value = '[]';
            }

            try {
                const formData = new FormData(addSubProjectForm);
                rememberAuthorFromForm(formData);

                const response = await apiRequest('/projects/sub', {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
            } catch (error) {
                alert(error.message || 'エラーが発生しました');
            }
        });
    }

    if (editProjectForm) {
        editProjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const projectId = document.getElementById('editProjectId').value;
            const projectCard = document.querySelector(`.project-card[data-project-id="${projectId}"]`);
            const isSubProject = projectCard?.dataset.isChild === '1';
            const formData = new FormData(editProjectForm);

            if (isSubProject) {
                formData.delete('department');
            }

            formData.append('_method', 'PUT');

            const author = typeof window.getLastAuthor === 'function' ? window.getLastAuthor() : '';
            if (author) {
                formData.append('author', author);
            }

            try {
                const response = await apiRequest(`/projects/${projectId}`, {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
            } catch (error) {
                alert(error.message || 'プロジェクトの更新に失敗しました');
            }
        });
    }

    drawHierarchyConnectors();
    window.addEventListener('resize', () => {
        requestAnimationFrame(drawHierarchyConnectors);
    });
});

function rememberAuthorFromForm(formData) {
    if (typeof window.setLastAuthor !== 'function' || !formData) {
        return;
    }

    const author = formData.get('author');

    if (typeof author === 'string' && author.trim() !== '') {
        window.setLastAuthor(author);
    }
}

async function apiRequest(url, options = {}) {
    const headers = {
        ...(typeof window.csrfHeaders === 'function' ? window.csrfHeaders() : {}),
        ...(options.headers || {}),
    };

    const response = await fetch(appUrl(url), {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    if (response.status === 419) {
        if (typeof window.handleSessionExpired === 'function') {
            window.handleSessionExpired();
        } else {
            alert('セッションの有効期限が切れました。ページを再読み込みします。');
            window.location.reload();
        }

        throw new Error('セッションの有効期限が切れました');
    }

    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
        if (typeof window.showAuthModal === 'function') {
            window.showAuthModal(data.message || 'ログインが必要です');
        }

        throw new Error(data.message || '認証が必要です');
    }

    if (!response.ok) {
        const errorMessages = data.errors
            ? Object.values(data.errors).flat()
            : [];

        const message = errorMessages.join('\n')
            || data.message
            || 'リクエストに失敗しました';

        throw new Error(message);
    }

    return data;
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);

    if (modal) {
        modal.classList.add('is-open');
        modal.style.display = 'flex';
    }
}

function resetChildHistoryExpandState(historyContent) {
    historyContent.classList.remove('history-expanded');

    const expandButton = historyContent.querySelector('[data-action="toggle-history-expand"]');

    if (expandButton) {
        expandButton.setAttribute('aria-expanded', 'false');
        expandButton.setAttribute('aria-label', 'コメントをさらに表示');
    }
}

function expandChildProjectHistory(projectId) {
    const content = document.getElementById(`history-content-${projectId}`);
    const toggle = document.querySelector(`.toggle-history[data-project-id="${projectId}"]`);

    if (!content) {
        return false;
    }

    if (content.classList.contains('collapsed') || content.style.display === 'none') {
        content.classList.remove('collapsed');
        content.style.removeProperty('display');
        resetChildHistoryExpandState(content);

        if (toggle) {
            toggle.textContent = '▼';
        }

        requestAnimationFrame(drawHierarchyConnectors);
    }

    return true;
}

function toggleChildProjectHistory(projectId) {
    const content = document.getElementById(`history-content-${projectId}`);
    const toggle = document.querySelector(`.toggle-history[data-project-id="${projectId}"]`);

    if (!content) {
        return false;
    }

    const isCollapsed = content.classList.contains('collapsed');

    if (isCollapsed) {
        content.classList.remove('collapsed');
        content.style.removeProperty('display');
        resetChildHistoryExpandState(content);

        if (toggle) {
            toggle.textContent = '▼';
        }
    } else {
        content.classList.add('collapsed');

        if (toggle) {
            toggle.textContent = '▶';
        }
    }

    requestAnimationFrame(drawHierarchyConnectors);
    return true;
}

function toggleHistoryExpand(button) {
    const history = button.closest('.project-history');

    if (!history) {
        return;
    }

    const expanded = history.classList.toggle('history-expanded');
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    button.setAttribute('aria-label', expanded ? 'コメントを減らして表示' : 'コメントをさらに表示');
    requestAnimationFrame(drawHierarchyConnectors);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);

    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = 'none';
    }
}

function openProgressModal(projectId) {
    document.getElementById('progressProjectId').value = projectId;
    document.getElementById('progressAuthor').value = '';
    document.getElementById('progressContent').value = '';
    openModal('progressModal');
}

function openAddProjectModal() {
    const form = document.getElementById('addProjectForm');
    const teamMembers = document.getElementById('teamMembers');
    const teamMemberTags = document.getElementById('teamMemberTags');

    if (form) {
        form.reset();
    }

    if (teamMembers) {
        teamMembers.value = '[]';
    }

    if (teamMemberTags) {
        teamMemberTags.innerHTML = '';
    }

    openModal('addProjectModal');
}

function openSubProjectModal(projectId) {
    document.getElementById('parentProjectId').value = projectId;
    document.getElementById('subProjectName').value = '';
    document.getElementById('subProjectAuthor').value = '';

    const teamMembers = document.getElementById('subTeamMembers');
    const teamMemberTags = document.getElementById('subTeamMemberTags');
    const teamMemberInput = document.getElementById('subTeamMemberInput');

    if (teamMembers) {
        teamMembers.value = '[]';
    }

    if (teamMemberTags) {
        teamMemberTags.innerHTML = '';
    }

    if (teamMemberInput) {
        teamMemberInput.value = '';
    }

    openModal('subProjectModal');
}

async function openEditProjectModal(projectId, projectName) {
    document.getElementById('editProjectId').value = projectId;
    document.getElementById('editProjectName').value = projectName;
    document.getElementById('editTeamMemberTags').innerHTML = '';
    document.getElementById('editTeamMembers').value = '[]';

    const projectCard = document.querySelector(`.project-card[data-project-id="${projectId}"]`);
    const isSubProject = projectCard?.dataset.isChild === '1';
    const departmentGroup = document.getElementById('editDepartmentGroup');

    if (departmentGroup) {
        departmentGroup.style.display = isSubProject ? 'none' : 'block';
    }

    openModal('editProjectModal');

    try {
        const response = await apiRequest(`/projects/${projectId}`);

        if (!response.success || !response.project) {
            alert('プロジェクト情報の取得に失敗しました');
            return;
        }

        if (!isSubProject) {
            const departmentSelect = document.getElementById('editProjectDepartment');

            if (departmentSelect) {
                departmentSelect.value = response.project.department || '選択なし';
            }
        }

        const members = Array.isArray(response.project.team_members)
            ? response.project.team_members
            : [];

        document.getElementById('editTeamMembers').value = JSON.stringify(members);
        renderTeamMemberTags('editTeamMemberTags', 'editTeamMembers', members);
    } catch (error) {
        alert(error.message || 'プロジェクト情報の取得に失敗しました');
    }
}

async function confirmDeleteProject(projectId) {
    if (!projectId) {
        alert('有効なプロジェクトIDが指定されていません');
        return;
    }

    if (!confirm('このプロジェクトを削除してもよろしいですか？')) {
        return;
    }

    try {
        const author = typeof window.getLastAuthor === 'function' ? window.getLastAuthor() : '';
        const query = author ? `?author=${encodeURIComponent(author)}` : '';
        const response = await apiRequest(`/projects/${projectId}${query}`, {
            method: 'DELETE',
        });

        if (response.success) {
            window.location.reload();
            return;
        }

        alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
    } catch (error) {
        alert(error.message || 'プロジェクトの削除に失敗しました');
    }
}

function setupTeamMemberInput(inputId, tagsId, hiddenInputId, addButtonId) {
    const input = document.getElementById(inputId);
    const tagsContainer = document.getElementById(tagsId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const addButton = addButtonId ? document.getElementById(addButtonId) : null;

    if (!input || !tagsContainer || !hiddenInput) {
        return;
    }

    function addMemberFromInput() {
        const value = input.value.trim();

        if (!value) {
            return;
        }

        const members = parseTeamMembers(hiddenInput.value);

        if (!members.includes(value)) {
            members.push(value);
            hiddenInput.value = JSON.stringify(members);
            renderTeamMemberTags(tagsId, hiddenInputId, members);
        }

        input.value = '';
        input.focus();
    }

    if (addButton) {
        addButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            addMemberFromInput();
        });
    }

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        addMemberFromInput();
    });
}

function addAuthorToTeam() {
    const authorInput = document.getElementById('projectAuthor');
    const hiddenInput = document.getElementById('teamMembers');
    const tagsId = 'teamMemberTags';

    if (!authorInput || !hiddenInput) {
        return;
    }

    const authorName = authorInput.value.trim();

    if (!authorName) {
        return;
    }

    const members = parseTeamMembers(hiddenInput.value);

    if (!members.includes(authorName)) {
        members.push(authorName);
        hiddenInput.value = JSON.stringify(members);
        renderTeamMemberTags(tagsId, 'teamMembers', members);
    }
}

function addSubAuthorToTeam() {
    const authorInput = document.getElementById('subProjectAuthor');
    const hiddenInput = document.getElementById('subTeamMembers');
    const tagsId = 'subTeamMemberTags';

    if (!authorInput || !hiddenInput) {
        return;
    }

    const authorName = authorInput.value.trim();

    if (!authorName) {
        return;
    }

    const members = parseTeamMembers(hiddenInput.value);

    if (!members.includes(authorName)) {
        members.push(authorName);
        hiddenInput.value = JSON.stringify(members);
        renderTeamMemberTags(tagsId, 'subTeamMembers', members);
    }
}

function parseTeamMembers(value) {
    if (!value || value === '[]') {
        return [];
    }

    try {
        const members = JSON.parse(value);
        return Array.isArray(members) ? members : [];
    } catch (error) {
        return [];
    }
}

function renderTeamMemberTags(tagsId, hiddenInputId, members) {
    const tagsContainer = document.getElementById(tagsId);
    const hiddenInput = document.getElementById(hiddenInputId);

    if (!tagsContainer || !hiddenInput) {
        return;
    }

    tagsContainer.innerHTML = '';

    members.forEach((member, index) => {
        const tag = document.createElement('div');
        tag.className = 'team-member-tag';
        tag.innerHTML = `
            ${member}
            <span class="delete-tag" data-index="${index}">×</span>
        `;

        tag.querySelector('.delete-tag').addEventListener('click', () => {
            const currentMembers = parseTeamMembers(hiddenInput.value);
            currentMembers.splice(index, 1);
            hiddenInput.value = JSON.stringify(currentMembers);
            renderTeamMemberTags(tagsId, hiddenInputId, currentMembers);
        });

        tagsContainer.appendChild(tag);
    });
}

async function openEditHistoryModal(historyId) {
    try {
        const response = await apiRequest(`/histories/${historyId}`);

        if (!response.success || !response.history) {
            alert('履歴の取得に失敗しました');
            return;
        }

        document.getElementById('editHistoryId').value = response.history.id;
        document.getElementById('editHistoryAuthor').value = response.history.author || '';
        document.getElementById('editHistoryContent').value = response.history.content || '';
        openModal('editHistoryModal');
    } catch (error) {
        alert(error.message || '履歴の取得に失敗しました');
    }
}

function showStatusDropdown(element) {
    const projectId = element.dataset.projectId;
    const dropdown = document.getElementById('status-dropdown');

    if (!dropdown || !projectId) {
        return;
    }

    const rect = element.getBoundingClientRect();

    dropdown.style.top = `${window.scrollY + rect.bottom + 5}px`;
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.display = 'block';
    dropdown.dataset.projectId = projectId;
}

async function updateProjectStatus(projectId, newStatus) {
    const formData = new FormData();
    formData.append('status', newStatus);

    const author = typeof window.getLastAuthor === 'function' ? window.getLastAuthor() : '';
    if (author) {
        formData.append('author', author);
    }

    try {
        const response = await apiRequest(`/projects/${projectId}/status`, {
            method: 'POST',
            body: formData,
        });

        if (response.success) {
            window.location.reload();
            return;
        }

        alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
    } catch (error) {
        alert(error.message || 'ステータスの更新に失敗しました');
    }
}

async function confirmDeleteHistory(historyId) {
    if (!historyId) {
        alert('有効な履歴IDが指定されていません');
        return;
    }

    if (!confirm('この進捗を削除してもよろしいですか？')) {
        return;
    }

    try {
        const author = typeof window.getLastAuthor === 'function' ? window.getLastAuthor() : '';
        const query = author ? `?author=${encodeURIComponent(author)}` : '';
        const response = await apiRequest(`/histories/${historyId}${query}`, {
            method: 'DELETE',
        });

        if (response.success) {
            window.location.reload();
            return;
        }

        alert('エラーが発生しました: ' + (response.message || '不明なエラー'));
    } catch (error) {
        alert(error.message || '履歴の削除に失敗しました');
    }
}

function matchesStatusFilter(cardStatus, filterValue) {
    if (filterValue === 'all') {
        return true;
    }

    if (filterValue === 'active') {
        return ['未着手', '進行中', 'レビュー中'].includes(cardStatus);
    }

    return cardStatus === filterValue;
}

function filterByStatus(status) {
    document.querySelectorAll('.parent-project').forEach((parentCard) => {
        const childCards = parentCard.querySelectorAll('.child-project');
        const parentMatches = matchesStatusFilter(parentCard.dataset.status, status);
        let parentVisible = parentMatches;
        let visibleChildCount = 0;

        // 親が稼働中のときは、子は完了・保留・中止も含めてすべて表示
        const showAllChildren = status === 'active' && parentMatches;

        childCards.forEach((childCard) => {
            const childVisible = showAllChildren || matchesStatusFilter(childCard.dataset.status, status);
            childCard.style.display = childVisible ? 'block' : 'none';

            if (childVisible) {
                visibleChildCount += 1;
            }
        });

        if (status !== 'all' && !parentVisible && visibleChildCount > 0) {
            parentVisible = true;
        }

        parentCard.style.display = parentVisible ? 'block' : 'none';
    });

    requestAnimationFrame(drawHierarchyConnectors);
    if (typeof window.refreshSidebarProjectList === 'function') {
        window.refreshSidebarProjectList();
    }
}

function performSearch(searchValue) {
    const isProjectSearch = document.getElementById('searchModeToggle').checked;

    if (isProjectSearch) {
        filterByProjectName(searchValue);
    } else {
        filterByMemberName(searchValue);
    }
}

function filterByProjectName(name) {
    const normalizedName = name.trim().toLowerCase();

    document.querySelectorAll('.parent-project').forEach((parentCard) => {
        const parentTitle = parentCard.querySelector(':scope > .project-header .project-name');
        const parentMatches = normalizedName === '' ||
            (parentTitle && parentTitle.textContent.toLowerCase().includes(normalizedName));

        let childMatches = false;

        parentCard.querySelectorAll('.child-project').forEach((childCard) => {
            const childTitle = childCard.querySelector('.project-name');
            const matches = normalizedName === '' ||
                (childTitle && childTitle.textContent.toLowerCase().includes(normalizedName));

            childCard.style.display = matches ? 'block' : 'none';

            if (matches) {
                childMatches = true;
            }
        });

        const visible = normalizedName === '' || parentMatches || childMatches;
        parentCard.style.display = visible ? 'block' : 'none';

        if (visible) {
            applyCurrentStatusFilter(parentCard);
        }
    });

    requestAnimationFrame(drawHierarchyConnectors);
    if (typeof window.refreshSidebarProjectList === 'function') {
        window.refreshSidebarProjectList();
    }
}

function filterByMemberName(name) {
    const normalizedName = name.trim().toLowerCase();

    document.querySelectorAll('.parent-project').forEach((parentCard) => {
        const memberNames = Array.from(parentCard.querySelectorAll('.team-member-name, .author-avatar'))
            .map((element) => element.textContent.trim().toLowerCase());

        const historyMatches = Array.from(parentCard.querySelectorAll('.history-item'))
            .some((item) => item.textContent.toLowerCase().includes(normalizedName));

        const memberMatches = normalizedName === '' ||
            memberNames.some((member) => member.includes(normalizedName)) ||
            historyMatches;

        parentCard.style.display = memberMatches ? 'block' : 'none';

        if (memberMatches) {
            parentCard.querySelectorAll('.child-project').forEach((childCard) => {
                childCard.style.display = 'block';
            });
            applyCurrentStatusFilter(parentCard);
        }
    });

    requestAnimationFrame(drawHierarchyConnectors);
    if (typeof window.refreshSidebarProjectList === 'function') {
        window.refreshSidebarProjectList();
    }
}

function applyCurrentStatusFilter(parentCard) {
    const status = document.getElementById('statusFilter').value;

    if (status === 'all') {
        return;
    }

    const parentMatches = matchesStatusFilter(parentCard.dataset.status, status);
    const showAllChildren = status === 'active' && parentMatches;
    let visibleChildCount = 0;

    parentCard.querySelectorAll('.child-project').forEach((childCard) => {
        const childVisible = showAllChildren || matchesStatusFilter(childCard.dataset.status, status);
        childCard.style.display = childVisible ? 'block' : 'none';

        if (childVisible) {
            visibleChildCount += 1;
        }
    });

    if (!parentMatches && visibleChildCount === 0) {
        parentCard.style.display = 'none';
    }
}

function isElementVisible(element) {
    if (!element) {
        return false;
    }

    if (element.offsetParent === null && getComputedStyle(element).position !== 'fixed') {
        return false;
    }

    const style = getComputedStyle(element);
    return style.display !== 'none' && style.visibility !== 'hidden';
}

function drawHierarchyConnectors() {
    document.querySelectorAll('.parent-project').forEach((parentCard) => {
        let svg = parentCard.querySelector(':scope > .hierarchy-connector');

        if (!svg) {
            svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.classList.add('hierarchy-connector');
            svg.setAttribute('aria-hidden', 'true');
            parentCard.prepend(svg);
        }

        if (!isElementVisible(parentCard)) {
            svg.innerHTML = '';
            return;
        }

        const parentHeader = parentCard.querySelector(':scope > .project-header');
        const childCards = Array.from(parentCard.querySelectorAll(':scope > .sub-projects > .child-project'))
            .filter((childCard) => isElementVisible(childCard));

        if (!parentHeader || childCards.length === 0) {
            svg.innerHTML = '';
            return;
        }

        const parentRect = parentCard.getBoundingClientRect();
        const parentHeaderRect = parentHeader.getBoundingClientRect();
        const projectName = parentHeader.querySelector('.project-name');
        const nameRect = (projectName || parentHeader).getBoundingClientRect();
        // 親タイトル1文字目付近から真下へ下ろす
        const startX = nameRect.left - parentRect.left + 6;
        const startY = parentHeaderRect.top - parentRect.top + parentHeaderRect.height / 2;

        const joints = [];

        childCards.forEach((childCard) => {
            const childHeader = childCard.querySelector(':scope > .project-header');

            if (!childHeader || !isElementVisible(childHeader)) {
                return;
            }

            const childHeaderRect = childHeader.getBoundingClientRect();
            joints.push({
                x: childHeaderRect.left - parentRect.left,
                y: childHeaderRect.top - parentRect.top + childHeaderRect.height / 2,
            });
        });

        if (joints.length === 0) {
            svg.innerHTML = '';
            return;
        }

        const lastY = joints[joints.length - 1].y;
        const width = Math.max(parentCard.offsetWidth, 1);
        const height = Math.max(parentCard.offsetHeight, lastY + 8, 1);

        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('width', String(width));
        svg.setAttribute('height', String(height));

        let markup = `<path class="hierarchy-trunk" d="M ${startX} ${startY} V ${lastY}" />`;

        joints.forEach((joint) => {
            markup += `<path class="hierarchy-branch" d="M ${startX} ${joint.y} H ${joint.x}" />`;
        });

        svg.innerHTML = markup;
    });
}
