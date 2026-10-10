<article class="card card-<?= $tone ?>">
    <h3 class="card-title">
        <?php if (isset($href)): ?>
            <a href="<?= $href ?>"><?= $title ?></a>
        <?php else: ?>
            <?= $title ?>
        <?php endif ?>
    </h3>
    <div class="card-body">
        <?= $this->slot() ?>
    </div>
</article>
