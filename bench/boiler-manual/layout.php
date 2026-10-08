<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= $this->escape($this->wrap($title)->trim()) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <?= $this->yield('head', '') ?>
    <?= $this->yield('script', '') ?>
</head>

<body id="catalog">
    <header>
        <nav>
            <?php if ($isLoggedIn): ?>
                <span>Welcome, <?= $this->escape($user['name']) ?></span>
                <span class="tier">Tier: <?= $this->escape($this->wrap($user['tier'])->trim()->upper()) ?></span>
                <?php if ($isAdmin): ?>
                    <a href="/admin">Admin Panel</a>
                <?php endif ?>
            <?php else: ?>
                <a href="/login">Login</a>
            <?php endif ?>
        </nav>

        <div class="breadcrumbs">
            <?php foreach ($breadcrumbs as $crumb): ?>
                <a href="<?= $this->escape($crumb['url']) ?>"><?= $this->escape($crumb['label']) ?></a>
                <span>/</span>
            <?php endforeach ?>
        </div>

        <ul class="top-categories">
            <?php foreach ($topCategories as $category): ?>
                <li>
                    <a href="<?= $this->escape($category['url']) ?>"><?= $this->escape($this->wrap($category['label'])->trim()->upper()) ?></a>
                    <?php if (count($category['children']) > 0): ?>
                        <ul>
                            <?php foreach ($category['children'] as $child): ?>
                                <li><a href="<?= $this->escape($child['url']) ?>"><?= $this->escape(trim($child['label'])) ?></a></li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    </header>

    <main>
        <?= $this->slot() ?>
    </main>

    <footer>
        <p>Total Products: <?= $stats['totalProducts'] ?> · Orders: <?= $stats['totalOrders'] ?> · Open: <?= $stats['openOrders'] ?></p>
        <p>Store: <?= $this->escape($store->name) ?> · Support: <?= $this->escape($store->support->email) ?> · Timezone: <?= $this->escape($store->support->timezone) ?></p>
        <?php if ($stats['revenue'] > 90000): ?>
            <p class="kpi">Revenue YTD: $<?= number_format($stats['revenue'], 2) ?> · Conversion: <?= number_format($stats['conversionRate'], 1) ?>%</p>
        <?php endif ?>
    </footer>
</body>

</html>
