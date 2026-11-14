document.addEventListener('DOMContentLoaded', () => {
    const departmentFilter = document.getElementById('departmentFilter');
    const statusFilter = document.getElementById('statusFilter');
    const memberSearch = document.getElementById('memberSearch');
    const clearSearch = document.getElementById('clearSearch');
    const myBallToggle = document.getElementById('myBallToggle');
    const hasBallToggle = document.getElementById('hasBallToggle');
    const holderFilter = document.getElementById('holderFilter');
    const editHistoryForm = document.getElementById('editHistoryForm');
    const addProjectForm = document.getElementById('addProjectForm');
    const editProjectForm = document.getElementById('editProjectForm');

    setupTeamMemberInput('teamMemberInput', 'teamMemberTags', 'teamMembers', 'teamMemberAddBtn');
    setupTeamMemberInput('editTeamMemberInput', 'editTeamMemberTags', 'editTeamMembers', 'editTeamMemberAddBtn');
    setupSubjectPickers();

    if (departmentFilter) {
        departmentFilter.addEventListener('change', reloadHomeFilters);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', reloadHomeFilters);
    }

    if (memberSearch && clearSearch) {
        memberSearch.addEventListener('input', () => {
            clearSearch.style.display = memberSearch.value.trim().length > 0 ? 'block' : 'none';
            scheduleCommentHits();
            applyTopicFilters();
        });

        clearSearch.addEventListener('click', () => {
            memberSearch.value = '';
            clearSearch.style.display = 'none';
            scheduleCommentHits();
            applyTopicFilters();
        });
    }

    if (myBallToggle) {
        myBallToggle.addEventListener('change', applyTopicFilters);
    }

    if (hasBallToggle) {
        hasBallToggle.addEventListener('change', applyTopicFilters);
    }

    if (holderFilter) {
        holderFilter.addEventListener('change', applyTopicFilters);
    }

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

        if (action === 'open-add-project') {
            openAddProjectModal();
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

    const reopenBallButton = document.getElementById('reopenBallButton');
    if (reopenBallButton) {
        reopenBallButton.addEventListener('click', async () => {
            const historyId = document.getElementById('editHistoryId').value;
            if (!historyId || !confirm('終了コメントを消して、ボールに戻しますか？')) {
                return;
            }

            try {
                const response = await apiRequest(`/histories/${historyId}/reopen-ball`, {
                    method: 'POST',
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || 'ボールを戻せませんでした');
            }
        });
    }

    if (editHistoryForm) {
        editHistoryForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const historyId = document.getElementById('editHistoryId').value;
            const formData = new FormData(editHistoryForm);
            formData.append('_method', 'PUT');

            try {
                const response = await apiRequest(`/histories/${historyId}`, {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    const content = document.getElementById('editHistoryContent')?.value ?? '';
                    closeModal('editHistoryModal');

                    if (typeof window.replaceRoomHistoryContent !== 'function' || !window.replaceRoomHistoryContent(historyId, content)) {
                        window.location.reload();
                    }
                }
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

            const subjectPicker = addProjectForm.querySelector('[data-subject-picker]');
            if (subjectPicker?.subjectPicker && !subjectPicker.subjectPicker.commit()) {
                alert('対象を候補から選ぶか、一番下の新規作成を押してください');
                subjectPicker.querySelector('.subject-picker-input')?.focus();
                return;
            }

            try {
                const response = await apiRequest('/projects', {
                    method: 'POST',
                    body: new FormData(addProjectForm),
                });

                if (response.success) {
                    window.location.href = response.redirect || window.location.href;
                }
            } catch (error) {
                alert(error.message || 'エラーが発生しました');
            }
        });
    }

    if (editProjectForm) {
        editProjectForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const editSubjectPicker = editProjectForm.querySelector('[data-subject-picker]');
            if (editSubjectPicker?.subjectPicker && !editSubjectPicker.subjectPicker.commit()) {
                alert('対象を候補から選ぶか、一番下の新規作成を押してください');
                editSubjectPicker.querySelector('.subject-picker-input')?.focus();
                return;
            }

            const projectId = document.getElementById('editProjectId').value;
            const formData = new FormData(editProjectForm);
            formData.append('_method', 'PUT');

            try {
                const response = await apiRequest(`/projects/${projectId}`, {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || '議題の更新に失敗しました');
            }
        });
    }

    applyTopicFilters();
    setupTopicPlates();
});

function topicPlateControl(target) {
    return target.closest('.topic-subject-link, .topic-name-link, [data-action], button, input, select, textarea, label');
}

function topicRoomUrl(row) {
    return row.dataset.roomUrl || row.querySelector('.topic-name-link')?.href || '';
}

function plainPrimaryClick(event) {
    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function setupTopicPlates() {
    const page = document.querySelector('.home-page');

    if (!page || page.classList.contains('subjects-page')) {
        return;
    }

    const selectRow = (row) => {
        page.querySelectorAll('.topic-row.is-selected').forEach((item) => {
            if (item !== row) {
                item.classList.remove('is-selected');
            }
        });
        row.classList.add('is-selected');
    };

    page.addEventListener('click', (event) => {
        if (!plainPrimaryClick(event)) {
            return;
        }

        const row = event.target.closest('.topic-row');

        if (!row || !page.contains(row)) {
            page.querySelectorAll('.topic-row.is-selected').forEach((item) => {
                item.classList.remove('is-selected');
            });
            return;
        }

        if (topicPlateControl(event.target)) {
            return;
        }

        selectRow(row);
    });

    page.addEventListener('dblclick', (event) => {
        const row = event.target.closest('.topic-row');

        if (!row || !page.contains(row) || topicPlateControl(event.target) || !plainPrimaryClick(event)) {
            return;
        }

        const url = topicRoomUrl(row);

        if (!url) {
            return;
        }

        window.location.href = url;
    });
}

function parseBallHolders(raw) {
    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed.map((name) => String(name)) : [];
    } catch (error) {
        return [];
    }
}

let commentHits = {};
let commentHitsQuery = '';
let commentHitsRequest = 0;
let commentHitsTimer = null;
let ballLinesNarrowed = false;

function reloadHomeFilters() {
    const url = new URL(window.location.href);
    url.search = '';
    const department = document.getElementById('departmentFilter')?.value || 'all';
    const status = document.getElementById('statusFilter')?.value || '進行中';

    if (department !== 'all') {
        url.searchParams.set('department', department);
    }

    if (status !== '進行中') {
        url.searchParams.set('status', status);
    }

    window.location.href = url.toString();
}

function scheduleCommentHits() {
    window.clearTimeout(commentHitsTimer);
    const query = normalizeSearchText(document.getElementById('memberSearch')?.value || '');

    if (query === '') {
        commentHits = {};
        commentHitsQuery = '';
        commentHitsRequest += 1;
        return;
    }

    commentHitsTimer = window.setTimeout(() => {
        fetchCommentHits(query);
    }, 250);
}

async function fetchCommentHits(query) {
    const requestId = ++commentHitsRequest;
    const params = new URLSearchParams({
        q: query,
        department: document.getElementById('departmentFilter')?.value || 'all',
        status: document.getElementById('statusFilter')?.value || '進行中',
    });

    try {
        const response = await apiRequest(`/comment-hits?${params.toString()}`);

        if (requestId !== commentHitsRequest) {
            return;
        }

        commentHits = response.hits && typeof response.hits === 'object' ? response.hits : {};
        commentHitsQuery = query;
        applyTopicFilters();
    } catch (error) {
        if (requestId !== commentHitsRequest) {
            return;
        }
    }
}

function normalizeSearchText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
}

function ballLineAssignees(line) {
    if (line.dataset.assignees) {
        try {
            const parsed = JSON.parse(line.dataset.assignees);
            if (Array.isArray(parsed)) {
                return parsed.map((name) => String(name));
            }
        } catch (error) {
            // 壊れた属性は data-assignee に戻す
        }
    }

    return line.dataset.assignee ? [line.dataset.assignee] : [];
}

function matchingComment(projectId, query) {
    if (query === '' || commentHitsQuery !== query) {
        return null;
    }

    const hit = commentHits[String(projectId)];

    return hit && hit.text ? hit : null;
}

function applyTopicFilters() {
    const rows = document.querySelectorAll('.topic-row');
    const empty = document.getElementById('topicFilterEmpty');

    if (rows.length === 0) {
        if (empty) {
            empty.hidden = true;
        }

        return;
    }

    const query = normalizeSearchText(document.getElementById('memberSearch')?.value || '');
    const mineOnly = document.getElementById('myBallToggle')?.checked === true;
    const ballsOnly = document.getElementById('hasBallToggle')?.checked === true;
    const holder = document.getElementById('holderFilter')?.value || '';
    const narrowBalls = mineOnly || holder !== '';
    let visibleCount = 0;

    rows.forEach((row) => {
        const mineOk = !mineOnly || row.dataset.hasMyBall === '1';
        const ballsOk = !ballsOnly || row.dataset.hasBall === '1';
        const holderOk = holder === '' || parseBallHolders(row.dataset.ballHolders).includes(holder);
        let searchOk = true;
        let comment = null;
        let visibleText = '';

        if (query !== '') {
            const ballText = [...row.querySelectorAll('.ball-line')].map((line) => line.textContent).join(' ');
            const preview = row.querySelector('.topic-preview')?.textContent || '';
            visibleText = normalizeSearchText([
                row.dataset.searchName,
                row.dataset.searchSubject,
                row.dataset.searchMembers,
                ballText,
                preview,
            ].join(' '));
            comment = matchingComment(row.dataset.projectId, query);
            searchOk = visibleText.includes(query) || comment !== null;
        }

        const show = mineOk && ballsOk && holderOk && searchOk;

        row.style.display = show ? '' : 'none';

        const hit = row.querySelector('.topic-comment-hit');

        if (hit) {
            const showComment = show && query !== '' && comment && !visibleText.includes(query);
            hit.hidden = !showComment;
            hit.textContent = showComment
                ? `「${comment.text}」${comment.author ? ` ${comment.author}` : ''}`
                : '';
            hit.title = hit.textContent;
        }

        if (show) {
            visibleCount += 1;
        }

        if (narrowBalls) {
            row.querySelectorAll('.ball-line').forEach((line) => {
                const mineMatch = !mineOnly || line.classList.contains('ball-mine');
                const holderMatch = holder === '' || ballLineAssignees(line).includes(holder);
                const showLine = mineMatch && holderMatch;
                line.style.display = showLine ? '' : 'none';
                let sibling = line.nextElementSibling;
                while (sibling && sibling.classList.contains('ball-panel')) {
                    sibling.style.display = showLine ? '' : 'none';
                    if (!showLine) {
                        sibling.hidden = true;
                    }
                    sibling = sibling.nextElementSibling;
                }
            });
        } else if (ballLinesNarrowed) {
            row.querySelectorAll('.ball-line, .ball-panel').forEach((line) => {
                line.style.display = '';
            });
        }
    });

    ballLinesNarrowed = narrowBalls;

    if (empty) {
        empty.hidden = visibleCount > 0;
    }

    document.getElementById('holderFilter')?.classList.toggle('is-active', holder !== '');

    if (typeof window.refreshSidebarProjectList === 'function') {
        window.refreshSidebarProjectList();
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

    if (!modal) {
        return;
    }

    modal.style.display = 'flex';
    modal.classList.add('is-open');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    modal.style.display = 'none';
}

function openAddProjectModal() {
    const form = document.getElementById('addProjectForm');
    const teamMembers = document.getElementById('teamMembers');
    const teamMemberTags = document.getElementById('teamMemberTags');

    if (form) {
        form.reset();
        form.querySelector('[data-subject-picker]')?.subjectPicker?.reset();
    }

    if (teamMembers) {
        teamMembers.value = '[]';
    }

    if (teamMemberTags) {
        teamMemberTags.innerHTML = '';
    }

    openModal('addProjectModal');
}

async function openEditProjectModal(projectId, projectName) {
    document.getElementById('editProjectId').value = projectId;
    document.getElementById('editProjectName').value = projectName || '';
    document.getElementById('editTeamMemberTags').innerHTML = '';
    document.getElementById('editTeamMembers').value = '[]';
    document.querySelector('#editProjectForm [data-subject-picker]')?.subjectPicker?.reset();

    openModal('editProjectModal');

    try {
        const response = await apiRequest(`/projects/${projectId}`);

        if (!response.success || !response.project) {
            alert('議題の情報を取得できませんでした');
            return;
        }

        const departmentSelect = document.getElementById('editProjectDepartment');
        if (departmentSelect) {
            departmentSelect.value = response.project.department || '選択なし';
        }

        document.querySelector('#editProjectForm [data-subject-picker]')?.subjectPicker?.selectById(response.project.subject_id);

        const members = Array.isArray(response.project.team_members)
            ? response.project.team_members
            : [];

        document.getElementById('editTeamMembers').value = JSON.stringify(members);
        renderTeamMemberTags('editTeamMemberTags', 'editTeamMembers', members);
    } catch (error) {
        alert(error.message || '議題の情報を取得できませんでした');
    }
}

async function confirmDeleteProject(projectId) {
    if (!projectId) {
        return;
    }

    if (!confirm('この議題を削除しますか？コメントと添付ファイルも削除されます。対象は残ります。')) {
        return;
    }

    try {
        const response = await apiRequest(`/projects/${projectId}`, { method: 'DELETE' });

        if (response.success) {
            if (document.querySelector('.room-page')) {
                window.location.href = appUrl('/');
                return;
            }

            window.location.reload();
        }
    } catch (error) {
        alert(error.message || '議題の削除に失敗しました');
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
        tag.innerHTML = `${member}<span class="delete-tag" data-index="${index}">×</span>`;

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
        document.getElementById('editHistoryContent').value = response.history.content || '';
        const reopenButton = document.getElementById('reopenBallButton');
        if (reopenButton) {
            reopenButton.hidden = response.history.can_reopen !== true;
        }
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

    try {
        const response = await apiRequest(`/projects/${projectId}/status`, {
            method: 'POST',
            body: formData,
        });

        if (response.success) {
            window.location.reload();
        }
    } catch (error) {
        alert(error.message || 'ステータスの更新に失敗しました');
    }
}

async function confirmDeleteHistory(historyId) {
    if (!historyId || !confirm('このコメントを削除しますか？')) {
        return;
    }

    try {
        const response = await apiRequest(`/histories/${historyId}`, { method: 'DELETE' });

        if (response.success) {
            if (typeof window.removeRoomHistory !== 'function' || !window.removeRoomHistory(historyId)) {
                window.location.reload();
            }
        }
    } catch (error) {
        alert(error.message || 'コメントの削除に失敗しました');
    }
}

function setupSubjectPickers() {
    const catalog = document.getElementById('subjectCatalog');
    let subjects = [];

    if (catalog) {
        try {
            const parsed = JSON.parse(catalog.textContent || '[]');
            subjects = Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            subjects = [];
        }
    }

    document.querySelectorAll('[data-subject-picker]').forEach((root) => {
        bindSubjectPicker(root, subjects);
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-subject-picker]').forEach((root) => {
            if (!root.contains(event.target)) {
                root.subjectPicker?.close();
            }
        });
    });
}

function bindSubjectPicker(root, subjects) {
    const input = root.querySelector('.subject-picker-input');
    const menu = root.querySelector('.subject-picker-menu');
    const options = root.querySelector('.subject-picker-options');
    const createButton = root.querySelector('.subject-picker-create');
    const subjectInput = root.querySelector('input[name="subject_id"]');
    const newNameInput = root.querySelector('input[name="new_subject_name"]');
    const status = root.querySelector('.subject-picker-status');
    let mode = 'unset';
    let activeIndex = -1;
    let matches = subjects.slice();

    function query() {
        return input.value.trim();
    }

    function filtered() {
        const needle = query().toLocaleLowerCase('ja');

        if (needle === '') {
            return subjects.slice();
        }

        return subjects.filter((subject) => String(subject.name).toLocaleLowerCase('ja').includes(needle));
    }

    function setExpanded(open) {
        menu.hidden = !open;
        input.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function optionCount() {
        return matches.length + (query() === '' ? 0 : 1);
    }

    function render() {
        matches = filtered();
        const text = query();
        const exact = subjects.find((subject) => subject.name === text);
        options.replaceChildren();

        matches.forEach((subject, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'subject-picker-option' + (index === activeIndex ? ' is-active' : '');
            button.setAttribute('role', 'option');
            button.textContent = subject.name;
            button.addEventListener('click', () => applyExisting(subject));
            options.appendChild(button);
        });

        createButton.disabled = text === '';
        if (text === '') {
            createButton.textContent = '新規作成';
        } else if (exact) {
            createButton.textContent = `「${text}」は既にあります`;
        } else {
            createButton.textContent = `「${text}」を新規作成`;
        }

        const createIndex = matches.length;
        const createActive = activeIndex === createIndex && !createButton.disabled;
        createButton.classList.toggle('is-active', createActive);

        if (activeIndex >= optionCount()) {
            activeIndex = -1;
        }

        const activeOption = options.querySelector('.is-active');
        if (activeOption) {
            const top = activeOption.offsetTop;
            const bottom = top + activeOption.offsetHeight;

            if (top < options.scrollTop) {
                options.scrollTop = top;
            } else if (bottom > options.scrollTop + options.clientHeight) {
                options.scrollTop = bottom - options.clientHeight;
            }
        }
    }

    function open() {
        render();
        setExpanded(true);
    }

    function close() {
        activeIndex = -1;
        setExpanded(false);
    }

    function applyExisting(subject) {
        input.value = subject.name;
        subjectInput.value = String(subject.id);
        newNameInput.value = '';
        mode = 'existing';
        status.hidden = true;
        status.textContent = '';
        close();
    }

    function applyCreate(name) {
        const exact = subjects.find((subject) => subject.name === name);

        if (exact) {
            applyExisting(exact);
            status.hidden = false;
            status.textContent = '同じ名前の対象があるので、それを使います';
            return;
        }

        input.value = name;
        subjectInput.value = '';
        newNameInput.value = name;
        mode = 'create';
        status.hidden = false;
        status.textContent = `「${name}」を新しい対象として作成します`;
        close();
    }

    function chooseActive() {
        if (activeIndex >= 0 && activeIndex < matches.length) {
            applyExisting(matches[activeIndex]);
            return;
        }

        if (activeIndex === matches.length && !createButton.disabled) {
            applyCreate(query());
        }
    }

    function clearChoice() {
        mode = 'unset';
        subjectInput.value = '';
        newNameInput.value = '';
        status.hidden = true;
        status.textContent = '';
    }

    input.addEventListener('focus', () => {
        activeIndex = query() !== '' && filtered().length === 0 ? 0 : -1;
        open();
    });

    input.addEventListener('input', () => {
        const text = query();

        if (mode === 'existing') {
            const selected = subjects.find((subject) => String(subject.id) === subjectInput.value);
            if (!selected || selected.name !== text) {
                clearChoice();
            }
        } else if (mode === 'create' && newNameInput.value !== text) {
            clearChoice();
        }

        const next = filtered();
        activeIndex = text !== '' && next.length === 0 ? 0 : -1;
        open();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (menu.hidden) {
                open();
            }
            const count = optionCount();
            if (count === 0) {
                return;
            }
            if (event.key === 'ArrowDown') {
                activeIndex = activeIndex < 0 ? 0 : (activeIndex + 1) % count;
            } else {
                activeIndex = activeIndex < 0 ? count - 1 : (activeIndex - 1 + count) % count;
            }
            render();
            return;
        }

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key === 'Enter' && !menu.hidden && activeIndex >= 0) {
            event.preventDefault();
            chooseActive();
        }
    });

    menu.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    createButton.addEventListener('click', () => {
        if (!createButton.disabled) {
            applyCreate(query());
        }
    });

    root.subjectPicker = {
        reset() {
            input.value = '';
            clearChoice();
            close();
        },
        selectById(id) {
            const subject = subjects.find((item) => String(item.id) === String(id));

            if (!subject) {
                this.reset();
                return;
            }

            applyExisting(subject);
        },
        commit() {
            const text = query();

            if (mode === 'existing' && subjectInput.value !== '') {
                const selected = subjects.find((item) => String(item.id) === subjectInput.value);
                if (selected && selected.name === text) {
                    return true;
                }
            }

            if (mode === 'create' && newNameInput.value !== '' && newNameInput.value === text) {
                return true;
            }

            const exact = subjects.find((item) => item.name === text);
            if (exact) {
                applyExisting(exact);
                return true;
            }

            if (text !== '' && filtered().length === 0) {
                applyCreate(text);
                return true;
            }

            open();
            return false;
        },
        close,
    };
}

window.apiRequest = apiRequest;
