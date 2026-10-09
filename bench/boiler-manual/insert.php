<aside class="sidebar">
    <h2><?= $this->escape(trim($title)) ?></h2>

    <div class="user-summary">
        <p>Logged in as: <?= $this->escape($user['name']) ?></p>
        <p>Location: <?= $this->escape($user['profile']['location']) ?></p>
        <p>Tier: <?= $this->escape($this->wrap($user['tier'])->trim()->upper()) ?></p>
    </div>

    <div class="quick-stats">
        <p>Products: <?= $stats['totalProducts'] ?></p>
        <p>Orders: <?= $stats['totalOrders'] ?></p>
    </div>

    <div class="active-filters">
        <h3>Active filters</h3>
        <?php if (count($activeFilters) > 0): ?>
            <ul>
                <?php foreach ($activeFilters as $filter): ?>
                    <li><?= $this->escape($filter['label']) ?>: <?= $this->escape($this->wrap($filter['value'])->trim()->upper()) ?></li>
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
