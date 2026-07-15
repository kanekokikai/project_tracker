@extends('layouts.app')

@section('title', 'Chatwork TO設定')
@section('header_title', 'Chatwork TO設定')

@section('content')
<div class="admin-page">
    <div class="admin-header">
        <div>
            <h1>TO対象メンバーの管理</h1>
            <p>プロジェクトのチームメンバー名と、ChatworkのアカウントIDの対応を編集します。</p>
        </div>
        <div class="admin-actions">
            <button type="button" class="btn" id="fetchRoomMembersBtn">
                <i class="fas fa-sync-alt"></i> ルームメンバー取得
            </button>
        </div>
    </div>

    <div id="statusMessage" class="status-message"></div>

    <div class="admin-card">
        <h2>メンバーを追加</h2>
        <form id="addMemberForm" class="member-form-grid">
            <div class="form-group">
                <label for="newMemberName">システム上の名前</label>
                <input type="text" id="newMemberName" class="form-control" placeholder="例: 堀内" required>
            </div>
            <div class="form-group">
                <label for="newAccountId">ChatworkアカウントID</label>
                <input type="text" id="newAccountId" class="form-control" placeholder="例: 1406764" pattern="\d+" required>
            </div>
            <div class="form-group">
                <label for="newNote">メモ（任意）</label>
                <input type="text" id="newNote" class="form-control" placeholder="例: 営業">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">追加</button>
            </div>
        </form>
        <p class="help-text">チームメンバーに入力する名前と一致させる必要があります。通知時に <code>[To:アカウントID]</code> が付きます。</p>
    </div>

    <div class="admin-card" id="roomMembersCard" style="display:none;">
        <h2>Chatworkルームメンバー</h2>
        <p class="help-text" style="margin-top:0;margin-bottom:0.75rem;">クリックでアカウントIDを追加フォームにセットできます。</p>
        <div id="roomMembersList" class="room-members-list"></div>
    </div>

    <div class="admin-card">
        <h2>登録済みメンバー</h2>
        <div id="membersTableWrap">
            <table class="members-table">
                <thead>
                    <tr>
                        <th style="width:24%">システム上の名前</th>
                        <th style="width:24%">ChatworkアカウントID</th>
                        <th class="hide-mobile" style="width:28%">メモ</th>
                        <th style="width:24%">操作</th>
                    </tr>
                </thead>
                <tbody id="membersTableBody">
                    <tr><td colspan="4" class="empty-state">読み込み中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const membersApi = appUrl('/chatwork/members');
    const roomMembersApi = appUrl('/chatwork/room-members');

    function setStatus(message, type = '') {
        const el = document.getElementById('statusMessage');
        el.textContent = message || '';
        el.className = 'status-message' + (type ? ' ' + type : '');
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function apiJson(url, options = {}) {
        const response = await fetch(url, {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: csrfHeaders(options.body ? { 'Content-Type': 'application/json' } : {}),
            body: options.body ? JSON.stringify(options.body) : undefined,
        });

        if (response.status === 419) {
            handleSessionExpired();
            throw new Error('セッションの有効期限が切れました');
        }

        const data = await response.json().catch(() => ({}));

        if (response.status === 401 && typeof window.showAuthModal === 'function') {
            window.showAuthModal(data.message || 'ログインが必要です');
            throw new Error(data.message || '認証が必要です');
        }

        if (!response.ok || data.success === false) {
            const message = data.message
                || (data.errors ? Object.values(data.errors).flat().join('\n') : '')
                || '処理に失敗しました';
            throw new Error(message);
        }

        return data;
    }

    function renderMembers(members) {
        const tbody = document.getElementById('membersTableBody');

        if (!members.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-state">メンバーが登録されていません</td></tr>';
            return;
        }

        tbody.innerHTML = members.map((member) => `
            <tr data-id="${member.id}">
                <td><input type="text" class="field-name" value="${escapeHtml(member.member_name)}"></td>
                <td><input type="text" class="field-account" value="${escapeHtml(member.chatwork_account_id)}" pattern="\\d+"></td>
                <td class="hide-mobile"><input type="text" class="field-note" value="${escapeHtml(member.note || '')}"></td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="btn btn-primary btn-sm save-btn">保存</button>
                        <button type="button" class="btn btn-danger btn-sm delete-btn">削除</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function loadMembers() {
        const data = await apiJson(membersApi);
        renderMembers(data.members || []);
    }

    document.getElementById('addMemberForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            setStatus('保存中...');
            await apiJson(membersApi, {
                method: 'POST',
                body: {
                    member_name: document.getElementById('newMemberName').value.trim(),
                    chatwork_account_id: document.getElementById('newAccountId').value.trim(),
                    note: document.getElementById('newNote').value.trim(),
                },
            });
            event.target.reset();
            setStatus('メンバーを追加しました', 'success');
            await loadMembers();
        } catch (error) {
            setStatus(error.message, 'error');
        }
    });

    document.getElementById('membersTableBody').addEventListener('click', async (event) => {
        const row = event.target.closest('tr[data-id]');
        if (!row) {
            return;
        }

        const id = parseInt(row.dataset.id, 10);

        if (event.target.classList.contains('save-btn')) {
            try {
                setStatus('保存中...');
                await apiJson(membersApi, {
                    method: 'POST',
                    body: {
                        id,
                        member_name: row.querySelector('.field-name').value.trim(),
                        chatwork_account_id: row.querySelector('.field-account').value.trim(),
                        note: row.querySelector('.field-note')?.value.trim() || '',
                    },
                });
                setStatus('保存しました', 'success');
                await loadMembers();
            } catch (error) {
                setStatus(error.message, 'error');
            }
        }

        if (event.target.classList.contains('delete-btn')) {
            if (!confirm('このメンバーを削除しますか？')) {
                return;
            }

            try {
                setStatus('削除中...');
                await apiJson(appUrl(`/chatwork/members/${id}`), { method: 'DELETE' });
                setStatus('削除しました', 'success');
                await loadMembers();
            } catch (error) {
                setStatus(error.message, 'error');
            }
        }
    });

    document.getElementById('fetchRoomMembersBtn').addEventListener('click', async function () {
        try {
            this.disabled = true;
            setStatus('ルームメンバーを取得中...');
            const data = await apiJson(roomMembersApi);
            const list = document.getElementById('roomMembersList');
            const members = data.members || [];

            if (!members.length) {
                list.innerHTML = '<div class="empty-state">メンバーが見つかりませんでした</div>';
            } else {
                list.innerHTML = members.map((member) => `
                    <button type="button" class="room-member-item" data-account-id="${member.account_id}" data-name="${escapeHtml(member.name || '')}">
                        <span>
                            <strong>${escapeHtml(member.name || '（無名）')}</strong><br>
                            <span class="meta">ID: ${member.account_id}</span>
                        </span>
                        <span class="btn btn-sm">選択</span>
                    </button>
                `).join('');
            }

            document.getElementById('roomMembersCard').style.display = 'block';
            setStatus(`${members.length}名のルームメンバーを取得しました`, 'success');
        } catch (error) {
            setStatus(error.message, 'error');
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('roomMembersList').addEventListener('click', (event) => {
        const item = event.target.closest('.room-member-item');
        if (!item) {
            return;
        }

        document.getElementById('newAccountId').value = item.dataset.accountId;
        if (!document.getElementById('newMemberName').value) {
            document.getElementById('newMemberName').value = item.dataset.name || '';
        }
        document.getElementById('newMemberName').focus();
        setStatus(`アカウントID ${item.dataset.accountId} をセットしました`, 'success');
    });

    loadMembers().catch((error) => setStatus(error.message, 'error'));
});
</script>
@endpush
