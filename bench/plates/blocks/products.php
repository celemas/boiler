<section class="block-products">
    <h3><?= $this->e($block['title']) ?></h3>
    <div class="grid">
        <?php foreach ($block['products'] as $product): ?>
            <?php $this->insert('partials/product-card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>
</section>
