<?php
declare(strict_types=1);

$layout = seating_box_layout($section);
if ($layout['has_seats'] === false) {
    echo '<p class="text-muted mb-0">No seats have been placed in this section yet.</p>';
    return;
}
?>
<div class="seat-box-board">
    <div class="seat-stage-label">Stage</div>
    <div class="seat-box-grid">
        <?php foreach ($layout['rows'] as $gridRow): ?>
            <div class="seat-box-row" style="--seat-columns: <?= (int) $layout['columns'] ?>">
                <span class="seat-row-label"><?= e($gridRow['label']) ?></span>
                <?php foreach ($gridRow['cells'] as $cell): ?>
                    <?php if ($cell === null): ?>
                        <div class="seat-box seat-box-empty" aria-hidden="true"></div>
                    <?php else: ?>
                        <?php
                        $meta = seat_status_meta((string) $cell['status']);
                        $price = $cell['price_override'] ?? $section['default_price'];
                        $description = $gridRow['label'] . ' ' . $cell['label'] . ', ' . $meta['payment']
                            . ', PHP ' . number_format((float) $price, 2);
                        ?>
                        <div class="seat-box <?= e($meta['class']) ?>" role="img" aria-label="<?= e($description) ?>" title="<?= e($description) ?>">
                            <span class="seat-box-label"><?= e((string) $cell['label']) ?></span>
                            <span class="seat-box-meta"><?= e($meta['short']) ?></span>
                            <span class="seat-box-price">₱<?= e(number_format((float) $price, 2)) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
