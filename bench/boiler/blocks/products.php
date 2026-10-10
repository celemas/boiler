<section class="block-products">
    <h3><?= $block['title'] ?></h3>
    <div class="grid">
        <?php foreach ($block['products'] as $product): ?>
            <?php $this->include('partials/product-card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>
</section>
