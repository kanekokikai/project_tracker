function getAppBaseUrl() {
    const meta = document.querySelector('meta[name="app-url"]')?.content || '';

    if (!meta) {
        return '';
    }

    try {
        const resolved = new URL(meta, window.location.origin);

        // Prefer same-origin relative base so cookies always accompany fetch/XHR.
        if (resolved.origin === window.location.origin) {
            return resolved.pathname.replace(/\/$/, '');
        }
    } catch (error) {
        // Fall through to absolute meta value.
    }

    return meta.replace(/\/$/, '');
}

function appUrl(path = '') {
    const base = getAppBaseUrl();
    const normalized = String(path || '');

    if (!normalized) {
        return base || '/';
    }

    if (/^https?:\/\//i.test(normalized)) {
        return normalized;
    }

    return `${base}/${normalized.replace(/^\//, '')}`;
}

function getCookie(name) {
    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Prefer the XSRF-TOKEN cookie (always aligned with the session cookie).
 * Meta csrf-token can go stale after login / long-lived tabs.
 */
function getCsrfToken() {
    return getCookie('XSRF-TOKEN')
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';
}

function csrfHeaders(extra = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...extra,
    };

    const xsrf = getCookie('XSRF-TOKEN');

    if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
    } else {
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        if (metaToken) {
            headers['X-CSRF-TOKEN'] = metaToken;
        }
    }

    return headers;
}

function updateCsrfMeta(token) {
    if (!token) {
        return;
    }

    const meta = document.querySelector('meta[name="csrf-token"]');

    if (meta) {
        meta.content = token;
    }
}

function handleSessionExpired(message) {
    alert(message || 'セッションの有効期限が切れました。ページを再読み込みします。');
    window.location.reload();
}

window.appUrl = appUrl;
window.getCsrfToken = getCsrfToken;
window.csrfHeaders = csrfHeaders;
window.updateCsrfMeta = updateCsrfMeta;
window.handleSessionExpired = handleSessionExpired;

document.addEventListener('DOMContentLoaded', () => {
    const authForm = document.getElementById('authForm');

    if (!authForm) {
        return;
    }

    authForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const passwordInput = document.getElementById('password');
        const authMessage = document.getElementById('authMessage');
        const password = passwordInput.value.trim();

        if (!password) {
            authMessage.textContent = 'パスワードを入力してください';
            return;
        }

        const formData = new FormData();
        formData.append('password', password);

        try {
            const response = await fetch(appUrl('/login'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders(),
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 419) {
                handleSessionExpired();
                return;
            }

            if (response.ok && data.success) {
                updateCsrfMeta(data.csrf_token);
                // Reload so every subsequent request uses a fresh meta + cookies.
                window.location.reload();
                return;
            }

            authMessage.textContent = data.message || 'パスワードが正しくありません';
        } catch (error) {
            authMessage.textContent = '認証中にエラーが発生しました';
        }
    });
});

function showAuthModal(message = '') {
    const authModal = document.getElementById('authModal');
    const authOverlay = document.querySelector('.auth-overlay');
    const authMessage = document.getElementById('authMessage');
    const mainContent = document.querySelector('.main-content');

    if (authModal) {
        authModal.style.display = 'flex';
        authModal.classList.add('is-open');
    }

    if (authOverlay) {
        authOverlay.classList.remove('authenticated');
    }

    if (mainContent) {
        mainContent.classList.add('blur-content');
    }

    document.body.classList.remove('is-authenticated');
    document.body.classList.add('is-guest');

    if (authMessage) {
        authMessage.textContent = message;
    }
}

function hideAuthModal() {
    const authModal = document.getElementById('authModal');
    const authOverlay = document.querySelector('.auth-overlay');
    const authMessage = document.getElementById('authMessage');
    const mainContent = document.querySelector('.main-content');
    const passwordInput = document.getElementById('password');

    if (authOverlay) {
        authOverlay.classList.add('authenticated');
    }

    setTimeout(() => {
        if (authModal) {
            authModal.style.display = 'none';
            authModal.classList.remove('is-open');
        }

        if (mainContent) {
            mainContent.classList.remove('blur-content');
        }
    }, 300);

    document.body.classList.add('is-authenticated');
    document.body.classList.remove('is-guest');

    if (authMessage) {
        authMessage.textContent = '';
    }

    if (passwordInput) {
        passwordInput.value = '';
    }
}

window.showAuthModal = showAuthModal;
window.hideAuthModal = hideAuthModal;
