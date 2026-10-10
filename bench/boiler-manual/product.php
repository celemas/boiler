<?php $this->layout('layouts/shop') ?>

<?php $this->section('title') ?><?= $this->escape($product->name) ?><?php $this->end() ?>

<?php $this->append('head') ?>
<meta property="og:title" content="<?= $this->escape($product->name) ?>">
<meta property="og:image" content="<?= $this->escape($product->image->src) ?>">
<link rel="canonical" href="<?= $this->escape($product->url) ?>">
<?php $this->end() ?>

<?php $this->section('hero') ?><?php $this->end() ?>

<article class="product" data-sku="<?= $this->escape($product->sku) ?>">
    <div class="gallery">
        <?php foreach ($product->gallery as $image): ?>
            <img src="<?= $this->escape($image->src) ?>" alt="<?= $this->escape($image->alt) ?>" width="<?= $image->width ?>" height="<?= $image->height ?>">
        <?php endforeach ?>
    </div>

    <div class="summary">
        <h1><?= $this->escape($product->name) ?></h1>
        <p class="vendor">by <a href="/brands/<?= $this->escape(strtolower($product->vendor)) ?>"><?= $this->escape($product->vendor) ?></a></p>

        <?php $this->include('partials/rating', ['rating' => $product->rating, 'count' => $product->reviews]) ?>
        <?php $this->include('partials/price') ?>

        <form class="variants" action="/cart" method="post">
            <?php foreach ($product->variants as $variant): ?>
                <label class="<?= $variant['available'] ? 'is-available' : 'is-unavailable' ?>">
                    <input type="radio" name="sku" value="<?= $this->escape($variant['sku']) ?>"<?= $variant['sku'] === $product->sku ? ' checked' : '' ?><?= $variant['available'] ? '' : ' disabled' ?>>
                    <?= $this->escape($variant['label']) ?>
                    <span><?= $this->money($variant['price']) ?></span>
                </label>
            <?php endforeach ?>
            <button type="submit"><?= $this->icon('cart') ?> Add to cart</button>
        </form>

        <div class="description"><?= $product->description ?></div>
    </div>

    <table class="specs">
        <caption>Specifications</caption>
        <?php foreach ($product->specs as $name => $value): ?>
            <tr>
                <th scope="row"><?= $this->escape($name) ?></th>
                <td><?= $this->escape($value) ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</article>

<section class="reviews" id="reviews">
    <h2>Reviews (<?= count($reviews) ?>)</h2>
    <?php foreach ($reviews as $review): ?>
        <?php $this->component('components/card', ['title' => $review->title, 'tone' => $review->verified ? 'verified' : 'plain']) ?>
            <?php $this->include('partials/rating', ['rating' => $review->rating]) ?>
            <p class="byline">
                <?= $this->escape($review->author) ?> ·
                <time datetime="<?= $review->date->format('Y-m-d') ?>"><?= $review->date->format('M j, Y') ?></time>
                <?php if ($review->verified): ?>
                    · <span class="verified">Verified purchase</span>
                <?php endif ?>
            </p>
            <p><?= $this->escape($review->body) ?></p>
        <?php $this->end() ?>
    <?php endforeach ?>
</section>

<section class="related">
    <h2>Customers also bought</h2>
    <div class="grid">
        <?php foreach ($related as $item): ?>
            <?php $this->include('partials/product-card', ['product' => $item]) ?>
        <?php endforeach ?>
    </div>
</section>

<?php $this->append('scripts') ?>
<script src="/js/product.js" data-sku="<?= $this->escape($product->sku) ?>"></script>
<?php $this->end() ?>
