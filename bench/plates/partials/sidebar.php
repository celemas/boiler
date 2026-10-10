<nav class="category-tree" aria-label="Categories">
    <h2>Categories</h2>
    <ul>
        <?php foreach ($categories as $entry): ?>
            <li class="<?= $entry['current'] ? 'is-current' : 'is-other' ?>">
                <a href="<?= $this->e($entry['url']) ?>"><?= $this->e($entry['name']) ?></a>
                <small>(<?= $entry['count'] ?>)</small>
            </li>
        <?php endforeach ?>
    </ul>
</nav>

<?php $this->insert('partials/cart', ['cart' => $cart, 'campaign' => $campaign]) ?>
<?php $this->insert('partials/support') ?>
