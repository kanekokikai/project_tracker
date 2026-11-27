function roomHeaderHeight() {
    return document.querySelector('.header')?.getBoundingClientRect().height || 0;
}

function clearRoomScrollPadding() {
    const page = document.querySelector('.room-page');

    if (page) {
        page.style.paddingBottom = '';
    }

    return page;
}

function scrollRoomTarget(target, placement) {
    const page = clearRoomScrollPadding();
    const headerHeight = roomHeaderHeight();
    const rect = target.getBoundingClientRect();
    let top;

    if (placement === 'top') {
        top = window.scrollY + rect.top - headerHeight;
    } else {
        const visibleHeight = window.innerHeight - headerHeight;
        const focusHeight = Math.min(rect.height, visibleHeight * 0.72);
        const visibleCenter = headerHeight + visibleHeight / 2;
        top = window.scrollY + rect.top + focusHeight / 2 - visibleCenter;
    }

    top = Math.max(0, top);

    if (page) {
        const shortfall = top + window.innerHeight - document.documentElement.scrollHeight;

        if (shortfall > 0) {
            page.style.paddingBottom = `${shortfall}px`;
        }
    }

    const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.scrollTo({
        top: Math.min(top, maxScroll),
        behavior: reduceMotion ? 'auto' : 'smooth',
    });
}

function roomLogIsNewer(item, newest) {
    const at = item.dataset.logAt || '';
    const newestAt = newest.dataset.logAt || '';

    if (at !== newestAt) {
        return at > newestAt;
    }

    const itemIsAttachment = item.dataset.logKind === 'attachment';
    const newestIsAttachment = newest.dataset.logKind === 'attachment';

    if (itemIsAttachment !== newestIsAttachment) {
        return !itemIsAttachment;
    }

    return Number(item.dataset.logTie || 0) > Number(newest.dataset.logTie || 0);
}

function syncRoomLatest(chat) {
    if (!chat) {
        return;
    }

    const items = chat.querySelectorAll('[data-log-at]');
    let latest = chat.querySelector('.room-latest');

    if (items.length === 0) {
        latest?.remove();
        chat.querySelector('.project-history')?.remove();

        if (!chat.querySelector('.room-empty')) {
            const empty = document.createElement('p');
            empty.className = 'room-empty';
            empty.textContent = 'まだ議事ログはありません。';
            const form = document.getElementById('roomCommentForm');
            chat.insertBefore(empty, form);
        }

        return;
    }

    let newest = items[0];

    items.forEach((item) => {
        if (roomLogIsNewer(item, newest)) {
            newest = item;
        }
    });

    if (!latest) {
        latest = document.createElement('p');
        latest.className = 'room-latest';
        const anchor = chat.querySelector('.project-history')
            || chat.querySelector('.room-empty')
            || document.getElementById('roomCommentForm');
        chat.insertBefore(latest, anchor);
    }

    latest.replaceChildren();

    const label = document.createElement('span');
    label.className = 'room-latest-label';
    label.textContent = newest.dataset.logKind === 'attachment' ? '最後の添付' : '最後の発言';

    const name = document.createElement('span');
    name.className = 'room-latest-name';
    name.textContent = newest.dataset.logName || '不明';

    const time = document.createElement('time');

    if (newest.dataset.logAt) {
        time.dateTime = newest.dataset.logAt;
    }

    time.textContent = `${newest.dataset.logDate || ''} ${newest.dataset.logTime || ''}`.trim();
    latest.append(label, name, time);
}

function ensureRoomLog(chat) {
    chat.querySelector('.room-empty')?.remove();

    let log = chat.querySelector('.project-history');

    if (!log) {
        log = document.createElement('div');
        log.className = 'project-history';
        const form = document.getElementById('roomCommentForm');
        chat.insertBefore(log, form);
    }

    return log;
}

function appendMultiline(node, content) {
    String(content ?? '').split('\n').forEach((line, index) => {
        if (index > 0) {
            node.appendChild(document.createElement('br'));
        }

        appendLinkedText(node, line);
    });
}

function appendLinkedText(node, line) {
    const pattern = /https?:\/\/[A-Za-z0-9\-._~:/?#\[\]@!$&'()*+,;=%]+/g;
    let last = 0;
    let match;

    while ((match = pattern.exec(line)) !== null) {
        if (match.index > last) {
            node.appendChild(document.createTextNode(line.slice(last, match.index)));
        }

        const split = splitTrailingUrl(match[0]);

        if (/^https?:\/\/[A-Za-z0-9]/i.test(split.url)) {
            const link = document.createElement('a');
            link.href = split.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.className = 'message-link';
            link.textContent = split.url;
            node.appendChild(link);

            if (split.trailing) {
                node.appendChild(document.createTextNode(split.trailing));
            }
        } else {
            node.appendChild(document.createTextNode(match[0]));
        }

        last = match.index + match[0].length;
    }

    if (last < line.length) {
        node.appendChild(document.createTextNode(line.slice(last)));
    }
}

function splitTrailingUrl(url) {
    const trailingMarks = ".,!?;:'、。…";
    let trailing = '';

    while (url && trailingMarks.includes(url.slice(-1))) {
        trailing = url.slice(-1) + trailing;
        url = url.slice(0, -1);
    }

    let open = (url.match(/\(/g) || []).length;
    let close = (url.match(/\)/g) || []).length;

    while (url.endsWith(')') && close > open) {
        trailing = `)${trailing}`;
        url = url.slice(0, -1);
        close -= 1;
    }

    return { url, trailing };
}

window.replaceRoomHistoryContent = function (historyId, content) {
    const node = document.getElementById(`content-${historyId}`);

    if (!node) {
        return false;
    }

    const kind = node.querySelector('.bubble-kind');
    node.replaceChildren();

    if (kind) {
        node.appendChild(kind);
    }

    appendMultiline(node, content);

    return true;
};

window.removeRoomHistory = function (historyId) {
    const item = document.querySelector(`.room-page .history-item[data-history-id="${historyId}"]`);

    if (!item) {
        return false;
    }

    const chat = item.closest('.line-chat');
    item.remove();
    syncRoomLatest(chat);

    return true;
};

function placeRoomJumps(stack) {
    const card = document.querySelector('.room-page .room-sheet');
    const head = card?.querySelector('.room-head');
    const first = stack.querySelector('.room-jump');

    if (!card || !head || !first) {
        return;
    }

    const cardRect = card.getBoundingClientRect();
    const headRect = head.getBoundingClientRect();
    const gap = 14;
    const top = headRect.top + window.scrollY + (headRect.height - first.offsetHeight) / 2;
    let left = cardRect.right + gap;
    const maxLeft = window.innerWidth - first.offsetWidth - 8;

    if (left > maxLeft) {
        left = Math.max(8, maxLeft);
    }

    stack.style.top = `${top}px`;
    stack.style.left = `${left}px`;
}

document.addEventListener('DOMContentLoaded', () => {
    const jumps = document.getElementById('roomJumps');
    const logJump = document.getElementById('roomLogJump');
    const roomLog = document.getElementById('roomLog');

    if (jumps) {
        placeRoomJumps(jumps);
        window.addEventListener('resize', () => placeRoomJumps(jumps));
    }

    if (logJump && roomLog) {
        logJump.addEventListener('click', () => {
            scrollRoomTarget(roomLog, 'top');
        });
    }

    const messageLog = document.querySelector('.room-page .project-history');
    if (messageLog) {
        messageLog.scrollTop = messageLog.scrollHeight;
    }

    const commentForm = document.getElementById('roomCommentForm');
    const projectId = document.querySelector('.room-card')?.dataset.projectId;
    const externalAssignee = commentForm?.dataset.external || '';
    const aim = document.getElementById('roomAim');
    const aimText = document.getElementById('roomAimText');
    const aimClear = document.getElementById('roomAimClear');
    const ballButton = document.getElementById('roomBallButton');
    const sendButton = document.getElementById('roomSend');
    const sendLabel = sendButton?.querySelector('.room-send-label');
    const throwDialog = document.getElementById('throwBallDialog');
    const throwPicker = document.getElementById('throwBallPicker');
    const throwDueDate = document.getElementById('throwDueDate');
    const throwNoDue = document.getElementById('throwNoDue');
    const compose = {
        mode: 'message',
        ballId: null,
        assignees: [],
        due: '',
        noDue: false,
    };

    function personLabel(name) {
        if (name === externalAssignee || name.endsWith('さん')) {
            return name;
        }

        return `${name}さん`;
    }

    function duePhrase(due, noDue) {
        if (noDue || due === '') {
            return '期限なし';
        }

        const parts = due.split('-');

        if (parts.length !== 3) {
            return due;
        }

        return `${Number(parts[1])}/${Number(parts[2])}まで`;
    }

    function setSendLabel(label, words) {
        if (!sendButton) {
            return;
        }

        sendButton.setAttribute('aria-label', label);

        if (sendLabel) {
            sendLabel.textContent = label;
            sendLabel.hidden = !words;
        }

        sendButton.classList.toggle('room-send--words', words);
    }

    function clearAimMarks() {
        document.querySelectorAll('.room-open-ball.is-aimed').forEach((row) => {
            row.classList.remove('is-aimed');
        });
    }

    function showAim(text) {
        if (!aim || !aimText) {
            return;
        }

        aimText.textContent = text;
        aim.hidden = false;
    }

    function clearAim() {
        compose.mode = 'message';
        compose.ballId = null;
        compose.assignees = [];
        compose.due = '';
        compose.noDue = false;

        if (commentForm) {
            commentForm.dataset.mode = 'message';
        }

        if (aim) {
            aim.hidden = true;
        }

        if (commentField) {
            commentField.maxLength = 2000;
            commentField.placeholder = 'メッセージ';
        }

        setSendLabel('送信', false);
        clearAimMarks();

        if (ballButton) {
            ballButton.classList.remove('is-on');
            ballButton.setAttribute('aria-pressed', 'false');
        }
    }

    function aimReturn(row) {
        if (!row?.dataset.ballId) {
            return;
        }

        compose.mode = 'return';
        compose.ballId = row.dataset.ballId;
        compose.assignees = [];
        compose.due = '';
        compose.noDue = false;

        if (commentForm) {
            commentForm.dataset.mode = 'return';
        }

        showAim(`返事 · ${row.dataset.task || ''}`);

        if (commentField) {
            commentField.maxLength = 2000;
            commentField.placeholder = '返事を書く';
            commentField.focus();
        }

        setSendLabel('このボールに返す', true);
        clearAimMarks();
        row.classList.add('is-aimed');

        if (ballButton) {
            ballButton.classList.remove('is-on');
            ballButton.setAttribute('aria-pressed', 'false');
        }
    }

    function aimThrow(assignees, due, noDue) {
        compose.mode = 'throw';
        compose.ballId = null;
        compose.assignees = assignees;
        compose.due = due;
        compose.noDue = noDue;

        if (commentForm) {
            commentForm.dataset.mode = 'throw';
        }

        const names = assignees.map(personLabel).join('と');
        showAim(`${names}へ · ${duePhrase(due, noDue)}`);

        if (commentField) {
            commentField.maxLength = 255;
            commentField.placeholder = '依頼を書く';
            commentField.focus();
        }

        setSendLabel('投げる', true);
        clearAimMarks();

        if (ballButton) {
            ballButton.classList.add('is-on');
            ballButton.setAttribute('aria-pressed', 'true');
        }
    }

    function openThrowDialog() {
        if (!throwDialog || typeof throwDialog.showModal !== 'function') {
            return;
        }

        const selected = new Set(compose.mode === 'throw' ? compose.assignees : []);
        throwDialog.querySelectorAll('input[name="assignees[]"]').forEach((input) => {
            input.checked = selected.has(input.value);
        });

        if (throwDueDate) {
            throwDueDate.value = compose.mode === 'throw' && !compose.noDue ? compose.due : '';
            throwDueDate.disabled = compose.mode === 'throw' && compose.noDue;
        }

        if (throwNoDue) {
            throwNoDue.checked = compose.mode === 'throw' && compose.noDue;
        }

        paintThrowDue();
        closeThrowCalendar();

        throwDialog.showModal();
    }

    const commentField = document.getElementById('roomComment');
    const fitCommentField = () => {
        if (!commentField) {
            return;
        }

        commentField.style.height = 'auto';
        commentField.style.height = `${commentField.scrollHeight}px`;
    };

    if (commentField) {
        commentField.addEventListener('input', fitCommentField);
        fitCommentField();
    }

    if (aimClear) {
        aimClear.addEventListener('click', () => {
            clearAim();
            commentField?.focus();
        });
    }

    if (ballButton) {
        ballButton.addEventListener('click', openThrowDialog);
    }

    const throwDueText = document.getElementById('throwDueText');
    const throwDueFace = document.getElementById('throwDueFace');
    const throwCalendar = document.getElementById('throwCalendar');
    const throwCalTitle = document.getElementById('throwCalTitle');
    const throwCalDays = document.getElementById('throwCalDays');
    let calYear = 0;
    let calMonth = 0;

    function paintThrowDue() {
        if (!throwDueText) {
            return;
        }

        const value = throwDueDate && !throwDueDate.disabled ? throwDueDate.value : '';
        const parts = value.split('-');

        if (parts.length === 3 && parts[0] !== '') {
            throwDueText.textContent = `${parts[0]}/${Number(parts[1])}/${Number(parts[2])}`;
            throwDueText.classList.remove('is-empty');
        } else {
            throwDueText.textContent = '日付を選ぶ';
            throwDueText.classList.add('is-empty');
        }

        throwDueFace?.classList.toggle('is-off', throwDueDate?.disabled === true);
    }

    throwDueDate?.addEventListener('change', paintThrowDue);
    throwDueDate?.addEventListener('input', paintThrowDue);

    function closeThrowCalendar() {
        if (!throwCalendar) {
            return;
        }

        throwCalendar.hidden = true;
        throwDueFace?.setAttribute('aria-expanded', 'false');
    }

    function renderThrowCalendar() {
        if (!throwCalTitle || !throwCalDays) {
            return;
        }

        throwCalTitle.textContent = `${calYear}年${calMonth + 1}月`;
        throwCalDays.replaceChildren();

        const start = new Date(calYear, calMonth, 1).getDay();
        const days = new Date(calYear, calMonth + 1, 0).getDate();
        const selected = throwDueDate?.value || '';
        const now = new Date();
        const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

        for (let blank = 0; blank < start; blank += 1) {
            const pad = document.createElement('span');
            pad.className = 'room-cal-blank';
            throwCalDays.appendChild(pad);
        }

        for (let day = 1; day <= days; day += 1) {
            const iso = `${calYear}-${String(calMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'room-cal-day';
            button.textContent = String(day);

            if (iso === selected) {
                button.classList.add('is-selected');
            }

            if (iso === today) {
                button.classList.add('is-today');
            }

            button.addEventListener('click', () => {
                if (!throwDueDate) {
                    return;
                }

                throwDueDate.disabled = false;
                throwDueDate.value = iso;

                if (throwNoDue) {
                    throwNoDue.checked = false;
                }

                paintThrowDue();
                closeThrowCalendar();
            });
            throwCalDays.appendChild(button);
        }
    }

    function openThrowCalendar() {
        if (!throwCalendar || throwDueDate?.disabled) {
            return;
        }

        const selected = throwDueDate?.value;
        const base = selected ? new Date(`${selected}T00:00:00`) : new Date();
        calYear = base.getFullYear();
        calMonth = base.getMonth();
        renderThrowCalendar();
        throwCalendar.hidden = false;
        throwDueFace?.setAttribute('aria-expanded', 'true');
    }

    throwDueFace?.addEventListener('click', () => {
        if (!throwCalendar || throwDueDate?.disabled) {
            return;
        }

        if (throwCalendar.hidden) {
            openThrowCalendar();
        } else {
            closeThrowCalendar();
        }
    });

    throwCalendar?.querySelector('[data-cal="prev"]')?.addEventListener('click', () => {
        calMonth -= 1;

        if (calMonth < 0) {
            calMonth = 11;
            calYear -= 1;
        }

        renderThrowCalendar();
    });

    throwCalendar?.querySelector('[data-cal="next"]')?.addEventListener('click', () => {
        calMonth += 1;

        if (calMonth > 11) {
            calMonth = 0;
            calYear += 1;
        }

        renderThrowCalendar();
    });

    document.addEventListener('click', (event) => {
        if (!throwCalendar || throwCalendar.hidden) {
            return;
        }

        if (event.target.closest('#throwCalendar') || event.target.closest('#throwDueFace')) {
            return;
        }

        closeThrowCalendar();
    });

    if (throwNoDue && throwDueDate) {
        throwNoDue.addEventListener('change', () => {
            throwDueDate.disabled = throwNoDue.checked;

            if (throwNoDue.checked) {
                throwDueDate.value = '';
            }

            paintThrowDue();
            closeThrowCalendar();
        });
    }

    document.getElementById('throwBallCancel')?.addEventListener('click', () => {
        throwDialog?.close();
    });

    if (throwPicker) {
        throwPicker.addEventListener('submit', (event) => {
            event.preventDefault();

            const assignees = [...throwPicker.querySelectorAll('input[name="assignees[]"]:checked')]
                .map((input) => input.value);

            if (assignees.length === 0) {
                alert('誰に投げるか選んでください');
                return;
            }

            const noDue = throwNoDue?.checked === true;
            const due = throwDueDate?.value || '';

            if (!noDue && due === '') {
                alert('期限を選ぶか、期限なしにしてください');
                return;
            }

            throwDialog?.close();
            aimThrow(assignees, due, noDue);
        });
    }

    if (commentForm) {
        commentForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (commentForm.dataset.sending === '1') {
                return;
            }

            const text = commentField?.value.trim() ?? '';

            if (compose.mode === 'throw' && Array.from(text).length > 255) {
                alert('依頼は255文字までです');
                return;
            }

            commentForm.dataset.sending = '1';

            if (sendButton) {
                sendButton.disabled = true;
            }

            try {
                if (compose.mode === 'throw') {
                    const formData = new FormData();
                    compose.assignees.forEach((name) => formData.append('assignees[]', name));
                    formData.set('task', text);

                    if (compose.noDue) {
                        formData.set('no_due', '1');
                    } else {
                        formData.set('due_date', compose.due);
                    }

                    const response = await apiRequest(`/projects/${projectId}/balls`, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.success) {
                        window.location.reload();
                    }

                    return;
                }

                if (compose.mode === 'return') {
                    const formData = new FormData();
                    formData.set('content', text);

                    const response = await apiRequest(`/balls/${compose.ballId}/done`, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.success) {
                        window.location.reload();
                    }

                    return;
                }

                const response = await apiRequest('/histories', {
                    method: 'POST',
                    body: new FormData(commentForm),
                });

                if (!response.success) {
                    return;
                }

                const chat = commentForm.closest('.line-chat');

                if (!response.html || !chat) {
                    window.location.reload();
                    return;
                }

                const log = ensureRoomLog(chat);
                log.insertAdjacentHTML('beforeend', response.html);
                syncRoomLatest(chat);
                log.scrollTop = log.scrollHeight;

                if (commentField) {
                    commentField.value = '';
                    fitCommentField();
                    commentField.focus();
                }
            } catch (error) {
                const fallback = compose.mode === 'throw'
                    ? 'ボールを投げられませんでした'
                    : (compose.mode === 'return' ? 'ボールを返せませんでした' : 'コメントを送れませんでした');
                alert(error.message || fallback);
            } finally {
                delete commentForm.dataset.sending;

                if (sendButton) {
                    sendButton.disabled = false;
                }
            }
        });
    }

    document.addEventListener('click', (event) => {
        const aimReturnButton = event.target.closest('[data-action="aim-return"]');
        if (aimReturnButton) {
            const row = aimReturnButton.closest('.room-open-ball');
            if (row) {
                aimReturn(row);
            }
        }

        const toggleSiblings = event.target.closest('[data-action="toggle-siblings"]');
        if (toggleSiblings) {
            const siblings = document.getElementById('roomSiblings');
            if (siblings) {
                siblings.hidden = !siblings.hidden;
                toggleSiblings.setAttribute('aria-expanded', siblings.hidden ? 'false' : 'true');
            }
        }

        const toggleMore = event.target.closest('[data-action="toggle-more-balls"]');
        if (toggleMore) {
            const more = document.getElementById('roomOpenMore');
            if (more) {
                more.hidden = !more.hidden;
                toggleMore.setAttribute('aria-expanded', more.hidden ? 'false' : 'true');
            }
        }

        const toggleExtend = event.target.closest('[data-action="toggle-extend"]');
        if (toggleExtend) {
            const panel = document.getElementById(`extend-panel-${toggleExtend.dataset.ballId}`);
            if (panel) {
                panel.hidden = !panel.hidden;
            }
        }

        const toggleTopicDue = event.target.closest('[data-action="toggle-topic-due"]');
        if (toggleTopicDue) {
            const topicDueForm = document.getElementById('topicDueForm');
            if (topicDueForm) {
                topicDueForm.hidden = !topicDueForm.hidden;
                if (!topicDueForm.hidden) {
                    topicDueForm.querySelector('input[name="due_date"]')?.focus();
                }
            }
        }
    });

    const autoReturn = document.querySelector('.room-open-ball[data-auto-return="1"]');
    if (autoReturn) {
        aimReturn(autoReturn);
    }

    const topicDueForm = document.getElementById('topicDueForm');
    if (topicDueForm && projectId) {
        topicDueForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                const response = await apiRequest(`/projects/${projectId}/due`, {
                    method: 'POST',
                    body: new FormData(topicDueForm),
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || '期限を延ばせませんでした');
            }
        });
    }

    document.querySelectorAll('.ball-extend-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const ballId = form.dataset.ballId;

            try {
                const response = await apiRequest(`/balls/${ballId}/extend`, {
                    method: 'POST',
                    body: new FormData(form),
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || '期限を延ばせませんでした');
            }
        });
    });
});
