<?php
declare(strict_types=1);

$eventDescription = trim((string) $event['description']);
?>
<article class="feature-card event-card h-100">
    <?php if ($event['poster_filename'] !== null): ?>
        <img class="event-poster-preview mb-3" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>">
    <?php endif; ?>
    <p class="eyebrow mb-2"><?= e(format_event_date((string) $event['starts_at'])) ?></p>
    <h2 class="h4"><?= e((string) $event['title']) ?></h2>
    <p class="text-muted mb-2"><?= e((string) $event['venue']) ?></p>
    <?php if ($eventDescription !== ''): ?>
        <p><?= e(mb_strlen($eventDescription) > 180 ? mb_substr($eventDescription, 0, 177) . '...' : $eventDescription) ?></p>
    <?php endif; ?>
    <a class="btn btn-outline-primary mt-auto" href="<?= e(app_url('/event.php?id=' . (int) $event['id'])) ?>">Event details</a>
</article>
