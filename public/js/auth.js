function getAppBaseUrl() {
    return document.querySelector('meta[name="app-url"]')?.content || '';
}

function appUrl(path = '') {
    const base = getAppBaseUrl().replace(/\/$/, '');
    const normalized = String(path || '');

    if (!normalized) {
        return base || '/';
    }

    if (/^https?:\/\//i.test(normalized)) {
        return normalized;
    }

    return `${base}/${normalized.replace(/^\//, '')}`;
}

window.appUrl = appUrl;

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
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                hideAuthModal();
                return;
            }

            authMessage.textContent = data.message || 'パスワードが正しくありません';
        } catch (error) {
            authMessage.textContent = '認証中にエラーが発生しました';
        }
    });
});

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

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
