<p class="rating" aria-label="<?= $rating ?> out of 5 stars">
    <?php for ($star = 1; $star <= 5; $star++): ?>
        <?= $star <= $rating ? '★' : '☆' ?>
    <?php endfor ?>
    <?php if (isset($count)): ?>
        <span class="rating-count">(<?= $count ?>)</span>
    <?php endif ?>
</p>
