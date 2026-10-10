<?php $this->layout('layouts/base', [
    'title' => ($title ?? 'All products') . ' | Shop',
    'bodyClass' => 'page-shop',
] + $this->data()) ?>

<?php $this->unshift('head') ?>
<link rel="stylesheet" href="/css/shop.css">
<?php $this->stop() ?>

<?php if ($promo ?? true): ?>
    <?php $this->start('hero') ?>
    <section class="promo">
        <h2><?= $this->e($campaign['title'], 'strtoupper') ?></h2>
        <p>
            Use code <strong><?= $this->e($campaign['code']) ?></strong>
            for free shipping above <?= $this->money($campaign['threshold']) ?>.
        </p>
        <p>Ends <time datetime="<?= $campaign['endsAt']->format('Y-m-d\TH:i') ?>"><?= $campaign['endsAt']->format('F j, H:i') ?></time></p>
    </section>
    <?php $this->stop() ?>
<?php endif ?>

<div class="shop">
    <div class="shop-main">
        <?= $this->section('content') ?>
    </div>

    <aside class="shop-sidebar">
        <?= $this->section('filters') ?>
        <?php $this->insert('partials/sidebar', ['categories' => $categories, 'cart' => $cart, 'campaign' => $campaign]) ?>
    </aside>
</div>

<?php $this->unshift('scripts') ?>
<script src="/js/shop.js"></script>
<?php $this->stop() ?>
