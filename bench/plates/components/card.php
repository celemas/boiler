<article class="card card-<?= $this->e($tone) ?>">
    <h3 class="card-title">
        <?php if (isset($href)): ?>
            <a href="<?= $this->e($href) ?>"><?= $this->e($title) ?></a>
        <?php else: ?>
            <?= $this->e($title) ?>
        <?php endif ?>
    </h3>
    <div class="card-body">
        <?= $this->section('content') ?>
    </div>
</article>
