document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initSidebar, 300);
    navigateToProjectFromUrl();
});

function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarPin = document.querySelector('.sidebar-pin');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const projectNav = document.querySelector('.project-nav');

    if (!sidebarToggle || !sidebar || !sidebarPin || !sidebarOverlay || !projectNav) {
        return;
    }

    function syncToggleVisibility() {
        const isOpen = sidebar.classList.contains('active') || sidebar.classList.contains('pinned');
        sidebarToggle.classList.toggle('is-hidden', isOpen);
        sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function closeSidebar() {
        if (sidebar.classList.contains('pinned')) {
            return;
        }

        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
        syncToggleVisibility();
    }

    function generateProjectList() {
        projectNav.innerHTML = '';

        document.querySelectorAll('.parent-project').forEach((project) => {
            if (project.style.display === 'none') {
                return;
            }

            const projectTitle = project.querySelector(':scope > .project-header .project-name');
            if (!projectTitle) {
                return;
            }

            const nameNode = Array.from(projectTitle.childNodes).find(
                (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== ''
            );
            const projectName = (nameNode?.textContent || projectTitle.textContent || '').trim();
            const projectId = project.dataset.projectId;
            const projectStatus = project.dataset.status || '';

            if (!projectId || !projectName) {
                return;
            }

            const listItem = document.createElement('li');
            listItem.className = 'project-nav-item';
            listItem.dataset.projectId = projectId;
            listItem.title = projectName;
            listItem.innerHTML =
                `<span class="project-nav-status status-${projectStatus}" aria-hidden="true"></span>` +
                '<span class="project-nav-name"></span>' +
                '<i class="fas fa-chevron-right project-nav-arrow" aria-hidden="true"></i>';
            listItem.querySelector('.project-nav-name').textContent = projectName;

            listItem.addEventListener('click', () => {
                const targetProject = document.querySelector(`.project-card[data-project-id="${projectId}"]`);
                if (!targetProject) {
                    return;
                }

                const headerHeight = document.querySelector('.header')?.offsetHeight || 0;
                const top = targetProject.getBoundingClientRect().top + window.scrollY - headerHeight - 20;

                window.scrollTo({ top, behavior: 'smooth' });

                if (!sidebar.classList.contains('pinned')) {
                    closeSidebar();
                }

                document.querySelectorAll('.project-nav-item').forEach((item) => {
                    item.classList.remove('active');
                });
                listItem.classList.add('active');

                targetProject.classList.add('highlight-project');
                setTimeout(() => {
                    targetProject.classList.remove('highlight-project');
                }, 2000);
            });

            projectNav.appendChild(listItem);
        });
    }

    window.refreshSidebarProjectList = generateProjectList;

    const isPinned = localStorage.getItem('sidebarPinned') === 'true';

    if (isPinned) {
        sidebar.classList.add('pinned', 'active');
        sidebarPin.classList.add('active');
        generateProjectList();
    }

    syncToggleVisibility();

    sidebarToggle.addEventListener('click', () => {
        if (sidebar.classList.contains('pinned')) {
            return;
        }

        sidebar.classList.add('active');
        sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        syncToggleVisibility();
        generateProjectList();
    });

    sidebarPin.addEventListener('click', () => {
        sidebar.classList.toggle('pinned');
        sidebarPin.classList.toggle('active');

        if (sidebar.classList.contains('pinned')) {
            localStorage.setItem('sidebarPinned', 'true');
            sidebar.classList.add('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
            generateProjectList();
        } else {
            localStorage.setItem('sidebarPinned', 'false');
            sidebar.classList.remove('active');
        }

        syncToggleVisibility();
    });

    sidebarOverlay.addEventListener('click', closeSidebar);
}

function navigateToProjectFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    let projectId = urlParams.get('project_id');

    if (!projectId && window.location.hash.startsWith('#project-')) {
        projectId = window.location.hash.replace('#project-', '');
    }

    if (!projectId) {
        return;
    }

    setTimeout(() => {
        const targetProject = document.querySelector(`.project-card[data-project-id="${projectId}"]`);
        if (!targetProject) {
            return;
        }

        const headerHeight = document.querySelector('.header')?.offsetHeight || 0;
        const top = targetProject.getBoundingClientRect().top + window.scrollY - headerHeight - 20;

        window.scrollTo({ top, behavior: 'smooth' });

        if (typeof expandChildProjectHistory === 'function') {
            expandChildProjectHistory(projectId);
        }

        targetProject.classList.add('highlight-project');
        setTimeout(() => {
            targetProject.classList.remove('highlight-project');
        }, 2000);
    }, 400);
}
