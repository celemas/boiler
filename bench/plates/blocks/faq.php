<section class="block-faq">
    <h3><?= $this->e($block['title']) ?></h3>
    <?php foreach ($block['items'] as $item): ?>
        <?php $this->insert('partials/faq-item', ['item' => $item]) ?>
    <?php endforeach ?>
</section>
