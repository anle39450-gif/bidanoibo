const dialog = document.getElementById('match-detail-dialog');

if (dialog && typeof dialog.showModal === 'function') {
    const matchPath = new URL(dialog.dataset.matchRoute, window.location.href).pathname.split('__match__')[0];
    const scrollArea = dialog.querySelector('[data-match-detail-scroll]');
    const content = dialog.querySelector('[data-match-detail-content]');
    const loading = dialog.querySelector('[data-match-detail-loading]');
    const error = dialog.querySelector('[data-match-detail-error]');
    const fullPage = dialog.querySelector('[data-full-match-page]');
    let pendingRequest;
    let ownsScrollLock = false;
    let opener;

    async function loadDetails() {
        pendingRequest?.abort();
        const request = new AbortController();
        pendingRequest = request;

        error.hidden = true;
        loading.hidden = false;
        content.replaceChildren();
        scrollArea.scrollTop = 0;

        const url = new URL(fullPage.href);
        url.searchParams.set('preview', '1');
        try {
            const response = await fetch(url, {
                headers: { Accept: 'text/html' },
                cache: 'no-store',
                signal: request.signal,
            });
            if (!response.ok || response.redirected) throw new Error();

            const preview = new DOMParser().parseFromString(await response.text(), 'text/html')
                .querySelector('[data-match-detail-fragment]');
            if (!preview) throw new Error();

            if (!request.signal.aborted) content.replaceChildren(preview);
        } catch {
            if (!request.signal.aborted) error.hidden = false;
        } finally {
            if (pendingRequest === request) {
                loading.hidden = true;
                pendingRequest = undefined;
            }
        }
    }

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('data-full-match-page') || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || !url.pathname.startsWith(matchPath) || !/^\d+\/?$/.test(url.pathname.slice(matchPath.length))) return;

        event.preventDefault();
        fullPage.href = url.href;
        if (!dialog.open) {
            opener = link;
            ownsScrollLock ||= !document.body.classList.contains('overflow-hidden');
            document.body.classList.add('overflow-hidden');
            dialog.showModal();
        }
        loadDetails();
    });

    dialog.querySelector('[data-match-detail-retry]').addEventListener('click', loadDetails);
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        if (dialog.open) return;
        pendingRequest?.abort();
        pendingRequest = undefined;
        if (ownsScrollLock) document.body.classList.remove('overflow-hidden');
        ownsScrollLock = false;
        if (opener?.isConnected) opener.focus({ preventScroll: true });
        opener = undefined;
    });
}
