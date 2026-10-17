<?php
declare(strict_types=1);

$eventDescription = trim((string) $event['description']);
?>
<article class="feature-card event-card h-100">
    <?php if ($event['poster_filename'] !== null): ?>
        <?php $posterReturn = '/' . basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'events.php')); ?>
        <a class="poster-launch mb-3" href="<?= e(app_url('/view_event_poster.php?id=' . (int) $event['id'] . '&return=' . rawurlencode($posterReturn))) ?>" data-poster-open data-poster-src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" data-poster-title="<?= e((string) $event['title']) ?>">
            <img class="event-poster-preview" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>. Open to view, then use Back or X to return.">
        </a>
    <?php endif; ?>
    <p class="eyebrow mb-2"><?= e(format_event_date((string) $event['starts_at'])) ?></p>
    <h2 class="h4"><?= e((string) $event['title']) ?></h2>
    <p class="text-muted mb-2"><?= e((string) $event['venue']) ?></p>
    <?php if ($eventDescription !== ''): ?>
        <p><?= e(mb_strlen($eventDescription) > 180 ? mb_substr($eventDescription, 0, 177) . '...' : $eventDescription) ?></p>
    <?php endif; ?>
    <a class="btn btn-outline-primary mt-auto" href="<?= e(app_url('/event.php?id=' . (int) $event['id'])) ?>">Event details</a>
</article>
