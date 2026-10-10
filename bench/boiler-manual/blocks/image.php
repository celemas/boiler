<figure class="block-image">
    <img src="<?= $this->escape($block['src']) ?>" alt="<?= $this->escape($block['alt']) ?>" width="<?= $block['width'] ?>" height="<?= $block['height'] ?>" loading="lazy">
    <figcaption>
        <?= $this->escape($block['caption']) ?>
        <?php if (isset($block['credit'])): ?>
            <small>Photo: <?= $this->escape($block['credit']) ?></small>
        <?php endif ?>
    </figcaption>
</figure>
