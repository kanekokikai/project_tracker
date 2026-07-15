<div id="attachment-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="visibility: hidden;">添付ファイル</h2>
            <span class="close" data-close-attachment-modal>&times;</span>
        </div>
        <div class="modal-body">
            <div class="upload-section">
                <h3>ファイルをアップロード</h3>
                <div id="drop-area" class="drop-area">
                    <form id="file-upload-form" enctype="multipart/form-data">
                        <input type="hidden" id="project-id-input" name="project_id">
                        <div class="file-input-container">
                            <input type="file" id="file-input" name="file" class="file-input">
                            <p>ファイルをドラッグ＆ドロップするか、<label for="file-input" class="file-input-label">ファイルを選択</label></p>
                            <p id="selected-file-name" class="selected-file">選択されていません</p>
                        </div>
                        <button type="submit" id="upload-btn" class="upload-button" disabled>アップロード</button>
                    </form>
                    <div id="upload-progress" class="progress-bar-container" style="display: none;">
                        <div class="progress-bar"></div>
                    </div>
                    <div id="upload-message"></div>
                </div>
            </div>

            <div class="attachments-list-section">
                <h3>ファイル一覧</h3>
                <div id="attachments-container">
                    <p class="no-attachments">添付ファイルはありません</p>
                    <table id="attachments-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>ファイル名</th>
                                <th>サイズ</th>
                                <th>アップロード日</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="attachments-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="delete-confirm-modal" class="modal" style="display: none;">
    <div class="modal-content delete-confirm-content">
        <div class="modal-header">
            <h2>削除の確認</h2>
            <span class="close" data-close-attachment-modal>&times;</span>
        </div>
        <div class="modal-body">
            <p>この添付ファイルを削除してもよろしいですか？</p>
            <p id="delete-file-name"></p>
            <div class="button-container">
                <button id="cancel-delete" class="cancel-button" type="button">キャンセル</button>
                <button id="confirm-delete" class="delete-button" type="button">削除</button>
            </div>
        </div>
    </div>
</div>
