<div class="auth-overlay {{ $isAuthenticated ? 'authenticated' : '' }}"></div>

<div id="authModal" class="modal" style="display: {{ $isAuthenticated ? 'none' : 'flex' }};">
    <div class="modal-content">
        <div class="auth-header">
            <h2>ログイン</h2>
        </div>
        <div class="auth-body">
            <form id="authForm">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="パスワードを入力" required autocomplete="current-password">
                    </div>
                </div>
                <div id="authMessage" class="auth-message"></div>
                <div class="form-group auth-buttons">
                    <button type="submit" class="btn btn-primary auth-btn">ログイン</button>
                </div>
            </form>
        </div>
    </div>
</div>
