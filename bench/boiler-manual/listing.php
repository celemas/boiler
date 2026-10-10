<?php $this->layout('layouts/shop') ?>

<?php $this->section('title') ?><?= $this->escape($category['name']) ?><?php $this->end() ?>

<?php $this->append('head') ?>
<meta name="description" content="<?= $this->escape($category['description']) ?>">
<link rel="canonical" href="<?= $this->escape($category['url']) ?>">
<?php $this->end() ?>

<?php $this->prepend('sidebar') ?>
<?php $this->include('partials/facets') ?>
<?php $this->end() ?>

<section class="listing">
    <header>
        <h1><?= $this->escape($category['name']) ?></h1>
        <p class="lead"><?= $this->escape($category['description']) ?></p>
        <p class="count">Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= $pagination['total'] ?> products</p>
    </header>

    <div class="toolbar">
        <?php if ($activeFilters): ?>
            <ul class="active-filters">
                <?php foreach ($activeFilters as $filter): ?>
                    <li>
                        <?= $this->escape($filter['label']) ?>: <strong><?= $this->escape($filter['value']) ?></strong>
                        <a href="<?= $this->escape($filter['remove']) ?>" aria-label="Remove filter <?= $this->escape($filter['label']) ?>">×</a>
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
                    <option value="<?= $this->escape($value) ?>"<?= $value === $sort ? ' selected' : '' ?>><?= $this->escape($label) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    </div>

    <div class="grid">
        <?php foreach ($products as $product): ?>
            <?php $this->include('partials/product-card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>

    <?php $this->include('partials/pagination') ?>
</section>

<?php $this->append('scripts') ?>
<script src="/js/listing.js" data-category="<?= $this->escape($category['slug']) ?>" data-page="<?= $pagination['page'] ?>"></script>
<?php $this->end() ?>
