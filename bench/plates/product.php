<?php $this->layout('layouts/shop', ['title' => $product->name, 'promo' => false] + $this->data()) ?>

<?php $this->push('head') ?>
<meta property="og:title" content="<?= $this->e($product->name) ?>">
<meta property="og:image" content="<?= $this->e($product->image->src) ?>">
<link rel="canonical" href="<?= $this->e($product->url) ?>">
<?php $this->stop() ?>

<article class="product" data-sku="<?= $this->e($product->sku) ?>">
    <div class="gallery">
        <?php foreach ($product->gallery as $image): ?>
            <img src="<?= $this->e($image->src) ?>" alt="<?= $this->e($image->alt) ?>" width="<?= $image->width ?>" height="<?= $image->height ?>">
        <?php endforeach ?>
    </div>

    <div class="summary">
        <h1><?= $this->e($product->name) ?></h1>
        <p class="vendor">by <a href="/brands/<?= $this->e($product->vendor, 'strtolower') ?>"><?= $this->e($product->vendor) ?></a></p>

        <?php $this->insert('partials/rating', ['rating' => $product->rating, 'count' => $product->reviews]) ?>
        <?php $this->insert('partials/price', ['product' => $product]) ?>

        <form class="variants" action="/cart" method="post">
            <?php foreach ($product->variants as $variant): ?>
                <label class="<?= $variant['available'] ? 'is-available' : 'is-unavailable' ?>">
                    <input type="radio" name="sku" value="<?= $this->e($variant['sku']) ?>"<?= $variant['sku'] === $product->sku ? ' checked' : '' ?><?= $variant['available'] ? '' : ' disabled' ?>>
                    <?= $this->e($variant['label']) ?>
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
                <th scope="row"><?= $this->e($name) ?></th>
                <td><?= $this->e($value) ?></td>
            </tr>
        <?php endforeach ?>
    </table>
</article>

<section class="reviews" id="reviews">
    <h2>Reviews (<?= count($reviews) ?>)</h2>
    <?php foreach ($reviews as $review): ?>
        <?php $this->insert('partials/review', ['review' => $review]) ?>
    <?php endforeach ?>
</section>

<section class="related">
    <h2>Customers also bought</h2>
    <div class="grid">
        <?php foreach ($related as $item): ?>
            <?php $this->insert('partials/product-card', ['product' => $item]) ?>
        <?php endforeach ?>
    </div>
</section>

<?php $this->push('scripts') ?>
<script src="/js/product.js" data-sku="<?= $this->e($product->sku) ?>"></script>
<?php $this->stop() ?>
