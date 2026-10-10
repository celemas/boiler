<?php $this->layout('layouts/shop', ['title' => $category['name']] + $this->data()) ?>

<?php $this->push('head') ?>
<meta name="description" content="<?= $this->e($category['description']) ?>">
<link rel="canonical" href="<?= $this->e($category['url']) ?>">
<?php $this->stop() ?>

<?php $this->start('filters') ?>
<?php $this->insert('partials/facets', ['category' => $category, 'facets' => $facets]) ?>
<?php $this->stop() ?>

<section class="listing">
    <header>
        <h1><?= $this->e($category['name']) ?></h1>
        <p class="lead"><?= $this->e($category['description']) ?></p>
        <p class="count">Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= $pagination['total'] ?> products</p>
    </header>

    <div class="toolbar">
        <?php if ($activeFilters): ?>
            <ul class="active-filters">
                <?php foreach ($activeFilters as $filter): ?>
                    <li>
                        <?= $this->e($filter['label']) ?>: <strong><?= $this->e($filter['value']) ?></strong>
                        <a href="<?= $this->e($filter['remove']) ?>" aria-label="Remove filter <?= $this->e($filter['label']) ?>">×</a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <p class="active-filters">No filters selected.</p>
        <?php endif ?>

        <label>
            Sort by
            <select name="sort">
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?= $this->e($value) ?>"<?= $value === $sort ? ' selected' : '' ?>><?= $this->e($label) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    </div>

    <div class="grid">
        <?php foreach ($products as $product): ?>
            <?php $this->insert('partials/product-card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>

    <?php $this->insert('partials/pagination', ['pagination' => $pagination]) ?>
</section>

<?php $this->push('scripts') ?>
<script src="/js/listing.js" data-category="<?= $this->e($category['slug']) ?>" data-page="<?= $pagination['page'] ?>"></script>
<?php $this->stop() ?>
