/**
 * Polls a running audit and refreshes the page when it finishes.
 */
function watchAuditProgress(container) {
    const url = container.dataset.auditStatusUrl;
    const bar = container.querySelector('[data-progress-bar]');
    const text = container.querySelector('[data-progress-text]');

    const poll = async () => {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const audit = await response.json();

            bar.style.width = `${audit.progress}%`;
            text.textContent = audit.progress;

            if (audit.finished) {
                window.location.reload();
                return;
            }
        } catch {
            // A failed poll is retried on the next tick.
        }

        setTimeout(poll, 1000);
    };

    setTimeout(poll, 1000);
}

document.querySelectorAll('[data-audit-status-url]').forEach(watchAuditProgress);

document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
