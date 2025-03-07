</main>
    <script src="js/main.js"></script>
    <!-- 履歴トグル機能用スクリプト -->
    <script>
    function toggleHistory(projectId) {
        const historyContent = document.getElementById(`history-content-${projectId}`);
        const toggleButton = document.querySelector(`.toggle-history[data-project-id="${projectId}"]`);
        
        if (historyContent) {
            if (historyContent.classList.contains('collapsed')) {
                historyContent.classList.remove('collapsed');
                toggleButton.textContent = '▼';
            } else {
                historyContent.classList.add('collapsed');
                toggleButton.textContent = '▶';
            }
        }
    }
    </script>
</body>
</html>