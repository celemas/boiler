<?php use Celema\Boiler\Bench\Stock; ?>
<p class="price">
    <span class="price-now"><?= $this->money($product->price) ?></span>
    <?php if ($product->onSale()): ?>
        <s class="price-was"><?= $this->money($product->compareAt) ?></s>
        <span class="price-save">Save <?= $product->discount() ?>%</span>
    <?php endif ?>
</p>
<p class="stock stock-<?= $this->e($product->stock->value) ?>">
    <?php if ($product->stock === Stock::InStock): ?>
        In stock
    <?php elseif ($product->stock === Stock::Low): ?>
        Only <?= $product->stockCount ?> left
    <?php elseif ($product->stock === Stock::Preorder): ?>
        Preorder
    <?php else: ?>
        Sold out
    <?php endif ?>
    <?php if ($product->freeShipping): ?>
        · Free shipping
    <?php endif ?>
</p>
