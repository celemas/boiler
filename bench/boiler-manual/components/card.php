<article class="card card-<?= $this->escape($tone) ?>">
    <h3 class="card-title">
        <?php if (isset($href)): ?>
            <a href="<?= $this->escape($href) ?>"><?= $this->escape($title) ?></a>
        <?php else: ?>
            <?= $this->escape($title) ?>
        <?php endif ?>
    </h3>
    <div class="card-body">
        <?= $this->slot() ?>
    </div>
</article>
