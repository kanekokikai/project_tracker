document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('backToTop');

    if (!button) {
        return;
    }

    const SHOW_AFTER = Math.min(window.innerHeight * 0.8, 480);
    const preferReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let ticking = false;

    function isModalOpen() {
        if (document.querySelector('.modal.is-open')) {
            return true;
        }

        const authModal = document.getElementById('authModal');

        return Boolean(
            authModal
            && document.body.classList.contains('is-guest')
            && getComputedStyle(authModal).display !== 'none'
        );
    }

    function updateVisibility() {
        const shouldShow = window.scrollY > SHOW_AFTER && !isModalOpen();

        button.classList.toggle('is-visible', shouldShow);
        button.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
        button.tabIndex = shouldShow ? 0 : -1;
    }

    function onScroll() {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(() => {
            updateVisibility();
            ticking = false;
        });
    }

    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: preferReducedMotion.matches ? 'auto' : 'smooth',
        });
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });

    const modalObserver = new MutationObserver(updateVisibility);
    document.querySelectorAll('.modal').forEach((modal) => {
        modalObserver.observe(modal, {
            attributes: true,
            attributeFilter: ['class', 'style'],
        });
    });

    updateVisibility();
});
