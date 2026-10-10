<!DOCTYPE html>
<html lang="<?= $this->e($site->locale) ?>">

<head>
    <meta charset="utf-8">
    <title><?= $this->e($title ?? 'Welcome') ?> – <?= $this->e($site->name) ?></title>
    <link rel="stylesheet" href="/css/site.css">
    <?= $this->section('head') ?>
</head>

<body class="<?= $this->e($bodyClass ?? 'page') ?>">
    <div class="announcement"><?= $announcement ?></div>

    <header class="site-header">
        <a class="logo" href="/"><?= $this->e($site->name) ?></a>
        <p class="tagline"><?= $this->e($site->tagline) ?></p>

        <?php $this->insert('partials/nav') ?>

        <div class="account">
            <?php if ($user): ?>
                <img src="<?= $this->e($user->avatar) ?>" alt="" width="32" height="32">
                <span class="user">Hello, <?= $this->e($user->name) ?></span>
                <span class="tier tier-<?= $this->e($user->tier) ?>"><?= $this->e($user->tier, 'strtoupper') ?></span>
            <?php else: ?>
                <a href="/login">Sign in</a>
            <?php endif ?>
            <a class="cart" href="/cart"><?= $this->icon('cart') ?> Cart (<?= $cart['items'] ?>) · <?= $this->money($cart['subtotal']) ?></a>
        </div>
    </header>

    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <a href="<?= $this->e($crumb['url']) ?>"><?= $this->e($crumb['label']) ?></a>
        <?php endforeach ?>
    </nav>

    <?= $this->section('hero') ?>

    <main>
        <?= $this->section('content') ?>
    </main>

    <?php $this->insert('partials/footer') ?>

    <script src="/js/site.js"></script>
    <?= $this->section('scripts') ?>
</body>

</html>
