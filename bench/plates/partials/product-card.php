<?php $this->layout('components/card', [
    'title' => $product->name,
    'href' => $product->url,
    'tone' => $product->onSale() ? 'sale' : 'plain',
]) ?>

<img src="<?= $this->e($product->image->src) ?>" alt="<?= $this->e($product->image->alt) ?>" width="<?= $product->image->width ?>" height="<?= $product->image->height ?>" loading="lazy">
<p class="vendor"><?= $this->e($product->vendor, 'strtoupper') ?> · <span class="sku"><?= $this->e($product->sku) ?></span></p>
<?php if (in_array('new', $product->tags, true)): ?>
    <span class="flag">New</span>
<?php endif ?>

<?php $this->insert('partials/price', ['product' => $product]) ?>
<?php $this->insert('partials/rating', ['rating' => $product->rating, 'count' => $product->reviews]) ?>

<?php if ($product->badges): ?>
    <ul class="badges">
        <?php foreach ($product->badges as $badge): ?>
            <li><?= $this->e($badge, 'strtoupper') ?></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
<ul class="tags">
    <?php foreach ($product->tags as $tag): ?>
        <li><a href="/tags/<?= $this->e($tag) ?>"><?= $this->e($tag) ?></a></li>
    <?php endforeach ?>
</ul>
<button type="button" data-add="<?= $this->e($product->sku) ?>"><?= $this->icon('cart') ?> Add to cart</button>
