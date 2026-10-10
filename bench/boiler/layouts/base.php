<!DOCTYPE html>
<html lang="<?= $site->locale ?>">

<head>
    <meta charset="utf-8">
    <title><?= $this->yield('title', 'Welcome') ?> – <?= $site->name ?></title>
    <?= $this->yield('head', function () { ?>
        <link rel="stylesheet" href="/css/site.css">
    <?php }) ?>
</head>

<body class="<?= $bodyClass ?? 'page' ?>">
    <div class="announcement"><?= $announcement ?></div>

    <header class="site-header">
        <a class="logo" href="/"><?= $site->name ?></a>
        <p class="tagline"><?= $site->tagline ?></p>

        <?php $this->include('partials/nav') ?>

        <div class="account">
            <?php if ($user): ?>
                <img src="<?= $user->avatar ?>" alt="" width="32" height="32">
                <span class="user">Hello, <?= $user->name ?></span>
                <span class="tier tier-<?= $user->tier ?>"><?= $user->tier->upper() ?></span>
            <?php else: ?>
                <a href="/login">Sign in</a>
            <?php endif ?>
            <a class="cart" href="/cart"><?= $this->icon('cart') ?> Cart (<?= $cart['items'] ?>) · <?= $this->money($cart['subtotal']) ?></a>
        </div>
    </header>

    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <a href="<?= $crumb['url'] ?>"><?= $crumb['label'] ?></a>
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
