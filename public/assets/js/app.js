document.addEventListener('DOMContentLoaded', () => {
    const posterDialog = document.createElement('dialog');
    posterDialog.className = 'poster-preview-dialog';
    posterDialog.setAttribute('aria-labelledby', 'poster-viewer-title');
    posterDialog.innerHTML = [
        '<div class="poster-preview-header">',
        '<h2 class="h5 mb-0" id="poster-viewer-title">Event poster</h2>',
        '<button class="poster-close-button" type="button" data-poster-close aria-label="Close poster">×</button>',
        '</div>',
        '<img class="poster-preview-image" alt="">',
        '<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3">',
        '<p class="text-muted small mb-0">Use Back, X, or Escape to return.</p>',
        '<button class="btn btn-secondary" type="button" data-poster-close>Back</button>',
        '</div>',
    ].join('');
    document.body.append(posterDialog);
    const posterTitle = posterDialog.querySelector('#poster-viewer-title');
    const posterImage = posterDialog.querySelector('img');
    const closePoster = () => {
        posterDialog.close();
    };
    posterDialog.querySelectorAll('[data-poster-close]').forEach((button) => {
        button.addEventListener('click', closePoster);
    });
    posterDialog.addEventListener('click', (event) => {
        if (event.target === posterDialog) {
            closePoster();
        }
    });
    posterDialog.addEventListener('close', () => {
        if (posterDialog.returnFocus instanceof HTMLElement) {
            posterDialog.returnFocus.focus();
        }
    });

    document.querySelectorAll('[data-poster-open]').forEach((opener) => {
        opener.addEventListener('click', (event) => {
            const src = opener.getAttribute('data-poster-src');
            if (!src || !(posterTitle instanceof HTMLElement) || !(posterImage instanceof HTMLImageElement)) {
                return;
            }
            event.preventDefault();
            const title = opener.getAttribute('data-poster-title') || 'Event poster';
            posterTitle.textContent = title;
            posterImage.src = src;
            posterImage.alt = `Poster for ${title}`;
            posterDialog.returnFocus = opener;
            posterDialog.showModal();
            posterDialog.querySelector('[data-poster-close]')?.focus();
        });
    });

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
