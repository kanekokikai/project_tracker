function scrollRoomLogToCenter(log) {
    const headerHeight = document.querySelector('.header')?.getBoundingClientRect().height || 0;
    const visibleHeight = window.innerHeight - headerHeight;
    const rect = log.getBoundingClientRect();
    const focusHeight = Math.min(rect.height, visibleHeight * 0.72);
    const visibleCenter = headerHeight + visibleHeight / 2;
    const target = Math.max(0, window.scrollY + rect.top + focusHeight / 2 - visibleCenter);
    const page = document.querySelector('.room-page');
    const needed = target + window.innerHeight + 24;

    if (page && needed > document.documentElement.scrollHeight) {
        const extra = needed - document.documentElement.scrollHeight;
        const current = parseFloat(page.style.paddingBottom) || 0;
        page.style.paddingBottom = `${current + extra}px`;
    }

    const maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.scrollTo({
        top: Math.min(target, maxScroll),
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
        chat.insertBefore(latest, chat.firstChild);
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

        node.appendChild(document.createTextNode(line));
    });
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
    const ballJump = document.getElementById('roomBallJump');
    const ballForm = document.getElementById('throwBallForm');

    if (jumps) {
        placeRoomJumps(jumps);
        window.addEventListener('resize', () => placeRoomJumps(jumps));
    }

    if (logJump && roomLog) {
        logJump.addEventListener('click', () => {
            scrollRoomLogToCenter(roomLog);
        });
    }

    if (ballJump && ballForm) {
        ballJump.addEventListener('click', () => {
            scrollRoomLogToCenter(ballForm);
        });
    }

    const messageLog = document.querySelector('.room-page .project-history');
    if (messageLog) {
        messageLog.scrollTop = messageLog.scrollHeight;
    }

    const commentForm = document.getElementById('roomCommentForm');
    const noDueToggle = document.getElementById('noDueToggle');
    const projectId = document.querySelector('.room-card')?.dataset.projectId;

    if (noDueToggle) {
        const dueInput = ballForm?.querySelector('input[name="due_date"]');
        noDueToggle.addEventListener('change', () => {
            if (!dueInput) {
                return;
            }

            dueInput.disabled = noDueToggle.checked;
            if (noDueToggle.checked) {
                dueInput.value = '';
            }
        });
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

    if (commentForm) {
        commentForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (commentForm.dataset.sending === '1') {
                return;
            }

            const sendButton = commentForm.querySelector('.room-send');
            commentForm.dataset.sending = '1';

            if (sendButton) {
                sendButton.disabled = true;
            }

            try {
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
                alert(error.message || 'コメントを送れませんでした');
            } finally {
                delete commentForm.dataset.sending;

                if (sendButton) {
                    sendButton.disabled = false;
                }
            }
        });
    }

    if (ballForm && projectId) {
        ballForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(ballForm);

            if (noDueToggle?.checked) {
                formData.set('no_due', '1');
                formData.delete('due_date');
            }

            try {
                const response = await apiRequest(`/projects/${projectId}/balls`, {
                    method: 'POST',
                    body: formData,
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || 'ボールを投げられませんでした');
            }
        });
    }

    document.addEventListener('click', (event) => {
        const toggleDone = event.target.closest('[data-action="toggle-done"]');
        if (toggleDone) {
            const panel = document.getElementById(`done-panel-${toggleDone.dataset.ballId}`);
            if (panel) {
                panel.hidden = !panel.hidden;
            }
        }

        const toggleExtend = event.target.closest('[data-action="toggle-extend"]');
        if (toggleExtend) {
            const panel = document.getElementById(`extend-panel-${toggleExtend.dataset.ballId}`);
            if (panel) {
                panel.hidden = !panel.hidden;
            }
        }

        const templateButton = event.target.closest('[data-action="use-template"]');
        if (templateButton) {
            const form = templateButton.closest('form');
            const textarea = form?.querySelector('textarea[name="content"]');
            if (textarea) {
                textarea.value = templateButton.textContent.trim();
                form.requestSubmit();
            }
        }
    });

    document.querySelectorAll('.ball-done-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const ballId = form.dataset.ballId;

            try {
                const response = await apiRequest(`/balls/${ballId}/done`, {
                    method: 'POST',
                    body: new FormData(form),
                });

                if (response.success) {
                    window.location.reload();
                }
            } catch (error) {
                alert(error.message || 'ボールを閉じられませんでした');
            }
        });
    });

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
