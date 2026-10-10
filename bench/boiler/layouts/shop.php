<?php $this->layout('layouts/base', ['bodyClass' => 'page-shop']) ?>

<?php $this->rewrite('title') ?>
<?= $this->yield('title', 'All products') ?> | Shop
<?php $this->end() ?>

<?php $this->append('head') ?>
<link rel="stylesheet" href="/css/shop.css">
<?php $this->end() ?>

<?php $this->section('hero') ?>
<section class="promo">
    <h2><?= $campaign['title']->upper() ?></h2>
    <p>
        Use code <strong><?= $campaign['code'] ?></strong>
        for free shipping above <?= $this->money($campaign['threshold']) ?>.
    </p>
    <p>Ends <time datetime="<?= $campaign['endsAt']->format('Y-m-d\TH:i') ?>"><?= $campaign['endsAt']->format('F j, H:i') ?></time></p>
</section>
<?php $this->end() ?>

<div class="shop">
    <div class="shop-main">
        <?= $this->slot() ?>
    </div>

    <aside class="shop-sidebar">
        <?= $this->yield('sidebar', fn() => $this->include('partials/sidebar')) ?>
    </aside>
</div>

<?php $this->append('scripts') ?>
<script src="/js/shop.js"></script>
<?php $this->end() ?>
