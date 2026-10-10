<?php $this->component('components/card', [
    'title' => $product->name,
    'href' => $product->url,
    'tone' => $product->onSale() ? 'sale' : 'plain',
]) ?>
    <img src="<?= $this->escape($product->image->src) ?>" alt="<?= $this->escape($product->image->alt) ?>" width="<?= $product->image->width ?>" height="<?= $product->image->height ?>" loading="lazy">
    <p class="vendor"><?= $this->escape(strtoupper($product->vendor)) ?> · <span class="sku"><?= $this->escape($product->sku) ?></span></p>
    <?php if (in_array('new', $product->tags, true)): ?>
        <span class="flag">New</span>
    <?php endif ?>

    <?php $this->include('partials/price', ['product' => $product]) ?>
    <?php $this->include('partials/rating', ['rating' => $product->rating, 'count' => $product->reviews]) ?>

    <?php if ($product->badges): ?>
        <ul class="badges">
            <?php foreach ($product->badges as $badge): ?>
                <li><?= $this->escape(strtoupper($badge)) ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <ul class="tags">
        <?php foreach ($product->tags as $tag): ?>
            <li><a href="/tags/<?= $this->escape($tag) ?>"><?= $this->escape($tag) ?></a></li>
        <?php endforeach ?>
    </ul>
    <button type="button" data-add="<?= $this->escape($product->sku) ?>"><?= $this->icon('cart') ?> Add to cart</button>
<?php $this->end() ?>
