<figure class="block-image">
    <img src="<?= $this->e($block['src']) ?>" alt="<?= $this->e($block['alt']) ?>" width="<?= $block['width'] ?>" height="<?= $block['height'] ?>" loading="lazy">
    <figcaption>
        <?= $this->e($block['caption']) ?>
        <?php if (isset($block['credit'])): ?>
            <small>Photo: <?= $this->e($block['credit']) ?></small>
        <?php endif ?>
    </figcaption>
</figure>
