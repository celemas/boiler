<nav class="pagination" aria-label="Pagination">
    <?php if ($pagination['page'] > 1): ?>
        <a rel="prev" href="<?= $pagination['url'] ?><?= $pagination['page'] - 1 ?>">Previous</a>
    <?php endif ?>
    <?php for ($page = 1; $page <= $pagination['pages']; $page++): ?>
        <?php if ($page === $pagination['page']): ?>
            <span class="current" aria-current="page"><?= $page ?></span>
        <?php else: ?>
            <a href="<?= $pagination['url'] ?><?= $page ?>"><?= $page ?></a>
        <?php endif ?>
    <?php endfor ?>
    <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a rel="next" href="<?= $pagination['url'] ?><?= $pagination['page'] + 1 ?>">Next</a>
    <?php endif ?>
</nav>
