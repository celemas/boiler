<!DOCTYPE html>
<html lang="<?= $this->escape($site->locale) ?>">

<head>
    <meta charset="utf-8">
    <title><?= $this->yield('title', 'Welcome') ?> – <?= $this->escape($site->name) ?></title>
    <?= $this->yield('head', function () { ?>
        <link rel="stylesheet" href="/css/site.css">
    <?php }) ?>
</head>

<body class="<?= $this->escape($bodyClass ?? 'page') ?>">
    <div class="announcement"><?= $announcement ?></div>

    <header class="site-header">
        <a class="logo" href="/"><?= $this->escape($site->name) ?></a>
        <p class="tagline"><?= $this->escape($site->tagline) ?></p>

        <?php $this->include('partials/nav') ?>

        <div class="account">
            <?php if ($user): ?>
                <img src="<?= $this->escape($user->avatar) ?>" alt="" width="32" height="32">
                <span class="user">Hello, <?= $this->escape($user->name) ?></span>
                <span class="tier tier-<?= $this->escape($user->tier) ?>"><?= $this->escape(strtoupper($user->tier)) ?></span>
            <?php else: ?>
                <a href="/login">Sign in</a>
            <?php endif ?>
            <a class="cart" href="/cart"><?= $this->icon('cart') ?> Cart (<?= $cart['items'] ?>) · <?= $this->money($cart['subtotal']) ?></a>
        </div>
    </header>

    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <a href="<?= $this->escape($crumb['url']) ?>"><?= $this->escape($crumb['label']) ?></a>
        <?php endforeach ?>
    </nav>

    <?= $this->yield('hero', '') ?>

    <main>
        <?= $this->slot() ?>
    </main>

    <?php $this->include('partials/footer') ?>

    <script src="/js/site.js"></script>
    <?= $this->yield('scripts', '') ?>
</body>

</html>
