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
                        <i class="fas fa-id-badge"></i>
                        <input type="text" id="employeeNumber" name="employee_number" class="form-control" placeholder="社員番号" required autocomplete="username" inputmode="numeric">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="パスワード" required autocomplete="current-password">
                    </div>
                </div>
                <p class="auth-hint">パスワードの初期値は社員番号です。</p>
                <div id="authMessage" class="auth-message">{{ session('auth_error') }}</div>
                <div class="form-group auth-buttons">
                    <button type="submit" class="btn btn-primary auth-btn">ログイン</button>
                </div>
            </form>
        </div>
    </div>
</div>
