<?php $flashMessage = take_flash(); ?>
<?php if ($flashMessage !== null): ?>
    <div class="alert alert-<?= e((string) $flashMessage['type']) ?> mb-4" role="alert">
        <?= e((string) $flashMessage['message']) ?>
    </div>
<?php endif; ?>
