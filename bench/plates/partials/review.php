<?php $this->layout('components/card', ['title' => $review->title, 'tone' => $review->verified ? 'verified' : 'plain']) ?>

<?php $this->insert('partials/rating', ['rating' => $review->rating]) ?>
<p class="byline">
    <?= $this->e($review->author) ?> ·
    <time datetime="<?= $review->date->format('Y-m-d') ?>"><?= $review->date->format('M j, Y') ?></time>
    <?php if ($review->verified): ?>
        · <span class="verified">Verified purchase</span>
    <?php endif ?>
</p>
<p><?= $this->e($review->body) ?></p>
