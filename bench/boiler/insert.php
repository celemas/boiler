<aside class="sidebar">
    <h2><?= $title->trim() ?></h2>

    <div class="user-summary">
        <p>Logged in as: <?= $user['name'] ?></p>
        <p>Location: <?= $user['profile']['location'] ?></p>
        <p>Tier: <?= $user['tier']->trim()->upper() ?></p>
    </div>

    <div class="quick-stats">
        <p>Products: <?= $stats['totalProducts'] ?></p>
        <p>Orders: <?= $stats['totalOrders'] ?></p>
    </div>

    <div class="active-filters">
        <h3>Active filters</h3>
        <?php if (count($activeFilters->unwrap()) > 0): ?>
            <ul>
                <?php foreach ($activeFilters as $filter): ?>
                    <li><?= $filter['label'] ?>: <?= $filter['value']->trim()->upper() ?></li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <p>No filters selected.</p>
        <?php endif ?>
    </div>

    <div class="facets">
        <?php foreach ($facets as $facet): ?>
            <?php $this->include('facet-group', ['facet' => $facet]) ?>
        <?php endforeach ?>
    </div>
</aside>
