document.addEventListener('DOMContentLoaded', () => {
    const posterDialog = document.querySelector('[data-poster-dialog]');
    const posterOpen = document.querySelector('[data-poster-open]');
    if (posterDialog instanceof HTMLDialogElement && posterOpen instanceof HTMLButtonElement) {
        const closeButtons = posterDialog.querySelectorAll('[data-poster-close]');
        posterOpen.addEventListener('click', () => posterDialog.showModal());
        closeButtons.forEach((button) => {
            button.addEventListener('click', () => posterDialog.close());
        });
        posterDialog.addEventListener('click', (event) => {
            if (event.target === posterDialog) {
                posterDialog.close();
            }
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(isPassword));
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });
});
