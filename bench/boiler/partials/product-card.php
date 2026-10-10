<?php $this->component('components/card', [
    'title' => $product->name,
    'href' => $product->url,
    'tone' => $product->onSale() ? 'sale' : 'plain',
]) ?>
    <img src="<?= $product->image->src ?>" alt="<?= $product->image->alt ?>" width="<?= $product->image->width ?>" height="<?= $product->image->height ?>" loading="lazy">
    <p class="vendor"><?= $product->vendor->upper() ?> · <span class="sku"><?= $product->sku ?></span></p>
    <?php if ($product->tags->contains('new')): ?>
        <span class="flag">New</span>
    <?php endif ?>

    <?php $this->include('partials/price', ['product' => $product]) ?>
    <?php $this->include('partials/rating', ['rating' => $product->rating, 'count' => $product->reviews]) ?>

    <?php if (count($product->badges) > 0): ?>
        <ul class="badges">
            <?php foreach ($product->badges as $badge): ?>
                <li><?= $badge->upper() ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <ul class="tags">
        <?php foreach ($product->tags as $tag): ?>
            <li><a href="/tags/<?= $tag ?>"><?= $tag ?></a></li>
        <?php endforeach ?>
    </ul>
    <button type="button" data-add="<?= $product->sku ?>"><?= $this->icon('cart') ?> Add to cart</button>
<?php $this->end() ?>
